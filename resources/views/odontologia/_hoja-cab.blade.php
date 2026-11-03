<div class="cab">
    @if ($tienda->logo_url)<img src="{{ $tienda->logo_url }}" alt="">@endif
    <div class="neg"><b>{{ $tienda->nombre }}</b>
        @if ($tienda->nit)<span>NIT {{ $tienda->nit }}</span>@endif
        <span>{{ trim(implode(' · ', array_filter([$tienda->direccion, $tienda->ciudad, $tienda->telefono]))) }}</span></div>
    <div class="tit"><b>{{ $titulo }}</b><span>{{ $subtitulo ?? '' }}</span></div>
</div>
<div class="cuerpo">
<div class="pac">
    <div class="ancho"><small>Paciente</small><b>{{ $p->nombre_completo }}</b></div>
    <div><small>Documento</small><b>{{ trim($p->tipo_documento . ' ' . $p->documento) ?: '—' }}</b></div>
    <div><small>Edad</small><b>{{ $p->edad ?: '—' }}</b></div>
    <div><small>Celular</small><b>{{ $p->telefono ?: '—' }}</b></div>
    <div><small>EPS / prepagada</small><b>{{ $p->aseguradora ?: '—' }}</b></div>
    <div class="ancho"><small>Fecha</small><b>{{ $fecha ?? now()->format('d/m/Y') }}</b></div>
</div>
