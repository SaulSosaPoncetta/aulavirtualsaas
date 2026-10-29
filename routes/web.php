<?php

use App\Http\Controllers\AulaController;
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

    // Mis aulas (pantalla de inicio)
    Route::get('/', [AulaController::class, 'index'])->name('inicio');

    Route::post('/aulas/unirse', [AulaController::class, 'unirse'])->middleware('throttle:20,1')->name('aulas.unirse');
    Route::resource('aulas', AulaController::class)->except('index');
    Route::patch('/aulas/{aula}/archivar', [AulaController::class, 'archivar'])->name('aulas.archivar');
    Route::patch('/aulas/{aula}/codigo/regenerar', [AulaController::class, 'regenerarCodigo'])->name('aulas.codigo.regenerar');
    Route::patch('/aulas/{aula}/codigo/alternar', [AulaController::class, 'alternarCodigo'])->name('aulas.codigo.alternar');
    Route::patch('/aulas/{aula}/miembros/{user}/baja', [AulaController::class, 'bajaMiembro'])->name('aulas.miembros.baja');
    Route::post('/aulas/{aula}/salir', [AulaController::class, 'salir'])->name('aulas.salir');
});
