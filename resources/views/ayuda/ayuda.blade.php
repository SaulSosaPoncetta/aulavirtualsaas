@extends('layouts.app')

@section('titulo', 'Ayuda')

@push('estilos')
<style>
    .ayuda-indice { position: sticky; top: 5rem; }
    .ayuda-seccion { scroll-margin-top: 5rem; }
    .ayuda-seccion h3 { font-size: 1.05rem; margin-top: 1.25rem; }
    .ayuda-seccion table { font-size: .92rem; }
    .ayuda-seccion .card-body > :last-child { margin-bottom: 0; }
    mark.ayuda-hl { padding: 0 .1em; }
</style>
@endpush

@section('contenido')
    @php
        $u = auth()->user();
        $esSuper   = $u->esSuperadmin();
        $esDocente = $u->esDocente() || $u->administraInstituciones() || $esSuper;
        $esAlumno  = $u->esAlumno();
        $esInst    = $u->administraInstituciones() || $u->esDocente() || $esSuper;

        $secciones = array_filter([
            'intro'        => ['Introducción', 'bi-info-circle', true],
            'primeros'     => ['Primeros pasos', 'bi-door-open', true],
            'aulas'        => ['Crear y administrar aulas', 'bi-easel', $esDocente],
            'contenidos'   => ['Contenidos', 'bi-journal-text', $esDocente],
            'tareas'       => ['Tareas y entregas', 'bi-clipboard-check', $esDocente],
            'evaluaciones' => ['Evaluaciones', 'bi-ui-checks', $esDocente],
            'muro'         => ['Muro del aula', 'bi-chat-square-text', true],
            'calificaciones' => ['Calificaciones', 'bi-table', $esDocente],
            'agenda'       => ['Pendientes, calendario y avisos', 'bi-calendar3', true],
            'alumnos'      => [$esAlumno && ! $esDocente ? 'Cómo usar el aula' : 'Lo que hacen los alumnos', 'bi-person-workspace', true],
            'instituciones'=> ['Instituciones', 'bi-building', $esInst],
            'abonos'       => ['Abonos y pagos', 'bi-credit-card', $esDocente],
            'admin'        => ['Administración del sistema', 'bi-shield-lock', $esSuper],
            'faq'          => ['Preguntas frecuentes', 'bi-question-circle', true],
        ], fn ($s) => $s[2]);
    @endphp

    <div class="page-header">
        <div>
            <h1><i class="bi bi-life-preserver text-primary me-2"></i>Ayuda</h1>
            <p class="text-muted mb-0">Manual de uso del Aula Virtual. Buscá un tema o elegilo del índice.</p>
        </div>
        <div style="min-width: 260px">
            <input type="search" id="ayudaBuscar" class="form-control" placeholder="Buscar en la ayuda… (ej.: contraseña)" aria-label="Buscar en la ayuda">
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-3 d-none d-lg-block">
            <nav class="list-group ayuda-indice small" id="ayudaIndice">
                @foreach ($secciones as $id => [$titulo, $icono])
                    <a href="#{{ $id }}" class="list-group-item list-group-item-action" data-indice="{{ $id }}">
                        <i class="bi {{ $icono }} me-2 text-primary"></i>{{ $titulo }}
                    </a>
                @endforeach
            </nav>
        </div>

        <div class="col-lg-9">
            <div id="ayudaSinResultados" class="alert alert-light border d-none">No encontramos ese tema. Probá con otra palabra.</div>

            {{-- ===================== Introducción ===================== --}}
            <section id="intro" class="card mb-4 ayuda-seccion">
                <div class="card-body">
                    <h2 class="h4"><i class="bi bi-info-circle text-primary me-2"></i>Introducción</h2>
                    <p>El Aula Virtual es un campus en línea para dar clases: el docente publica contenidos, tareas y evaluaciones, y los alumnos entregan, rinden y siguen sus notas desde cualquier dispositivo.</p>
                    <ul>
                        <li><strong>Docente independiente:</strong> se registra, crea sus aulas (aunque sean de distintas escuelas) y paga un abono mensual personal.</li>
                        <li><strong>Institución:</strong> la escuela crea su cuenta, invita a sus docentes con un código y paga un abono según la cantidad de docentes.</li>
                    </ul>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light"><tr><th>Rol</th><th>Qué hace</th></tr></thead>
                            <tbody>
                                <tr><td>Docente</td><td>Crea aulas, publica clases, tareas y evaluaciones, corrige y lleva la libreta de calificaciones.</td></tr>
                                <tr><td>Alumno</td><td>Se une a las aulas, ve las clases, entrega tareas, rinde evaluaciones y consulta sus notas.</td></tr>
                                <tr><td>Administrador de institución</td><td>Invita docentes, supervisa todas las aulas de la institución y gestiona su abono.</td></tr>
                                <tr><td>Administrador del sistema</td><td>Aprueba pagos, ajusta abonos y precios, gestiona usuarios, correo y respaldos.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <p>Una misma persona puede tener más de un rol: por ejemplo, ser docente en sus aulas personales y también en una institución.</p>
                </div>
            </section>

            {{-- ===================== Primeros pasos ===================== --}}
            <section id="primeros" class="card mb-4 ayuda-seccion">
                <div class="card-body">
                    <h2 class="h4"><i class="bi bi-door-open text-primary me-2"></i>Primeros pasos</h2>

                    <h3>Registrarse</h3>
                    <ol>
                        <li>En la página principal tocá <strong>Crear cuenta</strong>.</li>
                        <li>Elegí el tipo de cuenta: <strong>Docente</strong>, <strong>Alumno</strong> o <strong>Institución</strong>.</li>
                        <li>Completá nombre, apellido, DNI, email y contraseña, y aceptá los Términos y la Política de privacidad.</li>
                        <li>Revisá tu correo y tocá <strong>Confirmar mi email</strong>. El enlace vence en 24 horas; si no llegó, mirá en spam o pedí que se reenvíe.</li>
                    </ol>

                    <h3>Ingresar</h3>
                    <p>En <strong>Ingresar</strong> podés escribir tu <strong>email o tu DNI</strong> y la contraseña. Si te dieron una contraseña inicial, el sistema te pide cambiarla la primera vez.</p>

                    <h3>Olvidé mi contraseña</h3>
                    <ul>
                        <li><strong>Si tenés email:</strong> tocá <strong>¿Olvidaste tu contraseña?</strong>, escribí tu email y seguí el enlace que te llega (vence en 60 minutos).</li>
                        <li><strong>Si sos alumno y no tenés email:</strong> usá la opción de pedir ayuda con tu DNI. Tu docente recibe el aviso y te asigna una clave nueva.</li>
                    </ul>

                    <h3>Mi perfil</h3>
                    <p>Desde el menú con tu nombre, arriba a la derecha:</p>
                    <ul>
                        <li><a href="{{ route('perfil.edit') }}">Mi perfil</a>: tus datos, tu foto y si querés recibir los avisos también por email.</li>
                        <li><a href="{{ route('password.cambiar') }}">Cambiar contraseña</a>.</li>
                    </ul>
                </div>
            </section>

            @if ($esDocente)
                {{-- ===================== Aulas ===================== --}}
                <section id="aulas" class="card mb-4 ayuda-seccion">
                    <div class="card-body">
                        <h2 class="h4"><i class="bi bi-easel text-primary me-2"></i>Crear y administrar aulas</h2>

                        <h3>Crear un aula</h3>
                        <ol>
                            <li>En <strong>Mis aulas</strong> tocá <strong>Nueva aula</strong>.</li>
                            <li>Completá materia, curso, división, turno, ciclo lectivo y una descripción.</li>
                            <li>Si sos docente de una institución, elegí si el aula es <strong>personal</strong> o de esa <strong>institución</strong>. Las personales las cubre tu abono; las institucionales, el de la escuela.</li>
                            <li>Guardá. El aula se crea con un <strong>código de acceso</strong> de 6 caracteres.</li>
                        </ol>

                        <h3>Sumar alumnos</h3>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light"><tr><th>Forma</th><th>Cómo funciona</th></tr></thead>
                                <tbody>
                                    <tr><td>Código de acceso</td><td>Compartí el código; el alumno lo carga en <strong>Unirme a un aula</strong>. Podés desactivarlo cuando ya estén todos.</td></tr>
                                    <tr><td>Importar planilla</td><td>Subí un CSV con los alumnos (apellido, nombre, DNI y email si lo tienen). Se crean las cuentas y quedan inscriptos.</td></tr>
                                    <tr><td>Alumnos sin email</td><td>Ingresan con su DNI. Desde la lista de miembros podés asignarles o restablecerles la clave.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p>Quien entra con una clave inicial debe cambiarla en el primer ingreso. Para dar de baja a alguien, usá la opción de baja en la lista de miembros.</p>

                        <h3>Co-docentes</h3>
                        <ul>
                            <li><strong>Invitar:</strong> escribí el email del colega; recibe el aviso y acepta.</li>
                            <li><strong>Pasar la titularidad:</strong> el titular puede dejarle el aula a un co-docente.</li>
                            <li><strong>Quitar o dejar el aula:</strong> el titular quita co-docentes; un co-docente puede salir cuando quiera.</li>
                        </ul>

                        <h3>Duplicar y archivar</h3>
                        <ul>
                            <li><strong>Duplicar</strong> copia unidades, clases, tareas y evaluaciones a un aula nueva, sin alumnos ni entregas. Ideal para el año siguiente o para otra división.</li>
                            <li><strong>Archivar</strong> saca el aula de las activas sin borrar nada.</li>
                        </ul>
                    </div>
                </section>

                {{-- ===================== Contenidos ===================== --}}
                <section id="contenidos" class="card mb-4 ayuda-seccion">
                    <div class="card-body">
                        <h2 class="h4"><i class="bi bi-journal-text text-primary me-2"></i>Contenidos</h2>
                        <p>El aula se organiza en <strong>unidades</strong> (por ejemplo, “Unidad 1: Números racionales”). Dentro de cada unidad van las clases, las tareas y las evaluaciones. Las unidades se ordenan con las flechas.</p>

                        <h3>Clases</h3>
                        <ol>
                            <li>Tocá <strong>Nueva clase</strong> dentro de una unidad.</li>
                            <li>Escribí el título y el contenido con el editor.</li>
                            <li>Agregá recursos: archivos (PDF, imágenes, documentos, hasta 25 MB cada uno) o enlaces (videos, páginas web).</li>
                            <li>Elegí cuándo se publica: <strong>ahora</strong>, en una <strong>fecha y hora</strong> programada o como <strong>borrador</strong>.</li>
                        </ol>
                        <p>Cuando una clase programada se publica, los alumnos reciben el aviso automáticamente.</p>

                        <h3>El editor de texto</h3>
                        <p>Permite títulos, negrita, cursiva, listas, enlaces, citas, código e imágenes. Las imágenes se insertan con su botón y quedan dentro del texto. Al pegar texto de otro sitio se limpia todo formato que no sea seguro.</p>
                    </div>
                </section>

                {{-- ===================== Tareas ===================== --}}
                <section id="tareas" class="card mb-4 ayuda-seccion">
                    <div class="card-body">
                        <h2 class="h4"><i class="bi bi-clipboard-check text-primary me-2"></i>Tareas y entregas</h2>

                        <h3>Crear una tarea</h3>
                        <ol>
                            <li>Dentro de una unidad tocá <strong>Nueva tarea</strong>.</li>
                            <li>Escribí la consigna y adjuntá los archivos que necesiten los alumnos.</li>
                            <li>Definí el <strong>puntaje máximo</strong> (por ejemplo, 10) y la <strong>fecha límite</strong>.</li>
                            <li>Elegí cuándo se publica: ahora, programada o como borrador.</li>
                        </ol>
                        <p>Un día antes del vencimiento, los alumnos que no entregaron reciben un recordatorio.</p>

                        <h3>Corregir</h3>
                        <p>En la tarea ves la lista de alumnos con el estado de cada uno. Abrí una entrega y elegí:</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light"><tr><th>Acción</th><th>Resultado para el alumno</th></tr></thead>
                                <tbody>
                                    <tr><td><strong>Corregir</strong> con nota y devolución</td><td>Ve la nota y tu comentario; la nota pasa a la libreta.</td></tr>
                                    <tr><td><strong>Devolver para rehacer</strong></td><td>La entrega vuelve a él para que la corrija y la mande de nuevo.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p>Las entregas por corregir aparecen en <a href="{{ route('agenda.pendientes') }}">Pendientes</a>.</p>
                    </div>
                </section>

                {{-- ===================== Evaluaciones ===================== --}}
                <section id="evaluaciones" class="card mb-4 ayuda-seccion">
                    <div class="card-body">
                        <h2 class="h4"><i class="bi bi-ui-checks text-primary me-2"></i>Evaluaciones</h2>

                        <h3>Crear una evaluación</h3>
                        <ol>
                            <li>Dentro de una unidad tocá <strong>Nueva evaluación</strong>.</li>
                            <li>Configurá apertura y cierre, el tiempo para resolverla, la cantidad de intentos y si se mezclan preguntas y opciones.</li>
                            <li>Entrá a <strong>Preguntas</strong> y cargalas con su puntaje. Se reordenan con las flechas.</li>
                            <li>Publicala. Los alumnos solo pueden rendirla entre la apertura y el cierre.</li>
                        </ol>

                        <h3>Tipos de preguntas</h3>
                        <ul>
                            <li><strong>Con opciones</strong> (una o varias correctas) y <strong>verdadero o falso</strong>: se corrigen solas.</li>
                            <li><strong>Respuesta abierta o desarrollo</strong>: quedan <strong>por corregir</strong> y las calificás vos desde el intento.</li>
                        </ul>

                        <h3>Durante y después</h3>
                        <ul>
                            <li>El alumno ve el tiempo que le queda; al terminarse, el intento se cierra con lo respondido.</li>
                            <li>En la libreta cuenta el <strong>mejor intento</strong> de cada alumno.</li>
                        </ul>
                    </div>
                </section>
            @endif

            {{-- ===================== Muro ===================== --}}
            <section id="muro" class="card mb-4 ayuda-seccion">
                <div class="card-body">
                    <h2 class="h4"><i class="bi bi-chat-square-text text-primary me-2"></i>Muro del aula</h2>
                    <p>El muro es el lugar de los avisos y las conversaciones del curso. Docentes y alumnos pueden publicar y comentar.</p>
                    <ul>
                        <li><strong>Publicar:</strong> escribí en el cuadro de arriba y tocá <strong>Publicar</strong>. Los miembros del aula reciben el aviso.</li>
                        <li><strong>Comentar:</strong> cada publicación tiene sus comentarios debajo.</li>
                        <li><strong>Fijar:</strong> el docente puede fijar una publicación para que quede siempre arriba.</li>
                        <li><strong>Editar o borrar:</strong> cada uno lo suyo; el docente puede borrar cualquier publicación o comentario de su aula.</li>
                    </ul>
                </div>
            </section>

            @if ($esDocente)
                {{-- ===================== Calificaciones ===================== --}}
                <section id="calificaciones" class="card mb-4 ayuda-seccion">
                    <div class="card-body">
                        <h2 class="h4"><i class="bi bi-table text-primary me-2"></i>Calificaciones</h2>
                        <p>La libreta junta todas las notas del aula (tareas, evaluaciones y notas cargadas a mano) y calcula el promedio de cada alumno en escala 10. Se abre con <strong>Calificaciones</strong> en la página del aula.</p>

                        <h3>Configurar (una vez por aula)</h3>
                        <p>En <strong>Calificaciones → Configurar</strong>:</p>
                        <ol>
                            <li><strong>Períodos:</strong> elegí una plantilla (3 trimestres, 2 cuatrimestres, 4 bimestres o anual) y ajustá las fechas si hace falta.</li>
                            <li><strong>Notas cargadas a mano:</strong> agregá columnas para concepto, oral, carpeta, etc., con puntaje máximo, peso, fecha y período.</li>
                            <li><strong>Pesos:</strong> cuánto vale cada tarea y evaluación en el promedio.</li>
                        </ol>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered" style="max-width: 420px">
                                <thead class="table-light"><tr><th>Peso</th><th>Significado</th></tr></thead>
                                <tbody>
                                    <tr><td>1</td><td>Normal</td></tr>
                                    <tr><td>2</td><td>Vale el doble</td></tr>
                                    <tr><td>0,5</td><td>Vale la mitad</td></tr>
                                    <tr><td>0</td><td>No cuenta (por ejemplo, una práctica)</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p>Cada tarea cae en el período de su fecha límite y cada evaluación en el de su fecha de cierre.</p>

                        <h3>Usar la libreta</h3>
                        <ul>
                            <li>Arriba están las pestañas de cada período y <strong>Todo el ciclo</strong>. Por defecto se abre el período en curso.</li>
                            <li>Las columnas <span class="px-1" style="background:#FFFBEB">amarillas</span> son las notas a mano: escribí la nota (acepta coma, ej. 7,5), Enter pasa al alumno de abajo, y tocá <strong>Guardar notas</strong>.</li>
                            <li>La columna <strong>Cierre</strong> es la nota final del período. Sugiere el promedio redondeado; si la dejás vacía, el boletín usa el promedio.</li>
                            <li>Con el ícono <i class="bi bi-chat-left-text"></i> agregás una observación; la del cierre aparece en el boletín.</li>
                            <li><strong>Contar lo no entregado como 0</strong> suma con 0 lo vencido sin entregar o sin rendir.</li>
                            <li>Tocá una nota de tarea o evaluación para ir a esa entrega o ese intento.</li>
                        </ul>

                        <h3>Boletín y Excel</h3>
                        <ul>
                            <li><strong>Boletines</strong> abre una hoja por alumno con las notas de cada período, las observaciones y la calificación final. Tocá <strong>Imprimir / Guardar como PDF</strong>. El ícono <i class="bi bi-printer"></i> de cada alumno abre solo el suyo.</li>
                            <li><strong>Excel</strong> descarga la planilla del período que estás viendo.</li>
                        </ul>
                    </div>
                </section>
            @endif

            {{-- ===================== Agenda ===================== --}}
            <section id="agenda" class="card mb-4 ayuda-seccion">
                <div class="card-body">
                    <h2 class="h4"><i class="bi bi-calendar3 text-primary me-2"></i>Pendientes, calendario y avisos</h2>

                    <h3>Pendientes y Calendario</h3>
                    <p>En el menú superior, <a href="{{ route('agenda.pendientes') }}">Pendientes</a> muestra la lista de lo que tenés por hacer y <a href="{{ route('agenda.calendario') }}">Calendario</a> lo ubica por fecha:</p>
                    <ul>
                        <li><strong>Alumnos:</strong> tareas por entregar y evaluaciones por rendir, con las vencidas marcadas.</li>
                        <li><strong>Docentes:</strong> entregas e intentos por corregir y los vencimientos de lo publicado.</li>
                    </ul>

                    <h3>Notificaciones</h3>
                    <p>La campanita <i class="bi bi-bell"></i> muestra los avisos sin leer. Llegan avisos cuando:</p>
                    <ul>
                        <li>Se publica una clase, tarea o evaluación (también las programadas, al llegar su hora).</li>
                        <li>Algo está por vencer (recordatorio diario a las 8:00).</li>
                        <li>Te corrigen o devuelven una entrega, o un alumno entrega una tarea.</li>
                        <li>Hay algo nuevo en el muro.</li>
                        <li>El abono está por vencer.</li>
                    </ul>
                    <p>Si en <a href="{{ route('perfil.edit') }}">Mi perfil</a> activás los avisos por email, también te llegan por correo. Los avisos leídos se borran solos a los 90 días.</p>
                </div>
            </section>

            {{-- ===================== Alumnos ===================== --}}
            <section id="alumnos" class="card mb-4 ayuda-seccion">
                <div class="card-body">
                    <h2 class="h4"><i class="bi bi-person-workspace text-primary me-2"></i>{{ $secciones['alumnos'][0] }}</h2>

                    <h3>Unirse a un aula</h3>
                    <p>En <strong>Mis aulas</strong> tocá <strong>Unirme a un aula</strong> y escribí el código de 6 caracteres que da el docente. Si el docente o la escuela ya inscribió al alumno, el aula aparece sola.</p>

                    <h3>Clases y materiales</h3>
                    <p>Dentro del aula se abren las unidades para ver las clases; los archivos se descargan desde cada clase. Lo que todavía no se publicó no aparece.</p>

                    <h3>Entregar una tarea</h3>
                    <ol>
                        <li>Abrir la tarea y leer la consigna.</li>
                        <li>Escribir la respuesta y/o adjuntar archivos (hasta 25 MB cada uno).</li>
                        <li>Tocar <strong>Entregar</strong> antes de la fecha límite.</li>
                    </ol>
                    <p>Si el docente la devuelve para rehacer, se corrige y se entrega de nuevo. Cuando la corrige, se ven la nota y la devolución.</p>

                    <h3>Rendir una evaluación</h3>
                    <ol>
                        <li>Con la evaluación abierta, tocar <strong>Comenzar</strong>: desde ahí corre el tiempo.</li>
                        <li>Responder y tocar <strong>Finalizar</strong>. Si se termina el tiempo, se entrega sola con lo respondido.</li>
                        <li>Las preguntas con opciones se corrigen al instante; las de desarrollo, cuando las corrige el docente.</li>
                    </ol>

                    <h3>Mis calificaciones</h3>
                    <p>En <strong>Calificaciones</strong> el alumno ve sus notas por período, su promedio, la nota de cierre con la observación del docente y el botón <strong>Mi boletín</strong> para imprimirlo o guardarlo en PDF.</p>
                </div>
            </section>

            @if ($esInst)
                {{-- ===================== Instituciones ===================== --}}
                <section id="instituciones" class="card mb-4 ayuda-seccion">
                    <div class="card-body">
                        <h2 class="h4"><i class="bi bi-building text-primary me-2"></i>Instituciones</h2>

                        <h3>Crear la cuenta de la institución</h3>
                        <ol>
                            <li>Registrate eligiendo <strong>Institución</strong>, o desde tu cuenta entrá a <a href="{{ route('instituciones.index') }}">Instituciones</a> y creá una nueva.</li>
                            <li>Completá nombre, CUE, nivel y localidad.</li>
                            <li>Quien la crea queda como <strong>administrador</strong>.</li>
                        </ol>

                        <h3>Invitar docentes</h3>
                        <p>Cada institución tiene un <strong>código de invitación</strong>. Cada docente, con su propia cuenta, entra a <strong>Instituciones</strong>, elige unirse y escribe el código. Desde ese momento puede crear aulas de la institución.</p>
                        <p>Cada docente ocupa un lugar del <strong>cupo</strong> del abono institucional. Con el cupo completo no se suman más hasta ampliarlo.</p>

                        <h3>Administrar</h3>
                        <ul>
                            <li>Ver los docentes, sus aulas y el cupo usado.</li>
                            <li>Dar o quitar el rol de <strong>administrador</strong> a otros miembros.</li>
                            <li>Dar de baja a un docente, lo que libera su lugar.</li>
                            <li>Entrar a cualquier aula de la institución para supervisar, incluida la libreta.</li>
                            <li>Gestionar y pagar el abono institucional.</li>
                        </ul>
                    </div>
                </section>
            @endif

            @if ($esDocente)
                {{-- ===================== Abonos ===================== --}}
                <section id="abonos" class="card mb-4 ayuda-seccion">
                    <div class="card-body">
                        <h2 class="h4"><i class="bi bi-credit-card text-primary me-2"></i>Abonos y pagos</h2>
                        <p>Los alumnos no pagan. Pagan los docentes por sus aulas personales y las instituciones por sus docentes. No hay período de prueba.</p>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light"><tr><th>Abono</th><th>Quién lo paga</th><th>Qué cubre</th><th>Cómo se calcula</th></tr></thead>
                                <tbody>
                                    <tr><td>Unipersonal</td><td>El docente</td><td>Sus aulas personales</td><td>Precio mensual fijo</td></tr>
                                    <tr><td>Institucional</td><td>La institución</td><td>Solo las aulas de la institución</td><td>Precio por docente según la cantidad; se contrata un cupo</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h3>Cómo pagar</h3>
                        <p>Entrá a <strong>Mi abono</strong> (menú con tu nombre) o al abono de tu institución, elegí los meses y el medio de pago:</p>
                        <ul>
                            <li><strong>Mercado Pago:</strong> el abono se activa en cuanto se acredita el pago.</li>
                            <li><strong>Transferencia o efectivo:</strong> registrá el pago con sus datos; se activa cuando el administrador lo aprueba.</li>
                        </ul>
                        <p>Unos días antes del vencimiento llega un aviso.</p>

                        <h3>Si el abono vence</h3>
                        <p>Las aulas que cubre quedan en <strong>solo lectura</strong>: se puede entrar y ver todo, pero no publicar ni crear contenido. Sí se puede seguir corrigiendo, cargando notas y sacando boletines. Al pagar, todo vuelve a funcionar sin perder nada.</p>
                    </div>
                </section>
            @endif

            @if ($esSuper)
                {{-- ===================== Administración ===================== --}}
                <section id="admin" class="card mb-4 ayuda-seccion">
                    <div class="card-body">
                        <h2 class="h4"><i class="bi bi-shield-lock text-primary me-2"></i>Administración del sistema</h2>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light"><tr><th>Pestaña</th><th>Para qué sirve</th></tr></thead>
                                <tbody>
                                    <tr><td><a href="{{ route('admin.panel') }}">Resumen</a></td><td>Docentes, alumnos, instituciones y aulas; abonos activos; ingresos; abonos que vencen en 10 días.</td></tr>
                                    <tr><td><a href="{{ route('admin.pagos.index') }}">Pagos</a></td><td>Aprobar o rechazar transferencias y registrar pagos manuales.</td></tr>
                                    <tr><td><a href="{{ route('admin.abonos.index') }}">Abonos</a></td><td>Buscar abonos y ajustar vencimientos o cupos.</td></tr>
                                    <tr><td><a href="{{ route('admin.planes.index') }}">Precios</a></td><td>Precio del abono unipersonal y escalas del institucional.</td></tr>
                                    <tr><td><a href="{{ route('admin.usuarios.index') }}">Usuarios</a></td><td>Activar o desactivar usuarios y restablecer contraseñas.</td></tr>
                                    <tr><td><a href="{{ route('admin.sistema.index') }}">Sistema</a></td><td>Correo de prueba, cola de envíos (pendientes y fallidos) y respaldos diarios (03:30, se guardan los últimos 14).</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p>Conviene bajar un respaldo a otra computadora cada tanto: si falla el servidor, los respaldos guardados ahí se pierden con él.</p>
                    </div>
                </section>
            @endif

            {{-- ===================== FAQ ===================== --}}
            <section id="faq" class="card mb-4 ayuda-seccion">
                <div class="card-body">
                    <h2 class="h4"><i class="bi bi-question-circle text-primary me-2"></i>Preguntas frecuentes</h2>
                    <div class="accordion" id="faqAcordeon">
                        @foreach ([
                            ['No me llegó el email de confirmación o de contraseña', 'Revisá spam y correo no deseado. Esperá un par de minutos y pedí que se reenvíe.'],
                            ['No recuerdo si me registré con email o DNI', 'Probá ingresar con cualquiera de los dos: el sistema acepta ambos.'],
                            ['Un alumno sin email no puede entrar', 'El docente le asigna una clave nueva desde la lista de miembros del aula.'],
                            ['El código del aula no funciona', 'Verificá que esté bien escrito y que el docente no lo haya desactivado.'],
                            ['No puedo publicar ni crear contenido', 'El abono de esa aula venció. Renovalo desde Mi abono o pedile a tu institución que lo renueve.'],
                            ['No se pueden sumar más docentes a la institución', 'El cupo está completo: ampliá el abono institucional o dale de baja a quien ya no lo use.'],
                            ['Un archivo no se sube', 'Cada archivo puede pesar hasta 25 MB. Comprimilo o dividí el contenido.'],
                            ['No veo una clase o tarea', 'Puede estar programada para más adelante o guardada como borrador.'],
                            ['Pagué por transferencia y el abono sigue vencido', 'Queda activo cuando el administrador aprueba el pago.'],
                            ['La nota de una tarea no aparece en la libreta', 'Revisá que la entrega esté corregida con nota y que la tarea esté en el período que estás mirando, o mirá Todo el ciclo.'],
                        ] as $i => [$p, $r])
                            <div class="accordion-item">
                                <h3 class="accordion-header m-0">
                                    <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}" aria-expanded="false">
                                        {{ $p }}
                                    </button>
                                </h3>
                                <div id="faq{{ $i }}" class="accordion-collapse collapse" data-bs-parent="#faqAcordeon">
                                    <div class="accordion-body small">{{ $r }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    (() => {
        const input = document.getElementById('ayudaBuscar');
        const secciones = [...document.querySelectorAll('.ayuda-seccion')];
        const indice = document.getElementById('ayudaIndice');
        const vacio = document.getElementById('ayudaSinResultados');
        const normalizar = (t) => t.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

        // Buscar: muestra solo las secciones que contienen todas las palabras
        input.addEventListener('input', () => {
            const palabras = normalizar(input.value.trim()).split(/\s+/).filter(Boolean);
            let visibles = 0;
            secciones.forEach((s) => {
                const texto = normalizar(s.textContent);
                const ok = palabras.every((p) => texto.includes(p));
                s.classList.toggle('d-none', !ok);
                indice?.querySelector(`[data-indice="${s.id}"]`)?.classList.toggle('d-none', !ok);
                if (ok) visibles++;
                // En las preguntas frecuentes, abrir las que coinciden
                if (s.id === 'faq') {
                    s.querySelectorAll('.accordion-item').forEach((it) => {
                        const coincide = palabras.length && palabras.every((p) => normalizar(it.textContent).includes(p));
                        it.querySelector('.accordion-collapse').classList.toggle('show', coincide);
                        it.querySelector('.accordion-button').classList.toggle('collapsed', !coincide);
                    });
                }
            });
            vacio.classList.toggle('d-none', visibles > 0);
        });

        // Marcar en el índice la sección que se está leyendo
        if (indice && 'IntersectionObserver' in window) {
            const obs = new IntersectionObserver((entradas) => {
                entradas.forEach((e) => {
                    if (!e.isIntersecting) return;
                    indice.querySelectorAll('.active').forEach((a) => a.classList.remove('active'));
                    indice.querySelector(`[data-indice="${e.target.id}"]`)?.classList.add('active');
                });
            }, { rootMargin: '-30% 0px -60% 0px' });
            secciones.forEach((s) => obs.observe(s));
        }
    })();
</script>
@endpush
