<!DOCTYPE html>
<html lang="es">
<head>
@include('citas._estilo')
<title>{{ $p->nombres }} · {{ $t->nombre }}</title>
<meta name="robots" content="noindex, nofollow">
<meta name="referrer" content="no-referrer">
</head>
<body>
<div class="pg">
    @include('citas._cab')
    <div class="card" style="background:linear-gradient(135deg,#0e7490,#155e75);color:#fff;border:0">
        <div style="font-size:.8rem;font-weight:800;color:#a5f3fc;text-transform:uppercase;letter-spacing:.06em">Mi portal de paciente</div>
        <div style="font-size:1.35rem;font-weight:800;margin-top:4px">Hola, {{ $p->nombres }}</div>
        <div style="color:#cffafe;font-size:.88rem;margin-top:4px">Aquí ve sus citas, sus tratamientos y lo que lleva pagado. Este enlace es personal: no lo comparta.</div>
        @if ($agenda)<a class="btn" style="background:#fff;color:#0e7490;margin-top:14px" href="{{ route('citas.publica', $t->slug) }}"><i class="fa-solid fa-calendar-plus"></i>Pedir otra cita</a>@endif
    </div>

    <div class="card">
        <h2><i class="fa-solid fa-calendar-days"></i>Mis citas</h2>
        @forelse ($citas as $c)
        @php [$en, $ec] = \App\Models\Cita::ESTADOS[$c->estado]; $futura = $c->activa && $c->fecha->gte(today()); @endphp
        <div class="fila">
            <div><b>{{ ucfirst($c->fecha->translatedFormat('D j M Y')) }} · {{ $c->hora_corta }}</b><div class="muted">{{ $c->motivo ?: 'Consulta' }}</div></div>
            <div style="text-align:right"><span class="est" style="background:{{ $ec }}">{{ $en }}</span>
                @if ($futura)<div><a href="{{ route('citas.confirmar', $c->token) }}" style="font-size:.8rem;font-weight:800;color:#0e7490">Confirmar o cancelar</a></div>@endif</div>
        </div>
        @empty<div class="muted">No tiene citas recientes.</div>@endforelse
    </div>

    @if ($planes->count())
    <div class="card">
        <h2><i class="fa-solid fa-file-invoice-dollar"></i>Mis tratamientos</h2>
        @foreach ($planes as $pl)
        @php [$en, $ec] = \App\Models\TratamientoPlan::ESTADOS[$pl->estado]; @endphp
        <div class="fila" style="display:block">
            <div style="display:flex;justify-content:space-between;gap:8px"><b>{{ $pl->nombre }}</b><span class="est" style="background:{{ $ec }}">{{ $en }}</span></div>
            <div class="muted">{{ $pl->numero }} · Total {{ dinero($pl->total) }} · Pagado {{ dinero($pl->abonado) }} · <b style="color:{{ $pl->saldo > 0 ? '#be123c' : '#15803d' }}">Saldo {{ dinero($pl->saldo) }}</b></div>
            @if ($pl->es_ortodoncia && $pl->atraso > 0)<div class="muted" style="color:#be123c;font-weight:700">Tiene {{ $pl->cuotas_atrasadas }} cuota(s) pendiente(s) por {{ dinero($pl->atraso) }}.</div>@endif
            <div class="bar"><i style="width:{{ $pl->avance }}%"></i></div>
            <div class="muted" style="font-size:.78rem;margin-top:4px">Avance del tratamiento: {{ $pl->avance }}% · <a href="{{ route('paciente.plan', [$token, $pl->id]) }}" style="color:#0e7490;font-weight:800">Ver detalle</a></div>
            @if ($pl->abonos->count())
            <details style="margin-top:6px"><summary class="muted" style="cursor:pointer">Mis pagos ({{ $pl->abonos->count() }})</summary>
                @foreach ($pl->abonos as $ab)<div class="muted" style="display:flex;justify-content:space-between"><span>{{ $ab->created_at->format('d/m/Y') }} · {{ $ab->numero }}</span><b>{{ dinero($ab->monto) }}</b></div>@endforeach
            </details>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    @if ($consent->count())
    <div class="card">
        <h2><i class="fa-solid fa-file-signature"></i>Mis consentimientos firmados</h2>
        @foreach ($consent as $c)
        <a class="fila" style="text-decoration:none;color:inherit" href="{{ route('paciente.consentimiento', [$token, $c->id]) }}"><span><b>{{ $c->titulo }}</b><div class="muted">{{ $c->procedimiento }}</div></span><span class="muted">{{ $c->firmado_at?->format('d/m/Y') }} <i class="fa-solid fa-chevron-right"></i></span></a>
        @endforeach
    </div>
    @endif
    <div class="pie">{{ $t->nombre }}{{ $t->telefono ? ' · ' . $t->telefono : '' }} · Hecho con <a href="{{ url('/') }}">LocalProo</a></div>
</div>
</body>
</html>
