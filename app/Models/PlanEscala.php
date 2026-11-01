<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanEscala extends Model
{
    protected $table = 'plan_escalas';
    protected $primaryKey = 'id_escala';

    protected $fillable = ['id_plan', 'desde', 'hasta', 'precio'];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'id_plan', 'id_plan');
    }
}
