@extends('layouts.app')

@section('title', 'Panel LocalPro')

@section('content')
@php
    $f = fn ($v) => '$' . number_format((float) $v, 0, ',', '.');
    $max = max(1, $meses->max('total'));
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Hola, <span>{{ strtok(auth()->user()->name, ' ') }}</span></h1>
            <span class="text-muted fw-semibold">{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM')) }}</span></div>
        <div class="lp-quick">
            <a href="{{ route('admin.tiendas.index') }}" class="btn btn-dark"><i class="fa-solid fa-store me-1"></i>Empresas</a>
            <a href="{{ route('admin.red.index') }}" class="btn btn-primary"><i class="fa-solid fa-sitemap me-1"></i>Mi red</a>
        </div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="lbl">Ingresos este mes</div><div class="val">{{ $f($kpi['ingresosMes']) }}</div><div class="sub">Este año {{ $f($kpi['ingresosAnio']) }} · total {{ $f($kpi['ingresosTotal']) }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-store"></i></div><div class="lbl">Empresas inscritas</div><div class="val">{{ $kpi['tiendas'] }}</div><div class="sub">{{ $kpi['nuevasMes'] }} nuevas este mes · {{ $kpi['activas'] }} activas · {{ $kpi['configurando'] }} configurando</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-triangle-exclamation"></i></div><div class="lbl">Por cobrar</div><div class="val">{{ $kpi['vencidas'] }}</div><div class="sub">Planes vencidos · {{ $kpi['pagando'] }} al día · {{ $kpi['gratis'] }} en plan gratis</div></div>
    </div>

    <div class="row g-3 mb-3">
        @php $tarjetas = [
            ['Usuarios en el sistema', $kpi['usuarios'], 'fa-users', route('admin.usuarios')],
            ['Personas en la red', $kpi['red'], 'fa-sitemap', route('admin.red.index')],
            ['Comisiones por pagar', $f($kpi['comisionesPendientes']), 'fa-coins', route('admin.red.comisiones')],
            ['Ventas hoy en las tiendas', $kpi['ventasHoy'] . ' · ' . $f($kpi['vendidoHoy']), 'fa-cash-register', route('admin.tiendas.index')],
            ['Pagos pendientes', $kpi['pagosPendientes'], 'fa-hourglass-half', route('admin.pagos', ['estado' => 'pendiente'])],
            ['Empresas suspendidas', $kpi['suspendidas'], 'fa-ban', route('admin.tiendas.index', ['estado' => 'suspendida'])],
        ]; @endphp
        @foreach ($tarjetas as [$t, $v, $i, $u])
        <div class="col-6 col-lg-4 col-xl-2">
            <a href="{{ $u }}" class="lp-box d-block text-decoration-none h-100" style="color:inherit;padding:14px">
                <i class="fa-solid {{ $i }}" style="color:#16b34a"></i>
                <div class="fw-bold mt-1" style="font-size:1.2rem">{{ $v }}</div>
                <small class="text-muted fw-semibold">{{ $t }}</small>
            </a>
        </div>
        @endforeach
    </div>

    <div class="lp-grid mb-3">
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-chart-column me-1"></i> Ingresos por mes</span><a href="{{ route('admin.pagos') }}" class="small">Ver pagos</a></h3>
            <div style="display:flex;align-items:flex-end;gap:10px;height:170px;padding-top:10px">
                @foreach ($meses as $m)
                <div style="flex:1;text-align:center;display:flex;flex-direction:column;justify-content:flex-end;height:100%">
                    <small class="fw-bold" style="font-size:.7rem">{{ $m['total'] ? $f($m['total']) : '' }}</small>
                    <div style="height:{{ max(3, $m['total'] / $max * 120) }}px;background:linear-gradient(180deg,#a6f34a,#16b34a);border-radius:8px 8px 3px 3px;margin-top:4px"></div>
                    <small class="text-muted fw-semibold mt-1">{{ $m['label'] }}</small>
                </div>
                @endforeach
            </div>
        </div>
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-bell me-1"></i> Empresas por cobrar</span><small class="text-muted">vencidas o vencen en 10 días</small></h3>
            <ul class="lp-list">
                @forelse ($porCobrar as $t)
                <li>
                    <div class="av" style="{{ $t->plan_vencido ? 'background:#ffe9f3;color:#b0206a' : '' }}"><i class="fa-solid {{ $t->plan_vencido ? 'fa-circle-exclamation' : 'fa-hourglass-half' }}"></i></div>
                    <div class="t"><b><a href="{{ route('admin.tiendas.show', $t) }}" class="text-decoration-none" style="color:inherit">{{ $t->nombre }}</a></b>
                        <small>{{ $t->plan->nombre ?? '' }} · {{ $t->plan_vencido ? 'venció' : 'vence' }} el {{ $t->plan_vence->format('d/m/Y') }}{{ $t->asesor ? ' · asesor ' . $t->asesor->nombre : '' }}</small></div>
                    @if ($t->telefono)<a class="btn btn-sm btn-success" target="_blank" href="https://wa.me/{{ preg_replace('/\D/', '', \App\Support\Paises::datos($t->pais)['indicativo'] . ltrim(preg_replace('/\D/', '', $t->telefono), '0')) }}?text={{ rawurlencode('Hola, le escribimos de LocalPro: el plan de ' . $t->nombre . ($t->plan_vencido ? ' venció' : ' vence') . ' el ' . $t->plan_vence->format('d/m/Y') . '. ¿Le ayudamos a renovarlo?') }}"><i class="fa-brands fa-whatsapp"></i></a>@endif
                </li>
                @empty
                <li class="lp-empty">Nadie por cobrar en este momento. ✅</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="lp-grid">
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-store me-1"></i> Últimas empresas inscritas</span><a href="{{ route('admin.tiendas.index') }}" class="small">Ver todas</a></h3>
            <ul class="lp-list">
                @forelse ($nuevas as $t)
                <li><div class="av"><i class="fa-solid {{ $t->tipo_icono }}"></i></div>
                    <div class="t"><b><a href="{{ route('admin.tiendas.show', $t) }}" class="text-decoration-none" style="color:inherit">{{ $t->nombre }}</a></b><small>{{ $t->ciudad ?: 'Sin ciudad' }} · {{ $t->plan->nombre ?? 'Sin plan' }} · {{ $t->created_at->locale('es')->diffForHumans() }}{{ $t->asesor ? ' · ' . $t->asesor->nombre : '' }}</small></div>
                    @unless ($t->onboarding_completo)<span class="badge bg-warning text-dark">Configurando</span>@endunless</li>
                @empty
                <li class="lp-empty">Todavía no hay empresas.</li>
                @endforelse
            </ul>
        </div>
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-file-invoice-dollar me-1"></i> Últimos pagos</span><a href="{{ route('admin.pagos') }}" class="small">Ver todos</a></h3>
            <ul class="lp-list">
                @forelse ($ultimosPagos as $p)
                <li><div class="av"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                    <div class="t"><b>{{ $p->monto_texto }} · {{ $p->tienda->nombre ?? '—' }}</b><small>{{ $p->plan->nombre ?? '' }} · {{ ucfirst($p->metodo) }} · {{ $p->created_at->format('d/m/Y') }}</small></div>
                    <span class="badge {{ $p->estado === 'aprobado' ? 'bg-success' : ($p->estado === 'pendiente' ? 'bg-warning text-dark' : 'bg-danger') }}">{{ ucfirst($p->estado) }}</span></li>
                @empty
                <li class="lp-empty">Sin pagos todavía.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
