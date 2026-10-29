<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Archivo extends Model
{
    protected $table = 'archivos';

    protected $fillable = ['archivable_type', 'archivable_id', 'path', 'nombre', 'mime', 'tamano', 'user_id'];

    protected static function booted(): void
    {
        // Al borrar el registro se borra también el archivo físico
        static::deleting(function (Archivo $a) {
            Storage::disk('local')->delete($a->path);
        });
    }

    public function archivable()
    {
        return $this->morphTo();
    }

    /** Guarda un archivo subido y lo asocia a una tarea o entrega. */
    public static function guardar(UploadedFile $archivo, Model $duenio, string $carpeta, ?int $userId = null): self
    {
        return $duenio->archivos()->create([
            'path'    => $archivo->store($carpeta, 'local'),
            'nombre'  => $archivo->getClientOriginalName(),
            'mime'    => $archivo->getClientMimeType(),
            'tamano'  => $archivo->getSize(),
            'user_id' => $userId,
        ]);
    }

    public function getSeVeEnLineaAttribute(): bool
    {
        return (bool) preg_match('~^(application/pdf|image/)~', (string) $this->mime);
    }

    public function getTamanoLegibleAttribute(): string
    {
        $b = (int) $this->tamano;

        return match (true) {
            $b >= 1048576 => number_format($b / 1048576, 1, ',', '.') . ' MB',
            $b >= 1024    => number_format($b / 1024, 0, ',', '.') . ' KB',
            default       => $b . ' B',
        };
    }

    public function getIconoAttribute(): string
    {
        $ext = strtolower(pathinfo((string) $this->nombre, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf'                               => 'bi-file-earmark-pdf-fill text-danger',
            'doc', 'docx', 'odt'                => 'bi-file-earmark-word-fill text-primary',
            'xls', 'xlsx', 'ods', 'csv'         => 'bi-file-earmark-excel-fill text-success',
            'ppt', 'pptx', 'odp'                => 'bi-file-earmark-ppt-fill text-warning',
            'jpg', 'jpeg', 'png', 'gif', 'webp' => 'bi-file-earmark-image-fill text-info',
            'zip', 'rar', '7z'                  => 'bi-file-earmark-zip-fill text-secondary',
            'mp3', 'wav', 'ogg', 'm4a'          => 'bi-file-earmark-music-fill text-secondary',
            'mp4', 'webm', 'mov'                => 'bi-file-earmark-play-fill text-danger',
            default                             => 'bi-file-earmark-fill text-secondary',
        };
    }
}
