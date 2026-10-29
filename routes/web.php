<?php

use App\Http\Controllers\ArchivoController;
use App\Http\Controllers\AulaController;
use App\Http\Controllers\ClaseController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\RecursoController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\UnidadController;
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

    // Unidades del aula
    Route::post('/aulas/{aula}/unidades', [UnidadController::class, 'store'])->name('unidades.store');
    Route::put('/unidades/{unidad}', [UnidadController::class, 'update'])->name('unidades.update');
    Route::delete('/unidades/{unidad}', [UnidadController::class, 'destroy'])->name('unidades.destroy');
    Route::patch('/unidades/{unidad}/mover/{direccion}', [UnidadController::class, 'mover'])
        ->whereIn('direccion', ['arriba', 'abajo'])->name('unidades.mover');

    // Clases y material
    Route::get('/aulas/{aula}/clases/crear', [ClaseController::class, 'create'])->name('clases.create');
    Route::post('/aulas/{aula}/clases', [ClaseController::class, 'store'])->name('clases.store');
    Route::get('/clases/{clase}', [ClaseController::class, 'show'])->name('clases.show');
    Route::get('/clases/{clase}/editar', [ClaseController::class, 'edit'])->name('clases.edit');
    Route::put('/clases/{clase}', [ClaseController::class, 'update'])->name('clases.update');
    Route::delete('/clases/{clase}', [ClaseController::class, 'destroy'])->name('clases.destroy');
    Route::get('/recursos/{recurso}/descargar', [RecursoController::class, 'descargar'])->name('recursos.descargar');

    // Tareas
    Route::get('/aulas/{aula}/tareas/crear', [TareaController::class, 'create'])->name('tareas.create');
    Route::post('/aulas/{aula}/tareas', [TareaController::class, 'store'])->name('tareas.store');
    Route::get('/tareas/{tarea}', [TareaController::class, 'show'])->name('tareas.show');
    Route::get('/tareas/{tarea}/editar', [TareaController::class, 'edit'])->name('tareas.edit');
    Route::put('/tareas/{tarea}', [TareaController::class, 'update'])->name('tareas.update');
    Route::delete('/tareas/{tarea}', [TareaController::class, 'destroy'])->name('tareas.destroy');

    // Entregas
    Route::post('/tareas/{tarea}/entrega', [EntregaController::class, 'guardar'])->middleware('throttle:20,1')->name('entregas.guardar');
    Route::get('/entregas/{entrega}', [EntregaController::class, 'show'])->name('entregas.show');
    Route::patch('/entregas/{entrega}/corregir', [EntregaController::class, 'corregir'])->name('entregas.corregir');

    // Archivos de tareas y entregas
    Route::get('/archivos/{archivo}/descargar', [ArchivoController::class, 'descargar'])->name('archivos.descargar');
});
