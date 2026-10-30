<?php

use App\Http\Controllers\AbonoController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\ArchivoController;
use App\Http\Controllers\AulaController;
use App\Http\Controllers\ClaseController;
use App\Http\Controllers\EntregaController;
use App\Http\Controllers\EvaluacionController;
use App\Http\Controllers\ImportarAlumnosController;
use App\Http\Controllers\InstitucionController;
use App\Http\Controllers\IntentoController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LibretaController;
use App\Http\Controllers\MuroController;
use App\Http\Controllers\PreguntaController;
use App\Http\Controllers\RecursoController;
use App\Http\Controllers\TareaController;
use App\Http\Controllers\UnidadController;
use App\Http\Controllers\Auth\CambiarPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RecuperarPasswordController;
use App\Http\Controllers\Auth\RegistroController;
use App\Http\Middleware\RequiereCambioPassword;
use App\Http\Middleware\SoloSuperadmin;
use App\Http\Middleware\VerificarAbono;
use Illuminate\Support\Facades\Route;

// ===================== Página pública =====================
Route::get('/', [LandingController::class, 'index'])->name('landing');

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
Route::middleware(['auth', RequiereCambioPassword::class, VerificarAbono::class])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/cambiar-password', [CambiarPasswordController::class, 'create'])->name('password.cambiar');
    Route::post('/cambiar-password', [CambiarPasswordController::class, 'store'])->name('password.actualizar');

    // Mis aulas (pantalla de inicio una vez logueado)
    Route::get('/aulas', [AulaController::class, 'index'])->name('inicio');

    Route::post('/aulas/unirse', [AulaController::class, 'unirse'])->middleware('throttle:20,1')->name('aulas.unirse');
    Route::resource('aulas', AulaController::class)->except('index');
    Route::patch('/aulas/{aula}/archivar', [AulaController::class, 'archivar'])->name('aulas.archivar');
    Route::patch('/aulas/{aula}/codigo/regenerar', [AulaController::class, 'regenerarCodigo'])->name('aulas.codigo.regenerar');
    Route::patch('/aulas/{aula}/codigo/alternar', [AulaController::class, 'alternarCodigo'])->name('aulas.codigo.alternar');
    Route::patch('/aulas/{aula}/miembros/{user}/baja', [AulaController::class, 'bajaMiembro'])->name('aulas.miembros.baja');
    Route::post('/aulas/{aula}/salir', [AulaController::class, 'salir'])->name('aulas.salir');
    Route::post('/aulas/{aula}/alumnos', [AulaController::class, 'agregarAlumnos'])->name('aulas.alumnos.agregar');

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

    // Evaluaciones
    Route::get('/aulas/{aula}/evaluaciones/crear', [EvaluacionController::class, 'create'])->name('evaluaciones.create');
    Route::post('/aulas/{aula}/evaluaciones', [EvaluacionController::class, 'store'])->name('evaluaciones.store');
    Route::get('/evaluaciones/{evaluacion}', [EvaluacionController::class, 'show'])->name('evaluaciones.show');
    Route::get('/evaluaciones/{evaluacion}/editar', [EvaluacionController::class, 'edit'])->name('evaluaciones.edit');
    Route::put('/evaluaciones/{evaluacion}', [EvaluacionController::class, 'update'])->name('evaluaciones.update');
    Route::delete('/evaluaciones/{evaluacion}', [EvaluacionController::class, 'destroy'])->name('evaluaciones.destroy');
    Route::get('/evaluaciones/{evaluacion}/preguntas', [EvaluacionController::class, 'preguntas'])->name('evaluaciones.preguntas');

    // Preguntas
    Route::post('/evaluaciones/{evaluacion}/preguntas', [PreguntaController::class, 'store'])->name('preguntas.store');
    Route::put('/preguntas/{pregunta}', [PreguntaController::class, 'update'])->name('preguntas.update');
    Route::delete('/preguntas/{pregunta}', [PreguntaController::class, 'destroy'])->name('preguntas.destroy');
    Route::patch('/preguntas/{pregunta}/mover/{direccion}', [PreguntaController::class, 'mover'])
        ->whereIn('direccion', ['arriba', 'abajo'])->name('preguntas.mover');

    // Intentos (rendir y corregir)
    Route::post('/evaluaciones/{evaluacion}/comenzar', [IntentoController::class, 'comenzar'])->name('intentos.comenzar');
    Route::get('/intentos/{intento}/rendir', [IntentoController::class, 'rendir'])->name('intentos.rendir');
    Route::post('/intentos/{intento}/finalizar', [IntentoController::class, 'finalizar'])->name('intentos.finalizar');
    Route::get('/intentos/{intento}', [IntentoController::class, 'show'])->name('intentos.show');
    Route::patch('/intentos/{intento}/calificar', [IntentoController::class, 'calificar'])->name('intentos.calificar');

    // Libreta de calificaciones
    Route::get('/aulas/{aula}/calificaciones', [LibretaController::class, 'index'])->name('libreta.index');
    Route::get('/aulas/{aula}/calificaciones/excel', [LibretaController::class, 'exportar'])->name('libreta.exportar');

    // Muro del aula
    Route::get('/aulas/{aula}/muro', [MuroController::class, 'index'])->name('muro.index');
    Route::post('/aulas/{aula}/muro', [MuroController::class, 'store'])->middleware('throttle:30,1')->name('muro.store');
    Route::put('/publicaciones/{publicacion}', [MuroController::class, 'update'])->name('muro.update');
    Route::delete('/publicaciones/{publicacion}', [MuroController::class, 'destroy'])->name('muro.destroy');
    Route::patch('/publicaciones/{publicacion}/fijar', [MuroController::class, 'fijar'])->name('muro.fijar');
    Route::post('/publicaciones/{publicacion}/comentarios', [MuroController::class, 'comentar'])->middleware('throttle:60,1')->name('muro.comentar');
    Route::delete('/comentarios/{comentario}', [MuroController::class, 'borrarComentario'])->name('muro.comentarios.destroy');

    // Instituciones
    Route::get('/instituciones', [InstitucionController::class, 'index'])->name('instituciones.index');
    Route::get('/instituciones/crear', [InstitucionController::class, 'create'])->name('instituciones.create');
    Route::post('/instituciones', [InstitucionController::class, 'store'])->name('instituciones.store');
    Route::post('/instituciones/unirse', [InstitucionController::class, 'unirse'])->middleware('throttle:20,1')->name('instituciones.unirse');
    Route::get('/instituciones/{institucion}', [InstitucionController::class, 'show'])->name('instituciones.show');
    Route::get('/instituciones/{institucion}/editar', [InstitucionController::class, 'edit'])->name('instituciones.edit');
    Route::put('/instituciones/{institucion}', [InstitucionController::class, 'update'])->name('instituciones.update');
    Route::patch('/instituciones/{institucion}/codigo', [InstitucionController::class, 'regenerarCodigo'])->name('instituciones.codigo.regenerar');
    Route::post('/instituciones/{institucion}/salir', [InstitucionController::class, 'salir'])->name('instituciones.salir');
    Route::patch('/instituciones/{institucion}/admin/{user}', [InstitucionController::class, 'alternarAdmin'])->name('instituciones.admin.alternar');
    Route::delete('/instituciones/{institucion}/miembros/{user}/{rol}', [InstitucionController::class, 'quitarMiembro'])
        ->whereIn('rol', ['docente', 'alumno'])->name('instituciones.miembros.quitar');
    Route::patch('/instituciones/{institucion}/alumnos/{user}/password', [InstitucionController::class, 'restablecerPassword'])->name('instituciones.alumnos.password');

    // Carga masiva de alumnos
    Route::get('/instituciones/{institucion}/importar', [ImportarAlumnosController::class, 'form'])->name('instituciones.importar');
    Route::get('/instituciones/{institucion}/importar/plantilla', [ImportarAlumnosController::class, 'plantilla'])->name('instituciones.importar.plantilla');
    Route::post('/instituciones/{institucion}/importar', [ImportarAlumnosController::class, 'procesar'])->name('instituciones.importar.procesar');

    // Abonos
    Route::get('/mi-abono', [AbonoController::class, 'mio'])->name('abonos.mio');
    Route::post('/mi-abono', [AbonoController::class, 'pagarMio'])->middleware('throttle:10,1')->name('abonos.mio.pagar');
    Route::get('/instituciones/{institucion}/abono', [AbonoController::class, 'institucion'])->name('abonos.institucion');
    Route::get('/instituciones/{institucion}/abono/cotizar', [AbonoController::class, 'cotizar'])->name('abonos.institucion.cotizar');
    Route::post('/instituciones/{institucion}/abono', [AbonoController::class, 'pagarInstitucion'])->middleware('throttle:10,1')->name('abonos.institucion.pagar');
    Route::patch('/pagos/{pago}/cancelar', [AbonoController::class, 'cancelar'])->name('abonos.cancelar');
    Route::get('/pagos/{pago}/comprobante', [AbonoController::class, 'comprobante'])->name('abonos.comprobante');

    // ===================== Administración (superadmin) =====================
    Route::middleware(SoloSuperadmin::class)->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [Admin\PanelController::class, 'index'])->name('panel');

        Route::get('/pagos', [Admin\PagoController::class, 'index'])->name('pagos.index');
        Route::get('/pagos/registrar', [Admin\PagoController::class, 'crear'])->name('pagos.crear');
        Route::post('/pagos', [Admin\PagoController::class, 'guardar'])->name('pagos.guardar');
        Route::patch('/pagos/{pago}/aprobar', [Admin\PagoController::class, 'aprobar'])->name('pagos.aprobar');
        Route::patch('/pagos/{pago}/rechazar', [Admin\PagoController::class, 'rechazar'])->name('pagos.rechazar');

        Route::get('/abonos', [Admin\SuscripcionController::class, 'index'])->name('abonos.index');
        Route::patch('/abonos/{abono}', [Admin\SuscripcionController::class, 'ajustar'])->name('abonos.ajustar');

        Route::get('/precios', [Admin\PlanController::class, 'index'])->name('planes.index');
        Route::post('/precios', [Admin\PlanController::class, 'store'])->name('planes.store');
        Route::put('/precios/{plan}', [Admin\PlanController::class, 'update'])->name('planes.update');
        Route::delete('/precios/{plan}', [Admin\PlanController::class, 'destroy'])->name('planes.destroy');

        Route::get('/usuarios', [Admin\UsuarioController::class, 'index'])->name('usuarios.index');
        Route::patch('/usuarios/{user}/activo', [Admin\UsuarioController::class, 'alternarActivo'])->name('usuarios.activo');
        Route::patch('/usuarios/{user}/password', [Admin\UsuarioController::class, 'restablecerPassword'])->name('usuarios.password');
    });

    // Archivos de tareas y entregas
    Route::get('/archivos/{archivo}/descargar', [ArchivoController::class, 'descargar'])->name('archivos.descargar');
});
