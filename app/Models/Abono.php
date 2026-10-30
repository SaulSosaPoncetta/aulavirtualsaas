<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Abono extends Model
{
    protected $table = 'abonos';

    protected $fillable = ['user_id', 'institucion_id', 'tipo', 'cupo_docentes', 'vence_el'];

    protected function casts(): array
    {
        return [
            'vence_el'      => 'date',
            'cupo_docentes' => 'integer',
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

    public function pagos()
    {
        return $this->hasMany(Pago::class)->orderByDesc('created_at');
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

    // ===================== Aplicar un pago aprobado =====================

    public function aplicarPago(Pago $pago, ?int $aprobadoPor = null): void
    {
        DB::transaction(function () use ($pago, $aprobadoPor) {
            $pago->refresh();
            if ($pago->estado === 'aprobado') {
                return; // ya aplicado (evita duplicar con el webhook)
            }

            if ($pago->concepto === 'renovacion') {
                $base = $this->estaActivo() ? $this->vence_el->copy() : static::hoy()->subDay();
                $this->vence_el = $base->addMonthsNoOverflow($pago->meses);
            }

            if ($this->tipo === 'institucional' && $pago->cupo_docentes) {
                $this->cupo_docentes = $pago->cupo_docentes;
            }

            $this->save();

            $pago->update([
                'estado'         => 'aprobado',
                'aprobado_at'    => Carbon::now('America/Argentina/Buenos_Aires'),
                'registrado_por' => $aprobadoPor,
            ]);
        });
    }
}
