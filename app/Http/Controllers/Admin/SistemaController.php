<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RespaldoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/** Estado técnico: correo, cola de envíos y respaldos. */
class SistemaController extends Controller
{
    public function index(RespaldoService $respaldos)
    {
        $hayJobs   = Schema::hasTable('jobs');
        $hayFallos = Schema::hasTable('failed_jobs');

        return view('admin.sistema', [
            'correo' => [
                'mailer'   => config('mail.default'),
                'host'     => config('mail.mailers.smtp.host'),
                'puerto'   => config('mail.mailers.smtp.port'),
                'usuario'  => config('mail.mailers.smtp.username'),
                'remitente'=> config('mail.from.address'),
                'nombre'   => config('mail.from.name'),
            ],
            'cola' => [
                'conexion'  => config('queue.default'),
                'porCron'   => config('sistema.cola_por_cron'),
                'pendientes'=> $hayJobs ? DB::table('jobs')->count() : null,
                'masViejo'  => $hayJobs ? DB::table('jobs')->min('created_at') : null,
                'fallidos'  => $hayFallos ? DB::table('failed_jobs')->latest('failed_at')->take(10)->get() : collect(),
                'totalFallidos' => $hayFallos ? DB::table('failed_jobs')->count() : 0,
            ],
            'respaldos' => $respaldos->listar(),
            'conservar' => config('sistema.respaldos.conservar'),
        ]);
    }

    /** Envía un correo de prueba en el momento (sin cola) para ver si el SMTP anda. */
    public function probarCorreo(Request $request)
    {
        $datos = $request->validate(['email' => ['required', 'email']]);

        try {
            Mail::raw(
                "¡Funciona! Este es un correo de prueba del Aula Virtual.\n\nServidor: " . config('mail.mailers.smtp.host') . "\nEnviado: " . now('America/Argentina/Buenos_Aires')->format('d/m/Y H:i:s'),
                fn ($m) => $m->to($datos['email'])->subject('Correo de prueba — ' . config('app.name'))
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'No se pudo enviar: ' . $e->getMessage());
        }

        return back()->with('ok', "Correo de prueba enviado a {$datos['email']}. Revisá también la carpeta de spam.");
    }

    public function reintentarFallidos()
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        return back()->with('ok', 'Los envíos fallidos volvieron a la cola.');
    }

    public function borrarFallidos()
    {
        Artisan::call('queue:flush');

        return back()->with('ok', 'Se borró la lista de envíos fallidos.');
    }

    public function crearRespaldo(RespaldoService $respaldos)
    {
        try {
            $r = $respaldos->crear();
        } catch (\Throwable $e) {
            $respaldos->avisarFalla($e);

            return back()->with('error', 'No se pudo crear el respaldo: ' . $e->getMessage());
        }

        return back()->with('ok', "Respaldo {$r['nombre']} creado (" . self::tamano($r['tamano']) . ", {$r['archivos']} archivos).");
    }

    public function descargarRespaldo(string $nombre, RespaldoService $respaldos)
    {
        $ruta = $respaldos->ruta($nombre);
        abort_unless($ruta, 404);

        return response()->download($ruta, $nombre, ['Cache-Control' => 'no-store']);
    }

    public function borrarRespaldo(string $nombre, RespaldoService $respaldos)
    {
        $ruta = $respaldos->ruta($nombre);
        abort_unless($ruta, 404);

        @unlink($ruta);

        return back()->with('ok', "Respaldo {$nombre} eliminado.");
    }

    public static function tamano(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $u) {
            if ($bytes < 1024 || $u === 'GB') {
                return ($u === 'B' ? $bytes : number_format($bytes, 1, ',', '.')) . ' ' . $u;
            }
            $bytes /= 1024;
        }

        return '';
    }
}
