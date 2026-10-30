<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aula Virtual — El campus online para docentes e instituciones | MiGestión</title>
    <meta name="description" content="Creá tus aulas virtuales en minutos: clases, material, tareas, evaluaciones que se corrigen solas, libreta de calificaciones y muro. Para docentes y para instituciones educativas de Argentina.">
    <meta property="og:title" content="Aula Virtual — MiGestión">
    <meta property="og:description" content="El campus online para docentes e instituciones. Clases, tareas, evaluaciones y calificaciones en un solo lugar.">
    <meta property="og:type" content="website">
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="landing">
@php($pesos = fn ($v) => \App\Models\Plan::pesos($v))

{{-- ===================== Barra superior ===================== --}}
<nav class="navbar navbar-expand-lg landing-nav sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('landing') }}">
            <i class="bi bi-mortarboard-fill text-warning me-1"></i> Aula Virtual
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#menuLanding" aria-label="Abrir menú">
            <i class="bi bi-list fs-2"></i>
        </button>
        <div class="collapse navbar-collapse" id="menuLanding">
            <ul class="navbar-nav mx-auto gap-lg-2">
                <li class="nav-item"><a class="nav-link" href="#funciones">Funciones</a></li>
                <li class="nav-item"><a class="nav-link" href="#como-funciona">Cómo funciona</a></li>
                <li class="nav-item"><a class="nav-link" href="#instituciones">Instituciones</a></li>
                <li class="nav-item"><a class="nav-link" href="#planes">Planes</a></li>
                <li class="nav-item"><a class="nav-link" href="#preguntas">Preguntas</a></li>
            </ul>
            <div class="d-flex gap-2 mt-2 mt-lg-0">
                <a href="{{ route('login') }}" class="btn btn-outline-primary">Ingresar</a>
                <a href="{{ route('registro', ['tipo' => 'docente']) }}" class="btn btn-primary">Empezar</a>
            </div>
        </div>
    </div>
</nav>

{{-- ===================== Hero ===================== --}}
<header class="landing-hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="landing-chip mb-3"><i class="bi bi-stars me-1"></i> Hecho en Argentina, para la escuela argentina</span>
                <h1 class="display-5 fw-bold mb-3">Tu aula, abierta <span class="text-resaltado">todos los días</span>, en cualquier lugar.</h1>
                <p class="lead mb-4">
                    Publicá clases y material, tomá tareas y evaluaciones que se corrigen solas, y tené todas las notas
                    en una libreta lista para exportar. Sin instalar nada: funciona en la compu y en el celular.
                </p>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="{{ route('registro', ['tipo' => 'docente']) }}" class="btn btn-warning btn-lg fw-semibold">
                        <i class="bi bi-person-workspace me-1"></i> Soy docente
                    </a>
                    <a href="{{ route('registro', ['tipo' => 'institucion']) }}" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-building me-1"></i> Soy una institución
                    </a>
                </div>
                <div class="d-flex flex-wrap gap-4 small opacity-75">
                    <span><i class="bi bi-check-circle me-1"></i>Alumnos gratis, siempre</span>
                    <span><i class="bi bi-check-circle me-1"></i>Aulas ilimitadas</span>
                    <span><i class="bi bi-check-circle me-1"></i>Sin permanencia</span>
                </div>
            </div>

            {{-- Vista previa del sistema (maqueta en HTML) --}}
            <div class="col-lg-6">
                <div class="landing-mock">
                    <div class="landing-mock-barra"><span></span><span></span><span></span></div>
                    <div class="landing-mock-portada">
                        <div class="small opacity-75">EES N° 5 · Ciclo {{ now('America/Argentina/Buenos_Aires')->year }}</div>
                        <div class="h5 fw-bold mb-0">Matemática — 3° A</div>
                    </div>
                    <div class="p-3">
                        <div class="landing-mock-item"><i class="bi bi-megaphone text-primary"></i><div><strong>Aviso fijado</strong><br><span>El viernes repasamos para la evaluación 📚</span></div></div>
                        <div class="landing-mock-item"><i class="bi bi-journal-text text-primary"></i><div><strong>Clase 8 — Funciones lineales</strong><br><span>PDF · video de YouTube</span></div></div>
                        <div class="landing-mock-item"><i class="bi bi-clipboard-check text-warning"></i><div><strong>TP N° 3</strong><br><span>Vence el viernes 18:00</span></div><span class="badge bg-primary ms-auto">12 por corregir</span></div>
                        <div class="landing-mock-item"><i class="bi bi-ui-checks text-success"></i><div><strong>Evaluación Unidad 2</strong><br><span>20 min · se corrige sola</span></div><span class="badge bg-success ms-auto">Prom. 7,8</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

{{-- ===================== Para quién ===================== --}}
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            @foreach ([
                ['bi-person-workspace', 'Para docentes', 'Usalo por tu cuenta en todas las escuelas donde des clases. Cada curso es un aula, con su código para que se sumen tus alumnos.'],
                ['bi-building', 'Para instituciones', 'La escuela invita a sus docentes con un código, carga a los alumnos desde una planilla y sigue todas sus aulas desde un panel.'],
                ['bi-backpack', 'Para alumnos', 'Entran con su email o su DNI, ven el material, entregan tareas, rinden evaluaciones y consultan sus notas. No pagan nada.'],
            ] as [$icono, $titulo, $texto])
                <div class="col-md-4">
                    <div class="landing-card h-100">
                        <div class="landing-icono"><i class="bi {{ $icono }}"></i></div>
                        <h3 class="h5 fw-bold">{{ $titulo }}</h3>
                        <p class="text-muted mb-0">{{ $texto }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===================== Funciones ===================== --}}
<section id="funciones" class="py-5 landing-gris">
    <div class="container">
        <div class="text-center mb-5">
            <span class="landing-chip landing-chip-claro">Funciones</span>
            <h2 class="fw-bold mt-2">Todo lo que tu curso necesita, en un solo lugar</h2>
            <p class="text-muted">Pensado para el día a día del aula, no para expertos en computación.</p>
        </div>
        <div class="row g-4">
            @foreach ([
                ['bi-journal-bookmark', 'Aulas con código', 'Creá un aula por curso y compartí un código de 6 letras. Tus alumnos se suman solos en segundos.'],
                ['bi-collection', 'Clases y material', 'Organizá por unidades. Subí PDF, Word, imágenes, audios, links y videos de YouTube que se ven ahí mismo.'],
                ['bi-clipboard-check', 'Tareas y entregas', 'Consigna con fecha límite, entregas con archivos, corrección con nota y devolución, o "devolver para rehacer".'],
                ['bi-ui-checks', 'Evaluaciones automáticas', 'Opción única, múltiple, verdadero/falso y respuesta escrita. Con reloj, intentos y corrección instantánea.'],
                ['bi-table', 'Libreta de calificaciones', 'Todas las notas del curso en una planilla, con promedios. Se exporta a Excel con un clic.'],
                ['bi-megaphone', 'Muro del aula', 'Avisos fijados, comentarios y un contador de novedades para que nadie se pierda nada.'],
                ['bi-calendar-event', 'Programá publicaciones', 'Dejá clases, tareas y evaluaciones listas para que se publiquen solas el día y la hora que elijas.'],
                ['bi-phone', 'Funciona en el celular', 'Se adapta a cualquier pantalla. Ideal para alumnos que solo tienen el teléfono.'],
            ] as [$icono, $titulo, $texto])
                <div class="col-md-6 col-lg-3">
                    <div class="landing-funcion h-100">
                        <i class="bi {{ $icono }}"></i>
                        <h3 class="h6 fw-bold mt-3">{{ $titulo }}</h3>
                        <p class="small text-muted mb-0">{{ $texto }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===================== Cómo funciona ===================== --}}
<section id="como-funciona" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="landing-chip landing-chip-claro">Cómo funciona</span>
            <h2 class="fw-bold mt-2">En marcha en 5 minutos</h2>
        </div>
        <div class="row g-4 landing-pasos">
            @foreach ([
                ['Creá tu cuenta', 'Registrate como docente y activá tu abono. Si tu escuela ya usa Aula Virtual, sumate con su código.'],
                ['Armá tus aulas', 'Una por curso: materia, año, división y color. Cargá tus unidades, clases y tareas.'],
                ['Compartí el código', 'Tus alumnos se registran con email o DNI y se unen con el código del aula.'],
                ['Corregí y seguí el curso', 'Las evaluaciones se corrigen solas y todas las notas quedan en la libreta.'],
            ] as $i => [$titulo, $texto])
                <div class="col-md-6 col-lg-3">
                    <div class="landing-paso">
                        <div class="landing-paso-numero">{{ $i + 1 }}</div>
                        <h3 class="h6 fw-bold">{{ $titulo }}</h3>
                        <p class="small text-muted mb-0">{{ $texto }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===================== Instituciones ===================== --}}
<section id="instituciones" class="py-5 landing-oscuro text-white">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="landing-chip">Para instituciones</span>
                <h2 class="fw-bold mt-2 mb-3">Toda la escuela conectada, sin perder la libertad de cada docente</h2>
                <p class="opacity-75 mb-4">La institución contrata lugares para sus docentes y gestiona todo desde un panel. Cada docente puede seguir teniendo sus aulas personales de otras escuelas: la institución solo ve las aulas que son suyas.</p>
                <ul class="list-unstyled landing-lista">
                    <li><i class="bi bi-key"></i> Código de invitación para sumar docentes</li>
                    <li><i class="bi bi-file-earmark-spreadsheet"></i> Carga masiva de alumnos desde Excel, con contraseña inicial</li>
                    <li><i class="bi bi-eye"></i> Seguimiento de todas las aulas y sus calificaciones</li>
                    <li><i class="bi bi-shield-lock"></i> Restablecimiento de contraseñas para alumnos sin email</li>
                    <li><i class="bi bi-people"></i> Varios administradores (dirección, secretaría)</li>
                </ul>
                <a href="{{ route('registro', ['tipo' => 'institucion']) }}" class="btn btn-warning btn-lg fw-semibold mt-2">Registrar mi institución</a>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    @foreach ([['bi-person-workspace', 'Docentes', 'invitados con código'], ['bi-backpack', 'Alumnos', 'ilimitados y gratis'], ['bi-journal-bookmark', 'Aulas', 'sin límite'], ['bi-table', 'Notas', 'exportables a Excel']] as [$i, $t, $s])
                        <div class="col-6">
                            <div class="landing-dato">
                                <i class="bi {{ $i }}"></i>
                                <div class="fw-bold">{{ $t }}</div>
                                <div class="small opacity-75">{{ $s }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===================== Planes ===================== --}}
<section id="planes" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="landing-chip landing-chip-claro">Planes</span>
            <h2 class="fw-bold mt-2">Precios simples, sin sorpresas</h2>
            <p class="text-muted">Los alumnos nunca pagan. Pagás por mes, sin permanencia, por transferencia o Mercado Pago.</p>
        </div>

        <div class="row g-4 justify-content-center">
            {{-- Unipersonal --}}
            <div class="col-lg-5">
                <div class="landing-plan h-100">
                    <div class="landing-icono"><i class="bi bi-person-workspace"></i></div>
                    <h3 class="h4 fw-bold">{{ $planUni?->nombre ?? 'Docente' }}</h3>
                    <p class="text-muted">{{ $planUni?->descripcion ?? 'Para docentes que quieren usar el Aula Virtual por su cuenta.' }}</p>
                    @if ($planUni)
                        <div class="landing-precio">{{ $pesos($planUni->precio) }} <span>/ mes</span></div>
                    @else
                        <div class="landing-precio"><span>Consultanos</span></div>
                    @endif
                    <ul class="list-unstyled landing-incluye my-4">
                        <li><i class="bi bi-check2"></i> Aulas ilimitadas, en todas tus escuelas</li>
                        <li><i class="bi bi-check2"></i> Alumnos ilimitados</li>
                        <li><i class="bi bi-check2"></i> Clases, tareas, evaluaciones y libreta</li>
                        <li><i class="bi bi-check2"></i> Archivos de hasta 20 MB</li>
                        <li><i class="bi bi-check2"></i> Exportación a Excel</li>
                    </ul>
                    <a href="{{ route('registro', ['tipo' => 'docente']) }}" class="btn btn-primary btn-lg w-100 mt-auto">Empezar como docente</a>
                </div>
            </div>

            {{-- Institucional --}}
            <div class="col-lg-5">
                <div class="landing-plan landing-plan-destacado h-100">
                    <span class="landing-plan-cinta">Ahorrá con más docentes</span>
                    <div class="landing-icono"><i class="bi bi-building"></i></div>
                    <h3 class="h4 fw-bold">Institucional</h3>
                    <p class="text-muted">Para escuelas e institutos. Pagás por cada docente que usa el Aula Virtual: cuantos más, menos cuesta cada uno.</p>

                    @if ($escalas->isNotEmpty())
                        <div class="landing-precio">desde {{ $pesos($desdeInst) }} <span>/ docente / mes</span></div>

                        <table class="table table-sm my-3 small">
                            <tbody>
                                @foreach ($escalas as $e)
                                    <tr><td>{{ $e->rango_texto }}</td><td class="text-end fw-semibold">{{ $pesos($e->precio) }} c/u</td></tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{-- Calculadora --}}
                        <div class="landing-calculadora mb-3">
                            <label for="calcDocentes" class="form-label small fw-semibold mb-1">¿Cuántos docentes tiene tu institución?</label>
                            <div class="d-flex align-items-center gap-3">
                                <input type="range" class="form-range flex-grow-1" id="calcRango" min="1" max="120" value="15">
                                <input type="number" class="form-control form-control-sm text-center" id="calcDocentes" min="1" max="500" value="15" style="width: 80px">
                            </div>
                            <div class="d-flex justify-content-between align-items-end mt-2">
                                <span class="small text-muted" id="calcDetalle"></span>
                                <span class="h4 fw-bold mb-0 text-primary" id="calcTotal"></span>
                            </div>
                        </div>
                    @else
                        <div class="landing-precio"><span>Consultanos</span></div>
                    @endif

                    <a href="{{ route('registro', ['tipo' => 'institucion']) }}" class="btn btn-warning btn-lg w-100 fw-semibold mt-auto">Registrar mi institución</a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ===================== Preguntas ===================== --}}
<section id="preguntas" class="py-5 landing-gris">
    <div class="container" style="max-width: 820px">
        <div class="text-center mb-4">
            <span class="landing-chip landing-chip-claro">Preguntas frecuentes</span>
            <h2 class="fw-bold mt-2">¿Tenés dudas?</h2>
        </div>
        <div class="accordion" id="faq">
            @foreach ([
                ['¿Los alumnos tienen que pagar?', 'No. Los alumnos se registran y usan el Aula Virtual gratis, siempre. Paga el docente (abono unipersonal) o la institución (abono institucional).'],
                ['¿Puedo usarlo si doy clases en varias escuelas?', 'Sí. Con el abono unipersonal creás aulas para todos tus cursos, en todas las escuelas que quieras. Si alguna de tus escuelas tiene abono institucional, las aulas de esa escuela las cubre la institución.'],
                ['¿Qué pasa si un alumno no tiene email?', 'Puede registrarse e ingresar con su DNI. Si se olvida la contraseña, su institución puede restablecerla.'],
                ['¿Cómo pago?', 'Por transferencia bancaria o Mercado Pago, por mes o adelantando 3, 6 o 12 meses. No hay permanencia mínima.'],
                ['¿Qué pasa si se vence el abono?', 'No se pierde nada. Las aulas quedan en modo de solo lectura: se puede ver todo el contenido y las notas, pero no publicar cosas nuevas hasta renovar.'],
                ['¿Cómo se cuentan los docentes en el plan institucional?', 'Cada docente que se suma a la institución con el código ocupa un lugar. Los directivos o administrativos que solo administran no ocupan lugar. Podés ampliar el cupo en cualquier momento.'],
                ['¿Necesito instalar algo?', 'No. Funciona desde el navegador de la compu, la tablet o el celular.'],
            ] as $i => [$pregunta, $respuesta])
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button {{ $i ? 'collapsed' : '' }} fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}">
                            {{ $pregunta }}
                        </button>
                    </h3>
                    <div id="faq{{ $i }}" class="accordion-collapse collapse {{ $i ? '' : 'show' }}" data-bs-parent="#faq">
                        <div class="accordion-body text-muted">{{ $respuesta }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ===================== Llamado final ===================== --}}
<section class="py-5 landing-cta text-white text-center">
    <div class="container">
        <h2 class="fw-bold mb-3">Tu próxima clase puede empezar hoy</h2>
        <p class="opacity-75 mb-4">Creá tu cuenta en un minuto y armá tu primera aula.</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="{{ route('registro', ['tipo' => 'docente']) }}" class="btn btn-warning btn-lg fw-semibold">Empezar como docente</a>
            <a href="{{ route('registro', ['tipo' => 'institucion']) }}" class="btn btn-outline-light btn-lg">Registrar institución</a>
        </div>
    </div>
</section>

{{-- ===================== Pie ===================== --}}
<footer class="landing-pie py-4">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3 small">
        <span><i class="bi bi-mortarboard-fill text-warning me-1"></i><strong>Aula Virtual</strong> · un sistema de <a href="https://www.migestion.com.ar" class="text-reset fw-semibold">MiGestión</a></span>
        <span>
            <a href="mailto:{{ config('abonos.contacto') }}" class="text-reset me-3"><i class="bi bi-envelope me-1"></i>{{ config('abonos.contacto') }}</a>
            <a href="{{ route('login') }}" class="text-reset">Ingresar</a>
        </span>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const escalas = @json($escalasJs);
    const rango = document.getElementById('calcRango');
    const num   = document.getElementById('calcDocentes');
    if (!rango || !escalas.length) return;

    const pesos = v => '$ ' + Math.round(v).toLocaleString('es-AR');

    const calcular = (n) => {
        n = Math.max(1, parseInt(n, 10) || 1);
        const e = escalas.find(x => n >= x.desde && (x.hasta === null || n <= x.hasta)) || escalas[escalas.length - 1];
        document.getElementById('calcDetalle').textContent = n + ' docentes × ' + pesos(e.precio);
        document.getElementById('calcTotal').textContent = pesos(n * e.precio) + ' /mes';
    };

    rango.addEventListener('input', () => { num.value = rango.value; calcular(rango.value); });
    num.addEventListener('input', () => { rango.value = Math.min(120, num.value || 1); calcular(num.value); });
    calcular(num.value);
});
</script>
</body>
</html>
