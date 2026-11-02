<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InvitacionAula extends Model
{
    protected $table = 'aula_invitaciones';

    public const DIAS_VIGENCIA = 14;

    protected $fillable = ['aula_id', 'email', 'token', 'invitado_por', 'estado', 'vence_at', 'respondida_at'];

    protected function casts(): array
    {
        return [
            'vence_at'      => 'datetime',
            'respondida_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (InvitacionAula $i) {
            $i->token    ??= Str::random(48);
            $i->vence_at ??= Carbon::now('America/Argentina/Buenos_Aires')->addDays(self::DIAS_VIGENCIA);
            $i->email      = mb_strtolower(trim($i->email));
        });
    }

    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }

    public function invitador()
    {
        return $this->belongsTo(User::class, 'invitado_por');
    }

    public function estaVigente(): bool
    {
        return $this->estado === 'pendiente' && $this->vence_at->gte(Carbon::now('America/Argentina/Buenos_Aires'));
    }

    public function scopeVigentes($q)
    {
        return $q->where('estado', 'pendiente')->where('vence_at', '>=', Carbon::now('America/Argentina/Buenos_Aires'));
    }
}
