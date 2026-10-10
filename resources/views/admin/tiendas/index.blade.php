@extends('layouts.app')

@section('title', 'Tiendas inscritas')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Tiendas <span>inscritas</span></h1></div>
        <div class="lp-quick"><a href="{{ route('admin.tiendas.create') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-plus me-1"></i>Crear tienda</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-counts mb-3" style="grid-template-columns:repeat(5,1fr)">
        <div class="lp-count"><div class="ci"><i class="fa-solid fa-store"></i></div><b>{{ $resumen['total'] }}</b><small>Tiendas</small></div>
        <div class="lp-count"><div class="ci"><i class="fa-solid fa-circle-check"></i></div><b>{{ $resumen['activas'] }}</b><small>Activas</small></div>
        <div class="lp-count"><div class="ci"><i class="fa-solid fa-seedling"></i></div><b>{{ $resumen['nuevas_mes'] }}</b><small>Nuevas este mes</small></div>
        <div class="lp-count"><div class="ci"><i class="fa-solid fa-gears"></i></div><b>{{ $resumen['configurando'] }}</b><small>Configurando</small></div>
        <div class="lp-count"><div class="ci"><i class="fa-solid fa-receipt"></i></div><b>{{ $resumen['ventas_hoy'] }}</b><small>Ventas hoy (todas)</small></div>
    </div>

    <div class="lp-box">
        <form class="row g-2 mb-3" method="get">
            <div class="col-md-7"><input name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por nombre, correo o ciudad"></div>
            <div class="col-md-3">
                <select name="estado" class="form-select" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <option value="activa" @selected(request('estado') === 'activa')>Activas</option>
                    <option value="suspendida" @selected(request('estado') === 'suspendida')>Suspendidas</option>
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-dark">Buscar</button></div>
        </form>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Tienda</th><th>Dueño</th><th>País</th><th>Productos</th><th>Ventas</th><th>Última venta</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse ($tiendas as $t)
                    @php $v = $ventas[$t->id] ?? null; $m = \App\Support\Paises::datos($t->pais); $d = $duenos[$t->id][0] ?? null; @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="lp-avatar" style="border-radius:12px;width:40px;height:40px;flex:none">@if($t->logo_url)<img src="{{ $t->logo_url }}" style="width:100%;height:100%;object-fit:cover;border-radius:12px">@else<i class="fa-solid {{ $t->tipo_icono }}"></i>@endif</span>
                                <div><a href="{{ route('admin.tiendas.show', $t) }}" class="fw-bold text-decoration-none" style="color:inherit">{{ $t->nombre }}</a><br><small class="text-muted">{{ $t->tipo_nombre }}{{ $t->ciudad ? ' · ' . $t->ciudad : '' }}</small></div>
                            </div>
                        </td>
                        <td>{{ $d?->name ?? '—' }}<br><small class="text-muted">{{ $d?->email }}</small></td>
                        <td>{{ $m['nombre'] }}<br><small class="text-muted">{{ $m['moneda'] }}</small></td>
                        <td>{{ $productos[$t->id] ?? 0 }}</td>
                        <td>{{ $v->n ?? 0 }}<br><small class="text-muted">{{ $m['simbolo'] }}{{ number_format((float) ($v->total ?? 0), $m['decimales'], $m['decimal'], $m['miles']) }}</small></td>
                        <td class="text-nowrap"><small>{{ $v?->ultima ? \Carbon\Carbon::parse($v->ultima)->diffForHumans() : '—' }}</small></td>
                        <td>
                            @if (!$t->onboarding_completo)<span class="badge bg-warning text-dark">Configurando</span>
                            @elseif ($t->activa)<span class="badge bg-success">Activa</span>
                            @else<span class="badge bg-danger">Suspendida</span>@endif
                        </td>
                        <td class="text-end text-nowrap">
                            <form method="post" action="{{ route('admin.tiendas.entrar', $t) }}" class="d-inline">@csrf<button class="btn btn-sm btn-primary" title="Entrar a la tienda"><i class="fa-solid fa-right-to-bracket"></i></button></form>
                            <a href="{{ route('admin.tiendas.show', $t) }}" class="btn btn-sm btn-dark"><i class="fa-solid fa-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="lp-empty">Aún no hay tiendas inscritas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
