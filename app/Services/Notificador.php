<?php

namespace App\Services;

use App\Models\Abono;
use App\Models\Aula;
use App\Models\Clase;
use App\Models\Comentario;
use App\Models\Entrega;
use App\Models\Evaluacion;
use App\Models\Intento;
use App\Models\Pago;
use App\Models\Plan;
use App\Models\Publicacion;
use App\Models\Tarea;
use App\Models\User;
use App\Notifications\Aviso;
use App\Notifications\AvisoMail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Decide a quién avisar y qué decir. Todo pasa por acá.
 */
class Notificador
{
    // ===================== Envío =====================

    /**
     * @param  iterable<User>  $usuarios
     */
    public function enviar(iterable $usuarios, array $datos, bool $conMail = false, ?int $excluir = null): void
    {
        $excluir ??= auth()->id();

        $lista = collect($usuarios)->filter()
            ->unique('id')
            ->reject(fn ($u) => $excluir && $u->id == $excluir)
            ->filter(fn ($u) => $u->activo)
            ->values();

        if ($lista->isEmpty()) {
            return;
        }

        Notification::send($lista, new Aviso($datos));

        if ($conMail) {
            $lista->filter(fn ($u) => $u->email && $u->email_verified_at && $u->notificar_email)
                ->each(fn ($u) => $u->notify(new AvisoMail($datos)));
        }
    }

    protected function datos(string $tipo, string $titulo, string $texto, string $url, string $icono, ?Aula $aula = null): array
    {
        return [
            'tipo'   => $tipo,
            'titulo' => $titulo,
            'texto'  => HtmlSeguro::aTexto($texto, 200),
            'url'    => $url,
            'icono'  => $icono,
            'aula'   => $aula?->nombre,
        ];
    }

    protected function docentes(Aula $aula): Collection
    {
        return $aula->docentes()->get();
    }

    protected function alumnos(Aula $aula): Collection
    {
        return $aula->alumnos()->get();
    }

    // ===================== Contenido nuevo =====================

    /** Avisa a los alumnos cuando una clase, tarea o evaluación queda publicada (una sola vez). */
    public function contenidoPublicado(Model $m): void
    {
        if ($m->notificado_at || ! method_exists($m, 'estaPublicada') || ! $m->estaPublicada()) {
            return;
        }

        if ($m instanceof Evaluacion && ! $m->preguntas()->exists()) {
            return;
        }

        $aula = $m->aula;
        if (! $aula || $aula->estado !== 'activa') {
            return;
        }

        [$tipo, $titulo, $texto, $url, $icono] = match (true) {
            $m instanceof Clase => ['clase', 'Nueva clase', $m->titulo, route('clases.show', $m), 'bi-journal-text'],
            $m instanceof Tarea => ['tarea', 'Nueva tarea', $m->titulo . ($m->fecha_limite ? ' · vence el ' . $m->fecha_limite->format('d/m H:i') : ''), route('tareas.show', $m), 'bi-clipboard-check'],
            default             => ['evaluacion', 'Nueva evaluación', $m->titulo . ($m->fecha_cierre ? ' · cierra el ' . $m->fecha_cierre->format('d/m H:i') : ''), route('evaluaciones.show', $m), 'bi-ui-checks'],
        };

        $m->forceFill(['notificado_at' => Carbon::now('America/Argentina/Buenos_Aires')])->saveQuietly();

        $this->enviar($this->alumnos($aula), $this->datos($tipo, $titulo, $texto, $url, $icono, $aula), true, $m->user_id);
    }

    /**
     * Revisa lo programado que ya se publicó y las evaluaciones listas.
     * Las evaluaciones se avisan cuando hace 10 minutos que no se tocan sus preguntas
     * (para no avisar mientras el docente las está cargando).
     */
    public function revisarProgramados(): int
    {
        $avisados = 0;

        foreach ([Clase::class, Tarea::class] as $modelo) {
            $modelo::with('aula')->whereNull('notificado_at')->where('visible', true)->get()
                ->filter(fn ($m) => $m->estaPublicada())
                ->each(function ($m) use (&$avisados) {
                    $this->contenidoPublicado($m);
                    $avisados++;
                });
        }

        $limite = Carbon::now('America/Argentina/Buenos_Aires')->subMinutes(10);

        Evaluacion::with('aula')->whereNull('notificado_at')->where('visible', true)
            ->whereHas('preguntas')
            ->withMax('preguntas', 'updated_at')
            ->get()
            ->filter(fn ($e) => $e->estaPublicada() && ! $e->estaCerrada()
                && Carbon::parse($e->preguntas_max_updated_at, 'America/Argentina/Buenos_Aires')->lte($limite))
            ->each(function ($e) use (&$avisados) {
                $this->contenidoPublicado($e);
                $avisados++;
            });

        return $avisados;
    }

    // ===================== Tareas =====================

    public function entrega(Entrega $e): void
    {
        $tarea = $e->tarea;
        $aula  = $tarea?->aula;
        if (! $aula) {
            return;
        }

        if ($e->estado === 'entregada') {
            // A los docentes: solo campanita (pueden ser muchas)
            $this->enviar($this->docentes($aula), $this->datos(
                'entrega', 'Nueva entrega',
                ($e->alumno?->nombre_completo ?? 'Un alumno') . ' entregó "' . $tarea->titulo . '"' . ($e->esTardia() ? ' (fuera de término)' : ''),
                route('entregas.show', $e), 'bi-inbox', $aula
            ), false, $e->alumno_id);

            return;
        }

        $texto = $e->estado === 'corregida'
            ? '"' . $tarea->titulo . '" ya está corregida' . ($e->nota_texto !== null ? ': tu nota es ' . $e->nota_texto . '/' . $tarea->puntaje_maximo_texto : '') . '.'
            : 'Tu docente te devolvió "' . $tarea->titulo . '" para que la rehagas.';

        $this->enviar([$e->alumno], $this->datos(
            'correccion', $e->estado === 'corregida' ? 'Tarea corregida' : 'Tarea para rehacer',
            $texto, route('tareas.show', $tarea), $e->estado === 'corregida' ? 'bi-patch-check' : 'bi-arrow-return-left', $aula
        ), true);
    }

    // ===================== Evaluaciones =====================

    public function intento(Intento $i): void
    {
        $ev   = $i->evaluacion;
        $aula = $ev?->aula;
        if (! $aula) {
            return;
        }

        if ($i->estado === 'pendiente_revision') {
            $this->enviar($this->docentes($aula), $this->datos(
                'revision', 'Respuestas para corregir',
                ($i->alumno?->nombre_completo ?? 'Un alumno') . ' terminó "' . $ev->titulo . '" y tiene respuestas escritas para corregir.',
                route('intentos.show', $i), 'bi-pencil-square', $aula
            ), false, $i->alumno_id);

            return;
        }

        // Corrección manual terminada
        if ($i->estado === 'finalizado' && $i->corregido_por) {
            $this->enviar([$i->alumno], $this->datos(
                'correccion', 'Evaluación corregida',
                '"' . $ev->titulo . '" ya está corregida' . ($i->nota_texto !== null ? ': tu nota es ' . $i->nota_texto . '/' . $ev->puntaje_maximo_texto : '') . '.',
                route('intentos.show', $i), 'bi-patch-check', $aula
            ), true);
        }
    }

    // ===================== Muro =====================

    public function publicacion(Publicacion $p): void
    {
        $aula = $p->aula;
        if (! $aula || ! $p->autor) {
            return;
        }

        $esDocente = $aula->puedeEditar($p->autor);

        $this->enviar(
            $esDocente ? $this->alumnos($aula)->concat($this->docentes($aula)) : $this->docentes($aula),
            $this->datos('muro', $esDocente ? 'Nuevo aviso en el muro' : 'Nueva publicación en el muro',
                $p->autor->nombre_completo . ': ' . $p->texto, route('muro.index', $aula) . '#pub' . $p->id, 'bi-megaphone', $aula),
            $esDocente, // mail solo para los avisos del docente
            $p->user_id
        );
    }

    public function comentario(Comentario $c): void
    {
        $pub  = $c->publicacion;
        $aula = $pub?->aula;
        if (! $aula || ! $pub->autor || $pub->user_id == $c->user_id) {
            return;
        }

        $this->enviar([$pub->autor], $this->datos(
            'comentario', 'Nuevo comentario',
            ($c->autor?->nombre_completo ?? 'Alguien') . ' comentó: ' . $c->texto,
            route('muro.index', $aula) . '#pub' . $pub->id, 'bi-chat-left-text', $aula
        ), false, $c->user_id);
    }

    // ===================== Abonos =====================

    protected function destinatariosAbono(Abono $a): Collection
    {
        return $a->institucion_id
            ? $a->institucion->administradores()->wherePivot('activo', true)->get()
            : collect([$a->usuario]);
    }

    protected function urlAbono(Abono $a): string
    {
        return $a->institucion_id ? route('abonos.institucion', $a->institucion_id) : route('abonos.mio');
    }

    public function pagoAprobado(Pago $p): void
    {
        $a = $p->abono->fresh();

        $this->enviar($this->destinatariosAbono($a), $this->datos(
            'pago', 'Pago acreditado',
            'Recibimos tu pago de ' . Plan::pesos($p->importe) . '. ' . ($a->vence_el ? 'El abono vence el ' . $a->vence_el->format('d/m/Y') . '.' : ''),
            $this->urlAbono($a), 'bi-check-circle'
        ), true, 0);
    }

    // ===================== Recordatorios diarios =====================

    public function recordatorios(): array
    {
        $ahora  = Carbon::now('America/Argentina/Buenos_Aires');
        $manana = $ahora->copy()->addDay();
        $cant   = ['tareas' => 0, 'evaluaciones' => 0, 'abonos' => 0];

        // Tareas que vencen en las próximas 24 h: alumnos que no entregaron
        Tarea::with('aula')->where('visible', true)->whereNotNull('fecha_limite')->get()
            ->filter(fn ($t) => $t->estaPublicada() && $t->fecha_limite->between($ahora, $manana) && $t->aula?->estado === 'activa')
            ->each(function ($t) use (&$cant) {
                $entregaron = $t->entregas()->pluck('alumno_id');
                $faltan = $this->alumnos($t->aula)->whereNotIn('id', $entregaron);
                $this->enviar($faltan, $this->datos('recordatorio', 'Tarea por vencer',
                    '"' . $t->titulo . '" vence el ' . $t->fecha_limite->format('d/m') . ' a las ' . $t->fecha_limite->format('H:i') . ' y todavía no la entregaste.',
                    route('tareas.show', $t), 'bi-alarm', $t->aula), true, 0);
                $cant['tareas'] += $faltan->count();
            });

        // Evaluaciones que cierran en las próximas 24 h: alumnos que no rindieron
        Evaluacion::with('aula')->where('visible', true)->whereNotNull('fecha_cierre')->get()
            ->filter(fn ($e) => $e->estaPublicada() && $e->fecha_cierre->between($ahora, $manana) && $e->aula?->estado === 'activa')
            ->each(function ($e) use (&$cant) {
                $rindieron = $e->intentos()->pluck('alumno_id');
                $faltan = $this->alumnos($e->aula)->whereNotIn('id', $rindieron);
                $this->enviar($faltan, $this->datos('recordatorio', 'Evaluación por cerrar',
                    '"' . $e->titulo . '" cierra el ' . $e->fecha_cierre->format('d/m') . ' a las ' . $e->fecha_cierre->format('H:i') . ' y todavía no la rendiste.',
                    route('evaluaciones.show', $e), 'bi-alarm', $e->aula), true, 0);
                $cant['evaluaciones'] += $faltan->count();
            });

        // Abonos: faltan 7 días, falta 1 día, o venció ayer
        Abono::with(['usuario', 'institucion'])->whereNotNull('vence_el')->get()
            ->each(function ($a) use (&$cant) {
                $dias = $a->diasRestantes();
                if (! in_array($dias, [7, 1, -1], true)) {
                    return;
                }

                $texto = $dias === -1
                    ? 'Tu abono venció ayer. Las aulas quedaron en modo solo lectura hasta que lo renueves.'
                    : 'Tu abono vence ' . ($dias === 1 ? 'mañana' : 'en 7 días') . ' (' . $a->vence_el->format('d/m/Y') . '). Renovalo para no interrumpir tus clases.';

                $this->enviar($this->destinatariosAbono($a), $this->datos(
                    'abono', $dias === -1 ? 'Abono vencido' : 'Tu abono está por vencer', $texto, $this->urlAbono($a), 'bi-credit-card'
                ), true, 0);
                $cant['abonos']++;
            });

        return $cant;
    }
}
