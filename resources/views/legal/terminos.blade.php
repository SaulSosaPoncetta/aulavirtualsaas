@extends('layouts.publico')

@section('titulo', 'Términos y condiciones')

@section('contenido')
    @php($e = config('empresa'))

    <h1 class="fw-bold mb-1">Términos y condiciones de uso</h1>
    <p class="text-muted">Última actualización: {{ $e['legales_actualizados'] }}</p>

    <h2 class="h5 fw-bold mt-4">1. Quiénes somos</h2>
    <p>
        Aula Virtual es un servicio de {{ $e['razon_social'] }}{{ $e['cuit'] ? ', CUIT ' . $e['cuit'] : '' }}{{ $e['domicilio'] ? ', con domicilio en ' . $e['domicilio'] : '' }}
        (en adelante, "{{ $e['marca'] }}"), que ofrece una plataforma online para que docentes e instituciones educativas publiquen
        contenidos, tareas y evaluaciones, y para que sus alumnos accedan a ellos. Contacto: <a href="mailto:{{ $e['email'] }}">{{ $e['email'] }}</a>.
    </p>

    <h2 class="h5 fw-bold mt-4">2. Aceptación</h2>
    <p>
        Al crear una cuenta o usar el servicio aceptás estos términos y la <a href="{{ route('legal.privacidad') }}">Política de privacidad</a>.
        Si no estás de acuerdo, no uses el servicio. Las personas menores de 18 años deben contar con la conformidad de su madre, padre o
        tutor. Cuando una institución da de alta a sus alumnos, es la institución la que garantiza contar con esa conformidad.
    </p>

    <h2 class="h5 fw-bold mt-4">3. Cuentas</h2>
    <ul>
        <li>Los datos que cargás tienen que ser verdaderos. Sos responsable de mantener la confidencialidad de tu contraseña y de lo que se haga con tu cuenta.</li>
        <li>Hay tres tipos de uso: <strong>docente</strong> (crea y administra aulas), <strong>alumno</strong> (participa de las aulas a las que se une) e <strong>institución</strong> (administra a sus docentes, alumnos y aulas).</li>
        <li>Podemos suspender o dar de baja cuentas que incumplan estos términos o usen el servicio de forma abusiva.</li>
    </ul>

    <h2 class="h5 fw-bold mt-4">4. Contenido que se publica</h2>
    <ul>
        <li>El contenido que suben docentes, alumnos e instituciones (clases, archivos, tareas, respuestas, comentarios) es de quien lo publica. {{ $e['marca'] }} solo lo almacena y lo muestra a los miembros del aula para prestar el servicio.</li>
        <li>No está permitido publicar contenido ilegal, ofensivo, discriminatorio, que viole derechos de autor o de terceros, ni datos personales de otras personas sin autorización.</li>
        <li>Los docentes y las instituciones son responsables de moderar el contenido de sus aulas. Podemos retirar contenido que viole estos términos.</li>
    </ul>

    <h2 class="h5 fw-bold mt-4">5. Abonos y pagos</h2>
    <ul>
        <li>El uso para <strong>alumnos es gratuito</strong>. Los docentes que usan el servicio por su cuenta pagan un <strong>abono unipersonal</strong> mensual, y las instituciones un <strong>abono institucional</strong> según la cantidad de docentes contratados.</li>
        <li>Los precios vigentes se publican en el sitio. Pueden cambiar; el cambio se aplica a los pagos siguientes y no afecta los períodos ya abonados.</li>
        <li>Los pagos se realizan por adelantado, por mes o por varios meses, mediante Mercado Pago o transferencia. No hay permanencia mínima: si no renovás, el abono simplemente vence.</li>
        <li>Al vencer el abono, las aulas que cubre quedan en <strong>modo solo lectura</strong>: el contenido y las calificaciones se conservan y se pueden consultar, pero no se pueden publicar contenidos ni entregas nuevas hasta renovar.</li>
        <li>Podés ejercer el derecho de revocación dentro de los 10 días corridos desde la contratación, conforme a la Ley 24.240 de Defensa del Consumidor, escribiendo a {{ $e['email'] }}.</li>
    </ul>

    <h2 class="h5 fw-bold mt-4">6. Disponibilidad del servicio</h2>
    <p>
        Hacemos lo posible para que el servicio esté disponible y funcione correctamente, y realizamos copias de seguridad periódicas.
        Puede haber interrupciones por mantenimiento o causas ajenas a nuestro control. Te recomendamos conservar una copia de los
        materiales importantes y exportar las calificaciones al cierre de cada período.
    </p>

    <h2 class="h5 fw-bold mt-4">7. Responsabilidad</h2>
    <p>
        {{ $e['marca'] }} no es responsable por el contenido publicado por los usuarios, ni por las decisiones pedagógicas o
        calificaciones que tomen docentes e instituciones. Nuestra responsabilidad se limita, en la medida que lo permita la ley,
        al importe abonado por el servicio en los últimos tres meses.
    </p>

    <h2 class="h5 fw-bold mt-4">8. Baja de la cuenta</h2>
    <p>
        Podés pedir la baja de tu cuenta en cualquier momento escribiendo a {{ $e['email'] }}. Las calificaciones y entregas
        que formen parte del registro de una institución pueden conservarse por ésta según sus propias obligaciones.
    </p>

    <h2 class="h5 fw-bold mt-4">9. Cambios en estos términos</h2>
    <p>Podemos actualizar estos términos. Si el cambio es importante, lo avisaremos en el sistema o por email antes de que entre en vigencia.</p>

    <h2 class="h5 fw-bold mt-4">10. Ley aplicable</h2>
    <p>Estos términos se rigen por las leyes de la República Argentina. Para cualquier controversia son competentes los tribunales ordinarios de {{ $e['jurisdiccion'] }}, sin perjuicio de los derechos que te otorga la ley de defensa del consumidor.</p>
@endsection
