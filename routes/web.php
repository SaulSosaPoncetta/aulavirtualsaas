<?php

use App\Http\Controllers\Auth\CambiarPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RecuperarPasswordController;
use App\Http\Controllers\Auth\RegistroController;
use App\Http\Middleware\RequiereCambioPassword;
use Illuminate\Support\Facades\Route;

// ===================== Invitados =====================
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:20,1');

    Route::get('/registro', [RegistroController::class, 'create'])->name('registro');
    Route::post('/registro', [RegistroController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/recuperar-password', [RecuperarPasswordController::class, 'solicitar'])->name('password.request');
    Route::post('/recuperar-password', [RecuperarPasswordController::class, 'enviar'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/restablecer-password/{token}', [RecuperarPasswordController::class, 'formulario'])->name('password.reset');
    Route::post('/restablecer-password', [RecuperarPasswordController::class, 'restablecer'])->name('password.store');
});

// ===================== Autenticados =====================
Route::middleware(['auth', RequiereCambioPassword::class])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/cambiar-password', [CambiarPasswordController::class, 'create'])->name('password.cambiar');
    Route::post('/cambiar-password', [CambiarPasswordController::class, 'store'])->name('password.actualizar');

    Route::get('/', function () {
        return view('inicio');
    })->name('inicio');
});
