<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Recurso extends Model
{
    protected $table = 'recursos';

    protected $fillable = [
        'clase_id', 'tipo', 'titulo', 'url',
        'archivo_path', 'archivo_nombre', 'archivo_mime', 'archivo_tamano', 'orden',
    ];

    protected static function booted(): void
    {
        // Al borrar el recurso se borra también el archivo físico
        static::deleting(function (Recurso $r) {
            if ($r->archivo_path) {
                Storage::disk('local')->delete($r->archivo_path);
            }
        });
    }

    public function clase()
    {
        return $this->belongsTo(Clase::class);
    }

    public function getNombreVisibleAttribute(): string
    {
        return $this->titulo ?: ($this->archivo_nombre ?: ($this->url ?? 'Recurso'));
    }

    /** Id de YouTube si la URL es de YouTube (para mostrar el video embebido). */
    public function getYoutubeIdAttribute(): ?string
    {
        if (! $this->url) {
            return null;
        }

        $patron = '~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~';

        return preg_match($patron, $this->url, $m) ? $m[1] : null;
    }

    public function getTamanoLegibleAttribute(): string
    {
        $b = (int) $this->archivo_tamano;

        return match (true) {
            $b >= 1048576 => number_format($b / 1048576, 1, ',', '.') . ' MB',
            $b >= 1024    => number_format($b / 1024, 0, ',', '.') . ' KB',
            default       => $b . ' B',
        };
    }

    public function getIconoAttribute(): string
    {
        if ($this->tipo === 'video') {
            return 'bi-play-btn-fill text-danger';
        }

        if ($this->tipo === 'enlace') {
            return 'bi-link-45deg text-primary';
        }

        $ext = strtolower(pathinfo((string) $this->archivo_nombre, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf'                         => 'bi-file-earmark-pdf-fill text-danger',
            'doc', 'docx', 'odt'          => 'bi-file-earmark-word-fill text-primary',
            'xls', 'xlsx', 'ods', 'csv'   => 'bi-file-earmark-excel-fill text-success',
            'ppt', 'pptx', 'odp'          => 'bi-file-earmark-ppt-fill text-warning',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'bi-file-earmark-image-fill text-info',
            'zip', 'rar', '7z'            => 'bi-file-earmark-zip-fill text-secondary',
            'mp3', 'wav', 'ogg', 'm4a'    => 'bi-file-earmark-music-fill text-secondary',
            'mp4', 'webm', 'mov'          => 'bi-file-earmark-play-fill text-danger',
            default                       => 'bi-file-earmark-fill text-secondary',
        };
    }
}
