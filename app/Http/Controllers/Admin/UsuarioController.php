<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar'));
        $rol    = $request->query('rol');
        $dni    = preg_replace('/\D/', '', $buscar);

        $usuarios = User::with(['roles', 'abono'])
            ->withCount(['aulasTitular', 'aulasComoAlumno'])
            ->when($rol, fn ($q) => $q->role($rol))
            ->when($buscar !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('email', 'like', "%{$buscar}%")->orWhere('apellido', 'like', "%{$buscar}%")->orWhere('name', 'like', "%{$buscar}%")
                ->when($dni !== '', fn ($x) => $x->orWhere('dni', 'like', "%{$dni}%"))))
            ->orderByDesc('created_at')
            ->paginate(30)->withQueryString();

        return view('admin.usuarios', compact('usuarios', 'buscar', 'rol'));
    }

    public function alternarActivo(Request $request, User $user)
    {
        abort_if($user->id === $request->user()->id, 422, 'No podés desactivarte a vos mismo.');

        $user->update(['activo' => ! $user->activo]);

        return back()->with('exito', $user->activo ? "{$user->nombre_completo} fue reactivado." : "{$user->nombre_completo} fue desactivado: ya no puede ingresar.");
    }

    public function restablecerPassword(Request $request, User $user)
    {
        if (! $user->dni) {
            return back()->with('error', 'El usuario no tiene DNI cargado: no se puede restablecer al DNI.');
        }

        $user->forceFill(['password' => $user->dni, 'debe_cambiar_password' => true])->save();

        return back()->with('exito', "La contraseña de {$user->nombre_completo} ahora es su DNI (con cambio obligatorio).");
    }
}
