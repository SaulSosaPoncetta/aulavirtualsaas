@extends('layouts.publico')

@section('titulo', 'Política de privacidad')

@section('contenido')
    @php($e = config('empresa'))

    <h1 class="fw-bold mb-1">Política de privacidad</h1>
    <p class="text-muted">Última actualización: {{ $e['legales_actualizados'] }}</p>

    <p>
        En {{ $e['marca'] }} cuidamos los datos personales de quienes usan el Aula Virtual, conforme a la
        <strong>Ley 25.326 de Protección de los Datos Personales</strong> y sus normas complementarias.
    </p>

    <h2 class="h5 fw-bold mt-4">1. Responsable de los datos</h2>
    <p>
        {{ $e['razon_social'] }}{{ $e['cuit'] ? ', CUIT ' . $e['cuit'] : '' }}{{ $e['domicilio'] ? ', ' . $e['domicilio'] : '' }}.
        Contacto: <a href="mailto:{{ $e['email'] }}">{{ $e['email'] }}</a>.
        Cuando una institución da de alta a sus docentes y alumnos, la institución es responsable de esos datos y {{ $e['marca'] }}
        actúa como prestador que los trata por cuenta de ella.
    </p>

    <h2 class="h5 fw-bold mt-4">2. Qué datos recolectamos</h2>
    <ul>
        <li><strong>De la cuenta:</strong> nombre, apellido, DNI y/o email, y contraseña (guardada cifrada; nadie puede verla).</li>
        <li><strong>De uso educativo:</strong> aulas a las que pertenecés, entregas, respuestas a evaluaciones, calificaciones, comentarios y archivos que subís.</li>
        <li><strong>De facturación (docentes e instituciones):</strong> pagos realizados, importes y comprobantes. Los datos de tarjetas los procesa Mercado Pago; nosotros no los vemos ni los guardamos.</li>
        <li><strong>Técnicos:</strong> fecha del último ingreso y datos necesarios para mantener la sesión iniciada. No usamos cookies publicitarias ni de seguimiento.</li>
    </ul>

    <h2 class="h5 fw-bold mt-4">3. Para qué los usamos</h2>
    <ul>
        <li>Prestar el servicio: que docentes y alumnos compartan contenidos, entregas y calificaciones dentro de cada aula.</li>
        <li>Gestionar los abonos y pagos.</li>
        <li>Enviarte avisos del servicio (confirmación de email, recuperación de contraseña, novedades de tus aulas, vencimientos).</li>
        <li>Brindar soporte y mantener la seguridad del sistema.</li>
    </ul>
    <p>No vendemos ni cedemos tus datos a terceros con fines comerciales.</p>

    <h2 class="h5 fw-bold mt-4">4. Quién ve tus datos</h2>
    <ul>
        <li>Los <strong>docentes</strong> de un aula ven los datos y la actividad de los alumnos de esa aula.</li>
        <li>Los <strong>alumnos</strong> ven el nombre de sus docentes y compañeros, y lo que se publica en el aula. No ven las notas ni las entregas de otros alumnos.</li>
        <li>Los <strong>administradores de una institución</strong> ven los docentes, alumnos, aulas y calificaciones de esa institución.</li>
        <li>Proveedores necesarios para funcionar: el servicio de alojamiento (servidor), el envío de emails y Mercado Pago para los pagos.</li>
    </ul>

    <h2 class="h5 fw-bold mt-4">5. Menores de edad</h2>
    <p>
        El Aula Virtual puede ser usado por alumnos menores de edad, siempre con la conformidad de su madre, padre o tutor, o
        en el marco de la institución educativa a la que asisten. Solo pedimos los datos mínimos necesarios para participar de las aulas.
    </p>

    <h2 class="h5 fw-bold mt-4">6. Cuánto tiempo los guardamos</h2>
    <p>
        Mientras la cuenta esté activa. Si pedís la baja, eliminamos o anonimizamos tus datos en un plazo razonable, salvo los que
        debamos conservar por obligaciones legales (por ejemplo, registros de facturación) o que formen parte del registro académico de una institución.
    </p>

    <h2 class="h5 fw-bold mt-4">7. Seguridad</h2>
    <p>
        Usamos conexión cifrada (HTTPS), contraseñas cifradas, archivos privados a los que solo acceden los miembros autorizados de cada aula,
        y copias de seguridad periódicas.
    </p>

    <h2 class="h5 fw-bold mt-4">8. Tus derechos</h2>
    <p>
        Podés pedir el <strong>acceso, rectificación, actualización o supresión</strong> de tus datos escribiendo a
        <a href="mailto:{{ $e['email'] }}">{{ $e['email'] }}</a>. El derecho de acceso puede ejercerse en forma gratuita a intervalos
        no menores a seis meses, salvo interés legítimo (art. 14, inc. 3, Ley 25.326).
    </p>
    <p class="small text-muted border rounded p-3">
        La AGENCIA DE ACCESO A LA INFORMACIÓN PÚBLICA, en su carácter de Órgano de Control de la Ley N° 25.326, tiene la
        atribución de atender las denuncias y reclamos que interpongan quienes resulten afectados en sus derechos por
        incumplimiento de las normas vigentes en materia de protección de datos personales.
    </p>

    <h2 class="h5 fw-bold mt-4">9. Cambios</h2>
    <p>Si modificamos esta política, publicaremos la nueva versión en esta página y, si el cambio es importante, lo avisaremos en el sistema o por email.</p>
@endsection
