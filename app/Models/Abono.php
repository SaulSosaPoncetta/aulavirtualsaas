<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Abono extends Model
{
    protected $table = 'abonos';

    protected $fillable = ['user_id', 'institucion_id', 'tipo', 'cupo_docentes', 'vence_el', 'panel_estado', 'panel_plan', 'panel_sync_at'];

    protected function casts(): array
    {
        return [
            'vence_el'      => 'date',
            'cupo_docentes' => 'integer',
            'panel_sync_at' => 'datetime',
        ];
    }

    // ===================== Relaciones =====================

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function institucion()
    {
        return $this->belongsTo(Institucion::class);
    }

    // ===================== Obtener / crear =====================

    public static function deUsuario(User $user): self
    {
        return static::firstOrCreate(['user_id' => $user->id], ['tipo' => 'unipersonal']);
    }

    public static function deInstitucion(Institucion $institucion): self
    {
        return static::firstOrCreate(['institucion_id' => $institucion->id], ['tipo' => 'institucional', 'cupo_docentes' => 0]);
    }

    // ===================== Estado =====================

    public static function hoy(): Carbon
    {
        return Carbon::now('America/Argentina/Buenos_Aires')->startOfDay();
    }

    public function estaActivo(): bool
    {
        return $this->vence_el !== null && $this->vence_el->copy()->startOfDay()->gte(static::hoy());
    }

    public function diasRestantes(): ?int
    {
        return $this->vence_el ? (int) static::hoy()->diffInDays($this->vence_el->copy()->startOfDay(), false) : null;
    }

    public function getEstadoAttribute(): string
    {
        if ($this->vence_el === null) {
            return 'sin_abono';
        }

        if (! $this->estaActivo()) {
            return 'vencido';
        }

        return $this->diasRestantes() <= 7 ? 'por_vencer' : 'activo';
    }

    public function getEstadoLabelAttribute(): string
    {
        return [
            'sin_abono'  => 'Sin abono',
            'vencido'    => 'Vencido',
            'por_vencer' => 'Por vencer',
            'activo'     => 'Activo',
        ][$this->estado];
    }

    public function getEstadoColorAttribute(): string
    {
        return ['sin_abono' => 'secondary', 'vencido' => 'danger', 'por_vencer' => 'warning', 'activo' => 'success'][$this->estado];
    }

    public function getNombreAttribute(): string
    {
        return $this->tipo === 'institucional'
            ? ($this->institucion?->nombre ?? 'Institución')
            : ($this->usuario?->nombre_completo ?? 'Docente');
    }

    // ===================== Cupo institucional =====================

    /** Docentes que ocupan lugar: miembros con rol "docente" activos (los administradores que no dan clases no ocupan). */
    public function docentesUsados(): int
    {
        if (! $this->institucion_id) {
            return 0;
        }

        return DB::table('institucion_user')
            ->where('institucion_id', $this->institucion_id)
            ->where('rol', 'docente')->where('activo', true)
            ->distinct()->count('user_id');
    }

    public function hayCupo(): bool
    {
        return $this->estaActivo() && $this->docentesUsados() < (int) $this->cupo_docentes;
    }

    /** Meses que faltan para el vencimiento (redondeado para arriba, mínimo 1). */
    public function mesesRestantes(): int
    {
        $dias = $this->diasRestantes();

        return $dias === null || $dias <= 0 ? 1 : max(1, (int) ceil(($dias + 1) / 30));
    }

    // ===================== Aviso del Panel de Gestión =====================

    /**
     * Aplica el abono que informa el Panel (fuente de verdad de pagos y vencimientos).
     * El sistema conserva su copia local de vencimiento y cupo, así sigue funcionando
     * aunque el Panel no responda.
     *
     * @param array $d  payload del Panel: estado, plan, cantidad, fecha_vencimiento (Y-m-d|null), enviado_en
     */
    public function aplicarDesdePanel(array $d): void
    {
        $estado = (string) ($d['estado'] ?? '');

        DB::transaction(function () use ($d, $estado) {
            $this->panel_estado = $estado;
            $this->panel_plan   = $d['plan'] ?? $this->panel_plan;
            $this->panel_sync_at = ! empty($d['enviado_en']) ? Carbon::parse($d['enviado_en']) : Carbon::now();

            // "pendiente": todavía no pagó, no se toca vencimiento ni cupo.
            if ($estado !== 'pendiente') {
                $fecha = ! empty($d['fecha_vencimiento'])
                    ? Carbon::parse($d['fecha_vencimiento'], 'America/Argentina/Buenos_Aires')->startOfDay()
                    : null;

                if ($estado === 'activa') {
                    // Sin fecha = sin vencimiento. Los días de gracia del Panel se respetan.
                    $this->vence_el = $fecha
                        ? $fecha->addDays((int) config('panel.gracia_dias'))
                        : Carbon::create(2099, 12, 31);
                } else {
                    // vencida / suspendida / cancelada: el acceso queda cortado desde ayer como mucho.
                    $ayer = static::hoy()->subDay();
                    $this->vence_el = ($fecha && $fecha->lt($ayer)) ? $fecha : $ayer;
                }

                if ($this->tipo === 'institucional' && isset($d['cantidad'])) {
                    $this->cupo_docentes = max(0, (int) $d['cantidad']);
                }
            }

            $this->save();
        });
    }
}
