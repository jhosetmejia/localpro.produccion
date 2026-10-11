@extends('layouts.app')

@section('title', 'Mi panel de asesor')

@section('content')
@php $f = fn ($v) => '$' . number_format((float) $v, 0, ',', '.'); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div>
            <small><i class="fa-solid {{ $yo->cargo_icono }} me-1"></i>{{ $yo->cargo_nombre }}{{ $yo->zona ? ' · ' . $yo->zona : '' }}{{ $yo->ciudad ? ' · ' . $yo->ciudad : '' }}</small>
            <h1>Hola, <span>{{ strtok($yo->nombre, ' ') }}</span></h1>
            @if ($yo->padre)<span class="text-muted fw-semibold">Su líder: {{ $yo->padre->nombre }} ({{ $yo->padre->cargo_nombre }})</span>@endif
        </div>
        <div class="lp-quick"><a href="{{ route('asesor.simulador') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-calculator me-1"></i>Simulador para reclutar</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="lbl">Comisión por cobrar</div><div class="val">{{ $f($resumen['pendiente']) }}</div><div class="sub">Este mes ganó {{ $f($resumen['mes']) }} · ya le pagaron {{ $f($resumen['pagada']) }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-store"></i></div><div class="lbl">Mis negocios</div><div class="val">{{ $negocios->count() }}</div><div class="sub">{{ $yo->cargo === 'asesor' ? 'Negocios que usted inscribió' : $resumen['negociosRed'] . ' en toda su red' }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-users"></i></div><div class="lbl">Mi equipo</div><div class="val">{{ $resumen['equipo'] }}</div><div class="sub">{{ $yo->cargo === 'lider_zona' ? 'Asesores de su zona (máximo ' . \App\Models\Asesor::MAX_ASESORES_ZONA . ')' : ($yo->cargo === 'asesor' ? 'Personas a su cargo' : 'Líderes y asesores debajo de usted') }}</div></div>
    </div>

    <div class="lp-box mb-3">
        <h3><span><i class="fa-solid fa-link me-1"></i> Su enlace para inscribir negocios</span></h3>
        <p class="text-muted small mb-2">El negocio que se registre con este enlace, o escriba su código <b>{{ $yo->codigo }}</b>, queda a su nombre y usted gana la comisión cuando pague su plan anual.</p>
        <div class="lp-link">
            <code id="miEnlace">{{ $yo->enlace }}</code>
            <button type="button" class="btn btn-dark btn-sm" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('miEnlace').textContent.trim()); LP.toast('Enlace copiado')"><i class="fa-regular fa-copy me-1"></i>Copiar</button>
            <a class="btn btn-success btn-sm" target="_blank" href="https://wa.me/?text={{ rawurlencode('¡Hola! Te recomiendo LocalPro para manejar tu negocio: ventas, caja, inventario y catálogo desde el celular. Inscríbete aquí: ' . $yo->enlace) }}"><i class="fa-brands fa-whatsapp me-1"></i>Compartir</a>
        </div>
    </div>

    <div class="lp-grid mb-3">
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-store me-1"></i> Mis negocios</span></h3>
            <ul class="lp-list">
                @forelse ($negocios as $t)
                <li>
                    <div class="av"><i class="fa-solid {{ $t->tipo_icono }}"></i></div>
                    <div class="t"><b>{{ $t->nombre }}</b><small>{{ $t->ciudad ?: 'Sin ciudad' }} · {{ $t->plan->nombre ?? 'Sin plan' }}{{ $t->plan_vence ? ' · ' . ($t->plan_vence->isPast() ? 'venció ' : 'vence ') . $t->plan_vence->format('d/m/Y') : '' }}</small></div>
                    @if ($t->telefono)<a class="btn btn-sm btn-light" target="_blank" href="https://wa.me/{{ numero_whatsapp($t->telefono) }}" title="Escribir por WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>@endif
                </li>
                @empty
                <li class="lp-empty">Aún no tiene negocios. Comparta su enlace para empezar.</li>
                @endforelse
            </ul>
        </div>
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-rotate me-1"></i> Para renovar</span></h3>
            <p class="text-muted small">Negocios cuyo plan vence en los próximos 30 días. Al renovar usted también gana comisión.</p>
            <ul class="lp-list">
                @forelse ($porVencer as $t)
                <li><div class="av"><i class="fa-solid fa-hourglass-half"></i></div><div class="t"><b>{{ $t->nombre }}</b><small>{{ $t->plan_vence->isPast() ? 'Venció' : 'Vence' }} el {{ $t->plan_vence->format('d/m/Y') }}</small></div>
                    @if ($t->telefono)<a class="btn btn-sm btn-success" target="_blank" href="https://wa.me/{{ numero_whatsapp($t->telefono) }}?text={{ rawurlencode('Hola, le escribo de LocalPro: su plan vence el ' . $t->plan_vence->format('d/m/Y') . '. ¿Le ayudo a renovarlo?') }}"><i class="fa-brands fa-whatsapp"></i></a>@endif
                </li>
                @empty
                <li class="lp-empty">Nada por renovar por ahora.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="lp-box">
        <h3><span><i class="fa-solid fa-coins me-1"></i> Últimas comisiones</span><a href="{{ route('asesor.comisiones') }}" class="small">Ver todas</a></h3>
        <ul class="lp-list">
            @forelse ($ultimas as $c)
            <li><div class="av"><i class="fa-solid fa-coins"></i></div><div class="t"><b>{{ $f($c->monto) }} · {{ $c->tienda->nombre ?? 'Negocio' }}</b><small>{{ $c->concepto_nombre }} · {{ $c->tipo === 'nueva' ? 'venta nueva' : 'renovación' }} · {{ $c->created_at->format('d/m/Y') }}</small></div>
                <span class="badge {{ $c->estado === 'pagada' ? 'bg-success' : ($c->estado === 'pendiente' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ ucfirst($c->estado) }}</span></li>
            @empty
            <li class="lp-empty">Todavía no tiene comisiones. Se generan cuando sus negocios pagan el plan.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
