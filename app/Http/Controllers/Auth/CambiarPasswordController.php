<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CambiarPasswordController extends Controller
{
    public function create(Request $request)
    {
        return view('auth.cambiar-password', [
            'obligatorio' => (bool) $request->user()->debe_cambiar_password,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $datos = $request->validate([
            'actual' => 'required|string|max:120',
            'nueva'  => ['required', 'confirmed', 'different:actual', Password::min(8)->letters()->numbers()],
        ], [
            'nueva.different' => 'La contraseña nueva tiene que ser distinta de la actual.',
        ], [
            'actual' => 'contraseña actual',
            'nueva'  => 'contraseña nueva',
        ]);

        if (! Hash::check($datos['actual'], $user->password)) {
            return back()->withErrors(['actual' => 'La contraseña actual no es correcta.']);
        }

        if ($user->dni && $datos['nueva'] === $user->dni) {
            return back()->withErrors(['nueva' => 'La contraseña nueva no puede ser tu DNI.']);
        }

        $user->forceFill([
            'password'              => $datos['nueva'],
            'debe_cambiar_password' => false,
        ])->save();

        return redirect()->route('inicio')->with('exito', 'Tu contraseña se actualizó correctamente.');
    }
}
