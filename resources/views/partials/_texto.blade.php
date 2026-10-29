{{-- Texto escapado, con saltos de línea y enlaces clickeables. Uso: @include('partials._texto', ['texto' => $x]) --}}
{!! nl2br(preg_replace('~(https?://[^\s<]+)~', '<a href="$1" target="_blank" rel="noopener">$1</a>', e($texto))) !!}
