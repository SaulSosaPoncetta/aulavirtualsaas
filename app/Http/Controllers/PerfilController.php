<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user()->load(['roles']);

        return view('perfil.edit', [
            'user'          => $user,
            'instituciones' => $user->instituciones()->wherePivot('activo', true)->get()
                ->groupBy('id')->map(fn ($g) => [
                    'nombre' => $g->first()->nombre,
                    'roles'  => $g->pluck('pivot.rol')->unique()->values(),
                ])->values(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->merge([
            'dni'   => $request->filled('dni') ? preg_replace('/\D/', '', $request->dni) : null,
            'email' => $request->filled('email') ? mb_strtolower(trim($request->email)) : null,
        ]);

        // Los docentes necesitan email (recuperar contraseña, avisos de pago); los alumnos, email o DNI
        $emailObligatorio = $user->esDocente() || $user->administraInstituciones() || $user->esSuperadmin();

        $datos = $request->validate([
            'name'             => 'required|string|max:80',
            'apellido'         => 'required|string|max:80',
            'email'            => [$emailObligatorio ? 'required' : 'required_without:dni', 'nullable', 'email', 'max:150',
                                   Rule::unique('users', 'email')->ignore($user->id)],
            'dni'              => [$emailObligatorio ? 'nullable' : 'required_without:email', 'nullable', 'digits_between:6,10',
                                   Rule::unique('users', 'dni')->ignore($user->id)],
            'telefono'         => 'nullable|string|max:40',
            'fecha_nacimiento' => 'nullable|date|before:today|after:1920-01-01',
        ], [
            'email.required_without' => 'Tenés que tener al menos email o DNI para poder ingresar.',
            'dni.required_without'   => 'Tenés que tener al menos DNI o email para poder ingresar.',
            'email.unique'           => 'Ese email ya está usado por otra cuenta.',
            'dni.unique'             => 'Ese DNI ya está usado por otra cuenta.',
        ], [
            'name'             => 'nombre',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'telefono'         => 'teléfono',
        ]);

        $cambioEmail = $datos['email'] !== $user->email;

        $user->fill([
            'name'             => trim($datos['name']),
            'apellido'         => trim($datos['apellido']),
            'email'            => $datos['email'],
            'dni'              => $datos['dni'],
            'telefono'         => $datos['telefono'] ?? null,
            'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
        ]);

        // Email nuevo: hay que volver a confirmarlo
        if ($cambioEmail) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($cambioEmail && $user->email) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                report($e);
            }

            return redirect()->route('verification.notice')
                ->with('exito', "Guardamos tus datos. Te enviamos un enlace a {$user->email} para confirmar el email nuevo.");
        }

        return back()->with('exito', 'Tus datos se actualizaron.');
    }

    public function subirFoto(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:min_width=80,min_height=80',
        ], [
            'avatar.max'        => 'La foto puede pesar como máximo 2 MB.',
            'avatar.dimensions' => 'La foto tiene que medir al menos 80×80 píxeles.',
        ], ['avatar' => 'foto']);

        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update(['avatar' => $request->file('avatar')->store('avatars', 'public')]);

        return back()->with('exito', 'Foto actualizada.');
    }

    public function quitarFoto(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return back()->with('exito', 'Foto eliminada.');
    }
}
