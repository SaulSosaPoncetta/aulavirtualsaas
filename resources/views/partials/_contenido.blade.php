{{-- Muestra contenido del editor (HTML ya limpiado al guardar) o texto plano viejo.
     Uso: @include('partials._contenido', ['texto' => $clase->contenido]) --}}
@php($c = $texto ?? '')
@if (\App\Services\HtmlSeguro::esHtml($c))
    <div class="contenido-html">{!! $c !!}</div>
@else
    @include('partials._texto', ['texto' => $c])
@endif
