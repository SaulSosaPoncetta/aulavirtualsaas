<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Publicacion extends Model
{
    use SoftDeletes;

    protected $table = 'publicaciones';

    protected $fillable = ['aula_id', 'user_id', 'texto', 'fijada'];

    protected function casts(): array
    {
        return [
            'fijada' => 'boolean',
        ];
    }

    public function aula()
    {
        return $this->belongsTo(Aula::class);
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comentarios()
    {
        return $this->hasMany(Comentario::class)->orderBy('created_at');
    }

    /** El autor o un docente del aula. */
    public function puedeBorrar(User $user, bool $esDocente): bool
    {
        return $esDocente || $this->user_id == $user->id;
    }
}
