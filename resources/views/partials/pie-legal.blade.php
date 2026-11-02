{{-- Pie con enlaces legales (landing, páginas públicas y sistema) --}}
<footer class="landing-pie py-4">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3 small">
        <span><i class="bi bi-mortarboard-fill text-warning me-1"></i><strong>Aula Virtual</strong> · un sistema de
            <a href="https://www.migestion.com.ar" class="text-reset fw-semibold">{{ config('empresa.marca') }}</a> © {{ date('Y') }}</span>
        <span class="d-flex flex-wrap gap-3">
            <a href="{{ route('legal.terminos') }}" class="text-reset">Términos y condiciones</a>
            <a href="{{ route('legal.privacidad') }}" class="text-reset">Privacidad</a>
            <a href="mailto:{{ config('empresa.email') }}" class="text-reset"><i class="bi bi-envelope me-1"></i>{{ config('empresa.email') }}</a>
        </span>
    </div>
</footer>
