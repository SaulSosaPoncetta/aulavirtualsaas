<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Facades\File;

/**
 * Limpia el HTML que viene del editor: deja solo formato seguro
 * (negrita, listas, títulos, links, imágenes...) y elimina scripts, estilos y eventos.
 */
class HtmlSeguro
{
    protected static ?HTMLPurifier $purifier = null;

    protected static function purifier(): HTMLPurifier
    {
        if (static::$purifier) {
            return static::$purifier;
        }

        $cache = storage_path('app/purifier');
        File::ensureDirectoryExists($cache);

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', $cache);
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', implode(',', [
            'p[class]', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
            'h2[class]', 'h3[class]', 'ul', 'ol', 'li[class]',
            'a[href|target|rel]', 'blockquote', 'pre', 'code',
            'img[src|alt|width|height]', 'span',
        ]));
        // Clases que usa el editor para alinear y sangrar
        $config->set('Attr.AllowedClasses', [
            'ql-align-center', 'ql-align-right', 'ql-align-justify',
            'ql-indent-1', 'ql-indent-2', 'ql-indent-3', 'ql-indent-4',
        ]);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('HTML.TargetBlank', true);
        $config->set('HTML.TargetNoopener', true);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('AutoFormat.Linkify', true);

        return static::$purifier = new HTMLPurifier($config);
    }

    public static function limpiar(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $limpio = trim(static::purifier()->purify($html));

        return static::estaVacio($limpio) ? null : $limpio;
    }

    /** Sin texto ni imágenes (por ejemplo "<p><br></p>"). */
    public static function estaVacio(?string $html): bool
    {
        if ($html === null) {
            return true;
        }

        $texto = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'), " \t\n\r\0\x0B\xC2\xA0");

        return $texto === '' && ! str_contains($html, '<img');
    }

    /** ¿El contenido guardado es HTML del editor o texto plano viejo? */
    public static function esHtml(?string $contenido): bool
    {
        return $contenido !== null && preg_match('~^\s*<(p|h2|h3|ul|ol|blockquote|pre|img)[\s>]~i', $contenido) === 1;
    }

    /** Texto plano (para notificaciones, vistas previas y emails). */
    public static function aTexto(?string $contenido, int $max = 0): string
    {
        $t = $contenido ?? '';

        if (static::esHtml($t)) {
            $t = preg_replace('~</(p|h2|h3|li|blockquote|pre)>~i', "$0\n", $t);
            $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $t = trim(preg_replace("/\n{3,}/", "\n\n", $t));

        return $max > 0 ? \Illuminate\Support\Str::limit($t, $max) : $t;
    }

    /** Para cargar en el editor contenido viejo en texto plano. */
    public static function paraEditor(?string $contenido): string
    {
        if ($contenido === null || $contenido === '') {
            return '';
        }

        return static::esHtml($contenido)
            ? $contenido
            : '<p>' . nl2br(e($contenido)) . '</p>';
    }
}
