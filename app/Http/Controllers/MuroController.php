<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\Comentario;
use App\Models\Publicacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MuroController extends Controller
{
    public function index(Request $request, Aula $aula)
    {
        $user = $request->user();
        abort_unless($aula->puedeVer($user), 403);

        $esDocente = $aula->puedeEditar($user);

        $publicaciones = $aula->publicaciones()
            ->with(['autor', 'comentarios.autor'])
            ->orderByDesc('fijada')
            ->orderByDesc('created_at')
            ->paginate(15);

        // Marcar el muro como visto (para el contador de novedades)
        DB::table('aula_user')
            ->where('aula_id', $aula->id)->where('user_id', $user->id)
            ->update(['muro_visto_at' => Carbon::now('America/Argentina/Buenos_Aires')]);

        $idsDocentes = DB::table('aula_user')
            ->where('aula_id', $aula->id)->where('rol', 'docente')
            ->pluck('user_id')->map(fn ($id) => (int) $id)->push((int) $aula->docente_id)->unique()->values()->all();

        return view('muro.index', [
            'aula'          => $aula,
            'idsDocentes'   => $idsDocentes,
            'publicaciones' => $publicaciones,
            'esDocente'     => $esDocente,
            'puedePublicar' => $esDocente || ($aula->alumnos_publican && $aula->esAlumno($user)),
            'puedeComentar' => $esDocente || ($aula->alumnos_comentan && $aula->esAlumno($user)),
        ]);
    }

    public function store(Request $request, Aula $aula)
    {
        $user = $request->user();
        abort_unless($aula->puedeEditar($user) || ($aula->alumnos_publican && $aula->esAlumno($user)), 403);
        abort_if($aula->estado === 'archivada', 422, 'El aula está archivada.');

        $datos = $this->validarTexto($request, 5000);

        $aula->publicaciones()->create([
            'user_id' => $user->id,
            'texto'   => $datos['texto'],
            'fijada'  => $aula->puedeEditar($user) && $request->boolean('fijada'),
        ]);

        return redirect()->route('muro.index', $aula)->with('exito', 'Publicado en el muro.');
    }

    public function update(Request $request, Publicacion $publicacion)
    {
        abort_unless($publicacion->user_id == $request->user()->id, 403, 'Solo el autor puede editar la publicación.');

        $datos = $this->validarTexto($request, 5000);
        $publicacion->update(['texto' => $datos['texto']]);

        return redirect()->to(route('muro.index', $publicacion->aula_id) . '#pub' . $publicacion->id)
            ->with('exito', 'Publicación editada.');
    }

    public function destroy(Request $request, Publicacion $publicacion)
    {
        $aula = $publicacion->aula;
        abort_unless($publicacion->puedeBorrar($request->user(), $aula->puedeEditar($request->user())), 403);

        $publicacion->delete();

        return redirect()->route('muro.index', $aula)->with('exito', 'Publicación eliminada.');
    }

    public function fijar(Request $request, Publicacion $publicacion)
    {
        abort_unless($publicacion->aula->puedeEditar($request->user()), 403);

        $publicacion->update(['fijada' => ! $publicacion->fijada]);

        return redirect()->route('muro.index', $publicacion->aula_id)
            ->with('exito', $publicacion->fijada ? 'Publicación fijada arriba.' : 'Publicación desfijada.');
    }

    public function comentar(Request $request, Publicacion $publicacion)
    {
        $user = $request->user();
        $aula = $publicacion->aula;

        abort_unless($aula->puedeEditar($user) || ($aula->alumnos_comentan && $aula->esAlumno($user)), 403);
        abort_if($aula->estado === 'archivada', 422, 'El aula está archivada.');

        $datos = $this->validarTexto($request, 2000);

        $publicacion->comentarios()->create(['user_id' => $user->id, 'texto' => $datos['texto']]);

        return redirect()->to(route('muro.index', $aula) . '#pub' . $publicacion->id);
    }

    public function borrarComentario(Request $request, Comentario $comentario)
    {
        $user = $request->user();
        $aula = $comentario->publicacion->aula;

        abort_unless($comentario->user_id == $user->id || $aula->puedeEditar($user), 403);

        $pubId = $comentario->publicacion_id;
        $comentario->delete();

        return redirect()->to(route('muro.index', $aula) . '#pub' . $pubId)->with('exito', 'Comentario eliminado.');
    }

    protected function validarTexto(Request $request, int $max): array
    {
        $request->merge(['texto' => trim((string) $request->texto)]);

        return $request->validate(
            ['texto' => "required|string|max:{$max}"],
            ['texto.required' => 'Escribí algo antes de publicar.'],
            ['texto' => 'texto']
        );
    }
}
