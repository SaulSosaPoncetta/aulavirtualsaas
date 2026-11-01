<?php

namespace App\Services;

use App\Models\Abono;
use App\Models\Institucion;
use App\Models\User;

/**
 * Arma el enlace firmado que manda al cliente a pagar en el Panel y verifica los
 * avisos que el Panel manda de vuelta. Misma firma que EnlacePago del Panel.
 *
 * Referencia del cliente dentro de Aula Virtual:
 *   u{id}  → abono unipersonal del docente
 *   i{id}  → abono institucional
 */
class PanelPagos
{
    public static function habilitado(): bool
    {
        return (bool) config('panel.activo') && filled(config('panel.secret'));
    }

    public static function referencia(Abono $abono): string
    {
        return $abono->institucion_id ? 'i' . $abono->institucion_id : 'u' . $abono->user_id;
    }

    /** @return array{0:string,1:int}|null  [tipo (u|i), id] */
    public static function leerReferencia(?string $ref): ?array
    {
        return preg_match('/^([ui])(\d+)$/', (string) $ref, $m) ? [$m[1], (int) $m[2]] : null;
    }

    public function urlParaUsuario(User $user): string
    {
        $abono = Abono::deUsuario($user);

        return $this->url([
            'ref'    => self::referencia($abono),
            'plan'   => config('panel.planes.unipersonal'),
            'email'  => $user->email,
            'nombre' => $user->nombre_completo,
        ]);
    }

    public function urlParaInstitucion(Institucion $institucion, User $admin): string
    {
        $abono = Abono::deInstitucion($institucion);

        return $this->url([
            'ref'      => self::referencia($abono),
            'plan'     => config('panel.planes.institucional'),
            'cantidad' => max(1, (int) $abono->cupo_docentes),
            'minimo'   => max(1, $abono->docentesUsados()),
            'email'    => $admin->email,
            'nombre'   => $institucion->nombre,
        ]);
    }

    public function url(array $params): string
    {
        $params['sistema'] = config('panel.slug');
        $params['ts']      = (string) time();
        $params = array_filter(array_map(fn ($v) => is_scalar($v) ? (string) $v : null, $params), fn ($v) => $v !== null && $v !== '');
        $params['firma'] = self::firmar($params, (string) config('panel.secret'));

        return config('panel.url') . '/pagar?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    public static function firmar(array $params, string $secret): string
    {
        unset($params['firma']);
        $params = array_filter($params, fn ($v) => $v !== null && $v !== '');
        ksort($params);

        return hash_hmac('sha256', http_build_query($params, '', '&', PHP_QUERY_RFC3986), $secret);
    }

    /** Firma del aviso del Panel: HMAC-SHA256 del cuerpo exacto recibido. */
    public static function firmaValida(string $cuerpo, ?string $firma): bool
    {
        $secret = (string) config('panel.secret');

        return $secret !== '' && filled($firma) && hash_equals(hash_hmac('sha256', $cuerpo, $secret), (string) $firma);
    }
}
