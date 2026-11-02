{{-- Avatar redondo: foto o iniciales. Uso: @include('partials._avatar', ['u' => $user, 'tam' => 40, 'docente' => false]) --}}
@php($tam = $tam ?? 40)
@if ($u?->avatar_url)
    <img src="{{ $u->avatar_url }}" alt="" class="avatar-img" style="width: {{ $tam }}px; height: {{ $tam }}px">
@else
    <span class="avatar-img avatar-iniciales {{ ($docente ?? false) ? 'avatar-docente' : '' }}"
          style="width: {{ $tam }}px; height: {{ $tam }}px; font-size: {{ round($tam * .38) }}px">{{ $u?->iniciales ?? '?' }}</span>
@endif
