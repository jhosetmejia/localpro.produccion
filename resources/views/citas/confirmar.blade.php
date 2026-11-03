<!DOCTYPE html>
<html lang="es">
<head>
@include('citas._estilo')
<title>Su cita · {{ $t->nombre }}</title>
<meta name="robots" content="noindex">
</head>
<body>
@php [$en, $ec] = \App\Models\Cita::ESTADOS[$c->estado]; @endphp
<div class="pg">
    @include('citas._cab')
    @if (session('nueva'))<div class="ok"><i class="fa-solid fa-circle-check"></i> ¡Listo, {{ $c->paciente?->nombres }}! Su cita quedó pedida. Guarde este enlace para confirmarla o cancelarla.</div>@endif
    @if (session('aviso'))<div class="ok"><i class="fa-solid fa-circle-info"></i> {{ session('aviso') }}</div>@endif
    <div class="card" style="text-align:center">
        <div style="width:64px;height:64px;border-radius:20px;margin:0 auto 10px;display:grid;place-items:center;background:#ecfeff;color:#0891b2;font-size:1.7rem"><i class="fa-solid fa-calendar-check"></i></div>
        <div class="muted">Cita de {{ $c->paciente?->nombre_completo }}</div>
        <div style="font-size:1.45rem;font-weight:800;margin:6px 0">{{ ucfirst($c->fecha->translatedFormat('l j \d\e F')) }}</div>
        <div style="font-size:1.2rem;font-weight:800;color:#0e7490">{{ $c->hora_corta }}</div>
        @if ($c->motivo)<div class="muted" style="margin-top:6px">{{ $c->motivo }}</div>@endif
        <div style="margin-top:10px"><span class="est" style="background:{{ $ec }}">{{ $en }}</span></div>
        @if ($c->activa && !$pasada)
        <div class="g2" style="margin-top:18px">
            @if ($c->estado !== 'confirmada')
            <form method="post" action="{{ route('citas.responder', $c->token) }}">@csrf<input type="hidden" name="accion" value="confirmar"><button class="btn prim"><i class="fa-solid fa-check"></i>Confirmo que voy</button></form>
            @else<div class="ok" style="margin:0"><i class="fa-solid fa-check"></i> Confirmada</div>@endif
            <div>
                <button type="button" class="btn peligro" style="width:100%" onclick="this.style.display='none';document.getElementById('cancelar').style.display=''"><i class="fa-solid fa-xmark"></i>Cancelar la cita</button>
                <form method="post" action="{{ route('citas.responder', $c->token) }}" id="cancelar" style="display:none">@csrf<input type="hidden" name="accion" value="cancelar">
                    <div class="muted" style="margin-bottom:6px">¿Seguro que la cancela?</div><button class="btn peligro" style="width:100%">Sí, cancelar mi cita</button></form>
            </div>
        </div>
        @endif
    </div>
    <div class="card">
        <h2><i class="fa-solid fa-location-dot"></i>Dónde</h2>
        <div><b>{{ $t->nombre }}</b></div>
        <div class="muted">{{ trim(implode(' · ', array_filter([$t->direccion, $t->ciudad]))) }}</div>
        @if ($t->telefono)
        <a class="btn sec" style="margin-top:12px" href="https://wa.me/{{ preg_replace('/\D/', '', strlen(preg_replace('/\D/', '', $t->telefono)) === 10 ? '57' . $t->telefono : $t->telefono) }}" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i>Escribir al consultorio</a>
        @endif
    </div>
    <div class="pie">Hecho con <a href="{{ url('/') }}">LocalProo</a></div>
</div>
</body>
</html>
