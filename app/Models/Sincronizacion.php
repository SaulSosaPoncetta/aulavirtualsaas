<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sincronizacion extends Model
{
    protected $table = 'sincronizaciones';

    protected $fillable = [
        'sistema',
        'tipo',
        'direccion',
        'aula_id',
        'user_id',
        'estado',
        'codigo_http',
        'mensaje',
        'payload',
        'intentos',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
