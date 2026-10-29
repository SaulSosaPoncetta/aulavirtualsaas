<?php

namespace App\Services\Integraciones;

use App\Models\Sincronizacion;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Cliente de la API del SGI (/api/aulavirtual/...)
 * Usa el token de servicio SGI_TOKEN.
 */
class SgiClient
{
    protected string $base;
    protected int $timeout;
    protected ?string $token;

    public function __construct()
    {
        $cfg = config('integraciones.sgi');
        $this->base = $cfg['url'] . $cfg['prefix'];
        $this->token = $cfg['token'];
        $this->timeout = config('integraciones.timeout', 15);
    }

    protected function url(string $clave, array $params = []): string
    {
        $ruta = config("integraciones.sgi.endpoints.{$clave}");
        foreach ($params as $k => $v) {
            $ruta = str_replace('{' . $k . '}', urlencode((string) $v), $ruta);
        }
        return $this->base . $ruta;
    }

    protected function http(): PendingRequest
    {
        $req = Http::acceptJson()->asJson()->timeout($this->timeout);
        return $this->token ? $req->withToken($this->token) : $req;
    }

    /**
     * Valida un alumno contra el SGI por DNI.
     *
     * Respuesta esperada del SGI:
     * {
     *   "alumno":      { "id", "dni", "nombre", "apellido", "email" },
     *   "institucion": { "id", "nombre", "cue", "nivel", "localidad" }
     * }
     */
    public function loginAlumno(string $dni, string $password): array
    {
        try {
            $resp = $this->http()->post($this->url('login_alumno'), [
                'dni'      => $dni,
                'password' => $password,
            ]);
        } catch (ConnectionException $e) {
            $this->registrar('login', 'error', null, 'Sin conexión con el SGI: ' . $e->getMessage());
            return $this->fallo('No se pudo conectar con el sistema de la escuela. Intentá nuevamente en unos minutos.');
        }

        if (in_array($resp->status(), [401, 404, 422])) {
            return $this->fallo('DNI o contraseña incorrectos.');
        }

        if (! $resp->successful()) {
            $this->registrar('login', 'error', $resp->status(), $resp->body());
            return $this->fallo('El sistema de la escuela respondió con un error (' . $resp->status() . ').');
        }

        $json   = $resp->json() ?? [];
        $alumno = $json['alumno'] ?? data_get($json, 'data.alumno');
        $inst   = $json['institucion'] ?? data_get($json, 'data.institucion');

        if (! is_array($alumno) || empty($alumno['id'])) {
            $this->registrar('login', 'error', $resp->status(), 'Respuesta sin alumno', $json);
            return $this->fallo('No se pudieron obtener los datos del alumno.');
        }

        $this->registrar('login', 'ok', $resp->status(), 'Login alumno DNI ' . $dni);

        return [
            'ok'          => true,
            'mensaje'     => 'OK',
            'alumno'      => [
                'id'       => (int) $alumno['id'],
                'dni'      => preg_replace('/\D/', '', (string) ($alumno['dni'] ?? $dni)),
                'nombre'   => $alumno['nombre'] ?? $alumno['name'] ?? '',
                'apellido' => $alumno['apellido'] ?? null,
                'email'    => $alumno['email'] ?? null,
            ],
            'institucion' => is_array($inst) && ! empty($inst['id']) ? [
                'id'        => (int) $inst['id'],
                'nombre'    => $inst['nombre'] ?? 'Institución',
                'cue'       => $inst['cue'] ?? null,
                'nivel'     => $inst['nivel'] ?? null,
                'localidad' => $inst['localidad'] ?? null,
            ] : null,
        ];
    }

    protected function fallo(string $mensaje): array
    {
        return ['ok' => false, 'mensaje' => $mensaje, 'alumno' => null, 'institucion' => null];
    }

    protected function registrar(string $tipo, string $estado, ?int $http, ?string $mensaje, ?array $payload = null): void
    {
        Sincronizacion::create([
            'sistema'     => 'sgi',
            'tipo'        => $tipo,
            'direccion'   => 'salida',
            'estado'      => $estado,
            'codigo_http' => $http,
            'mensaje'     => $mensaje ? mb_substr($mensaje, 0, 2000) : null,
            'payload'     => $payload,
            'intentos'    => 1,
        ]);
    }
}
