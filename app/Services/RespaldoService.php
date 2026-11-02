<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use ZipArchive;

/**
 * Respaldo completo en un .zip: base.sql (mysqldump) + archivos/private + archivos/public.
 */
class RespaldoService
{
    public function carpeta(): string
    {
        $dir = storage_path('app/respaldos');
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    /** Crea el respaldo y devuelve sus datos. Lanza excepción si falla. */
    public function crear(): array
    {
        @set_time_limit(0);

        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Falta la extensión zip de PHP (php-zip).');
        }

        $ahora  = Carbon::now('America/Argentina/Buenos_Aires');
        $nombre = 'aulavirtual_' . $ahora->format('Y-m-d_His') . '.zip';
        $zipRuta = $this->carpeta() . DIRECTORY_SEPARATOR . $nombre;
        $sql    = $this->carpeta() . DIRECTORY_SEPARATOR . 'tmp_' . $ahora->format('His') . '_' . bin2hex(random_bytes(3)) . '.sql';

        try {
            $this->volcarBase($sql);

            $zip = new ZipArchive();
            if ($zip->open($zipRuta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException("No se pudo crear {$nombre}.");
            }

            $zip->addFile($sql, 'base.sql');

            $archivos = 0;
            if (config('sistema.respaldos.incluir_archivos')) {
                $archivos += $this->agregarCarpeta($zip, storage_path('app/private'), 'archivos/private');
                $archivos += $this->agregarCarpeta($zip, storage_path('app/public'), 'archivos/public');
            }

            $zip->setArchiveComment('Aula Virtual — respaldo del ' . $ahora->format('d/m/Y H:i'));

            if (! $zip->close()) {
                throw new RuntimeException('No se pudo cerrar el .zip (¿falta espacio en disco?).');
            }
        } catch (\Throwable $e) {
            File::delete($zipRuta);
            throw $e;
        } finally {
            File::delete($sql);
        }

        $borrados = $this->limpiar();

        Log::info("Respaldo creado: {$nombre}", ['archivos' => $archivos, 'borrados' => $borrados]);

        return ['nombre' => $nombre, 'tamano' => filesize($zipRuta), 'archivos' => $archivos, 'borrados' => $borrados];
    }

    /** Lista de respaldos, del más nuevo al más viejo. */
    public function listar(): array
    {
        return collect(File::files($this->carpeta()))
            ->filter(fn ($f) => $f->getExtension() === 'zip')
            ->map(fn ($f) => [
                'nombre' => $f->getFilename(),
                'tamano' => $f->getSize(),
                'fecha'  => Carbon::createFromTimestamp($f->getMTime(), 'America/Argentina/Buenos_Aires'),
            ])
            ->sortByDesc(fn ($r) => $r['fecha']->getTimestamp())
            ->values()
            ->all();
    }

    /** Ruta segura de un respaldo por nombre (null si no existe o el nombre es raro). */
    public function ruta(string $nombre): ?string
    {
        if (! preg_match('/^aulavirtual_[0-9_\-]+\.zip$/', $nombre)) {
            return null;
        }

        $ruta = $this->carpeta() . DIRECTORY_SEPARATOR . $nombre;

        return is_file($ruta) ? $ruta : null;
    }

    /** Deja solo los N más nuevos. */
    public function limpiar(): int
    {
        $conservar = max(1, (int) config('sistema.respaldos.conservar', 14));
        $sobran    = array_slice($this->listar(), $conservar);

        foreach ($sobran as $r) {
            File::delete($this->carpeta() . DIRECTORY_SEPARATOR . $r['nombre']);
        }

        return count($sobran);
    }

    /** Avisa por email que el respaldo falló (si está configurado). */
    public function avisarFalla(\Throwable $e): void
    {
        Log::error('Falló el respaldo: ' . $e->getMessage());

        $destino = config('sistema.respaldos.avisar_a');
        if (! $destino) {
            return;
        }

        try {
            Mail::raw(
                "El respaldo automático del Aula Virtual falló el " . now('America/Argentina/Buenos_Aires')->format('d/m/Y H:i') . ".\n\n"
                . "Error: " . $e->getMessage() . "\n\nRevisá storage/logs/laravel.log en el servidor.",
                fn ($m) => $m->to($destino)->subject('⚠ Falló el respaldo — ' . config('app.name'))
            );
        } catch (\Throwable) {
            // Si tampoco anda el correo, queda en el log
        }
    }

    // ===================== Internos =====================

    protected function volcarBase(string $destino): void
    {
        $conexion = config('database.default');
        $db       = config("database.connections.{$conexion}");

        if (! in_array($db['driver'] ?? '', ['mysql', 'mariadb'])) {
            throw new RuntimeException('El respaldo de base de datos solo funciona con MySQL / MariaDB.');
        }

        $comando = [
            config('sistema.respaldos.mysqldump', 'mysqldump'),
            '--host=' . ($db['host'] ?? '127.0.0.1'),
            '--port=' . ($db['port'] ?? 3306),
            '--user=' . $db['username'],
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--no-tablespaces',
            '--default-character-set=utf8mb4',
            '--result-file=' . $destino,
            $db['database'],
        ];

        // La contraseña va por variable de entorno, no queda a la vista en la lista de procesos
        $r = Process::timeout(900)->env(['MYSQL_PWD' => (string) $db['password']])->run($comando);

        if (! $r->successful() || ! is_file($destino) || filesize($destino) < 100) {
            $error = trim($r->errorOutput()) ?: 'mysqldump no generó el archivo.';
            throw new RuntimeException('mysqldump: ' . $error);
        }
    }

    protected function agregarCarpeta(ZipArchive $zip, string $origen, string $prefijo): int
    {
        if (! is_dir($origen)) {
            return 0;
        }

        $n = 0;
        foreach (File::allFiles($origen) as $f) {
            if ($f->getFilename() === '.gitignore') {
                continue;
            }

            $relativa = str_replace('\\', '/', $f->getRelativePathname());
            $zip->addFile($f->getRealPath(), "{$prefijo}/{$relativa}");
            $zip->setCompressionName("{$prefijo}/{$relativa}", $this->yaComprimido($f->getExtension()) ? ZipArchive::CM_STORE : ZipArchive::CM_DEFLATE);
            $n++;
        }

        return $n;
    }

    /** Fotos, videos, PDFs y zips no ganan nada comprimiéndolos de nuevo. */
    protected function yaComprimido(string $ext): bool
    {
        return in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'mp3', 'zip', 'rar', '7z', 'pdf', 'docx', 'xlsx', 'pptx']);
    }
}
