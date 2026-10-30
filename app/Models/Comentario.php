<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comentario extends Model
{
    use SoftDeletes;

    protected $table = 'comentarios';

    protected $fillable = ['publicacion_id', 'user_id', 'texto'];

    public function publicacion()
    {
        return $this->belongsTo(Publicacion::class);
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
