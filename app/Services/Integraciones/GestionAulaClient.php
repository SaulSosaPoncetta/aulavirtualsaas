<?php

namespace App\Services\Integraciones;

use App\Models\Sincronizacion;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * Cliente de la API de GestiónAula (/api/app/...)
 */
class GestionAulaClient
{
    protected string $base;
    protected int $timeout;

    public function __construct()
    {
        $cfg = config('integraciones.gestionaula');
        $this->base = $cfg['url'] . $cfg['prefix'];
        $this->timeout = config('integraciones.timeout', 15);
    }

    protected function url(string $clave): string
    {
        return $this->base . config("integraciones.gestionaula.endpoints.{$clave}");
    }

    protected function http(?string $token = null): PendingRequest
    {
        $req = Http::acceptJson()->asJson()->timeout($this->timeout);
        return $token ? $req->withToken($token) : $req;
    }

    /**
     * Valida un docente contra GestiónAula.
     * Devuelve: ['ok' => bool, 'mensaje' => string, 'token' => ?string, 'docente' => ?array]
     */
    public function login(string $email, string $password): array
    {
        try {
            $resp = $this->http()->post($this->url('login'), [
                'email'       => $email,
                'password'    => $password,
                'device_name' => 'aulavirtual',
            ]);
        } catch (ConnectionException $e) {
            $this->registrar('login', 'error', null, 'Sin conexión con GestiónAula: ' . $e->getMessage());
            return $this->fallo('No se pudo conectar con GestiónAula. Intentá nuevamente en unos minutos.');
        }

        if ($resp->status() === 401 || $resp->status() === 422) {
            return $this->fallo('Email o contraseña incorrectos.');
        }

        if (! $resp->successful()) {
            $this->registrar('login', 'error', $resp->status(), $resp->body());
            return $this->fallo('GestiónAula respondió con un error (' . $resp->status() . ').');
        }

        $json  = $resp->json() ?? [];
        $token = $json['token'] ?? $json['access_token'] ?? data_get($json, 'data.token');

        if (! $token) {
            $this->registrar('login', 'error', $resp->status(), 'Respuesta sin token', $json);
            return $this->fallo('GestiónAula no devolvió un token válido.');
        }

        $usuario = $json['user'] ?? $json['usuario'] ?? data_get($json, 'data.user') ?? data_get($json, 'data.usuario');

        // Si el login no trae el usuario, se pide aparte
        if (! is_array($usuario) || empty($usuario['id'])) {
            $usuario = $this->usuario($token);
        }

        if (! $usuario || empty($usuario['id'])) {
            return $this->fallo('No se pudieron obtener los datos del docente.');
        }

        $this->registrar('login', 'ok', $resp->status(), 'Login docente ' . $email);

        return [
            'ok'      => true,
            'mensaje' => 'OK',
            'token'   => $token,
            'docente' => $this->normalizarDocente($usuario),
        ];
    }

    public function usuario(string $token): ?array
    {
        try {
            $resp = $this->http($token)->get($this->url('usuario'));
        } catch (ConnectionException) {
            return null;
        }

        if (! $resp->successful()) {
            return null;
        }

        $json = $resp->json() ?? [];
        return $json['user'] ?? $json['data'] ?? $json;
    }

    public function logout(?string $token): void
    {
        if (! $token) {
            return;
        }

        try {
            $this->http($token)->post($this->url('logout'));
        } catch (\Throwable) {
            // Si falla no impide cerrar la sesión local
        }
    }

    protected function normalizarDocente(array $u): array
    {
        return [
            'id'       => (int) $u['id'],
            'nombre'   => $u['nombre'] ?? $u['name'] ?? '',
            'apellido' => $u['apellido'] ?? null,
            'email'    => $u['email'] ?? null,
            'dni'      => isset($u['dni']) ? preg_replace('/\D/', '', (string) $u['dni']) : null,
        ];
    }

    protected function fallo(string $mensaje): array
    {
        return ['ok' => false, 'mensaje' => $mensaje, 'token' => null, 'docente' => null];
    }

    protected function registrar(string $tipo, string $estado, ?int $http, ?string $mensaje, ?array $payload = null): void
    {
        Sincronizacion::create([
            'sistema'     => 'gestionaula',
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
