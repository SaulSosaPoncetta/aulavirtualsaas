<?php

namespace App\Services;

use App\Models\SistemaSaas;

/**
 * Enlace firmado con el que un sistema satélite manda a su cliente a pagar en el Panel.
 *
 * Parámetros permitidos (todos string):
 *   sistema  slug del sistema                      (obligatorio)
 *   ref      id del cliente dentro del sistema     (obligatorio)
 *   ts       timestamp UNIX de generación          (obligatorio)
 *   plan     código del plan del catálogo          (opcional; si va, el plan queda fijo)
 *   cantidad cupo contratado (ej: docentes)        (opcional)
 *   minimo   cupo mínimo permitido (ej: docentes ya en uso, no se puede bajar de ahí) (opcional)
 *   email, nombre, cuit  datos para dar de alta al cliente si el Panel todavía no lo conoce (opcionales)
 *   firma    HMAC-SHA256 hex
 *
 * Firma: se quitan "firma" y los vacíos, se ordenan las claves (ksort), se arma
 * http_build_query($params, '', '&', PHP_QUERY_RFC3986) y se calcula
 * hash_hmac('sha256', ese_texto, webhook_secret_del_sistema).
 */
class EnlacePago
{
    public const PERMITIDOS = ['sistema', 'ref', 'ts', 'plan', 'cantidad', 'minimo', 'email', 'nombre', 'cuit', 'firma'];

    /** Minutos que el enlace sigue siendo válido. */
    public const VIGENCIA_MINUTOS = 60;

    public static function filtrar(array $query): array
    {
        $limpio = [];
        foreach (self::PERMITIDOS as $clave) {
            if (isset($query[$clave]) && is_scalar($query[$clave]) && $query[$clave] !== '') {
                $limpio[$clave] = (string) $query[$clave];
            }
        }

        return $limpio;
    }

    public static function firmar(array $params, string $secret): string
    {
        unset($params['firma']);
        $params = array_filter($params, fn ($v) => $v !== null && $v !== '');
        ksort($params);

        return hash_hmac('sha256', http_build_query($params, '', '&', PHP_QUERY_RFC3986), $secret);
    }

    public static function verificar(array $params, string $secret): bool
    {
        $firma = $params['firma'] ?? '';
        $ts = (int) ($params['ts'] ?? 0);

        if ($firma === '' || $ts <= 0) {
            return false;
        }

        if (abs(time() - $ts) > self::VIGENCIA_MINUTOS * 60) {
            return false;
        }

        return hash_equals(self::firmar($params, $secret), $firma);
    }

    /** Arma la URL completa de la página de pago del Panel (la usa el comando de prueba). */
    public static function generarUrl(SistemaSaas $sistema, array $params): string
    {
        $params['sistema'] = $sistema->slug;
        $params['ts'] = (string) time();
        $params = self::filtrar($params);
        $params['firma'] = self::firmar($params, $sistema->webhook_secret);

        return route('pagar.mostrar').'?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
