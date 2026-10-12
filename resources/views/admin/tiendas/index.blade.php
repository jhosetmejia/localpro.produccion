@extends('layouts.app')

@section('title', 'Empresas inscritas')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Empresas <span>inscritas</span></h1></div>
        <div class="lp-quick"><a href="{{ route('admin.planes.index') }}" class="btn btn-dark"><i class="fa-solid fa-gem me-1"></i>Planes y cobros</a><a href="{{ route('admin.tiendas.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Crear tienda</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-counts mb-3" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr))">
        <div class="lp-count"><div class="ci"><i class="fa-solid fa-store"></i></div><b>{{ $resumen['total'] }}</b><small>Empresas</small></div>
        <div class="lp-count"><div class="ci"><i class="fa-solid fa-circle-check"></i></div><b>{{ $resumen['activas'] }}</b><small>Activas</small></div>
        <div class="lp-count"><div class="ci"><i class="fa-solid fa-seedling"></i></div><b>{{ $resumen['nuevas_mes'] }}</b><small>Nuevas este mes</small></div>
        <div class="lp-count"><div class="ci"><i class="fa-solid fa-gears"></i></div><b>{{ $resumen['configurando'] }}</b><small>Configurando</small></div>
        <a href="{{ request()->fullUrlWithQuery(['asesor' => request('asesor') === 'sin' ? null : 'sin']) }}" class="lp-count text-decoration-none" style="color:inherit;{{ request('asesor') === 'sin' ? 'border-color:#16b34a;background:#f2fbf3' : '' }}"><div class="ci"><i class="fa-solid fa-user-slash"></i></div><b>{{ $resumen['sin_asesor'] }}</b><small>Sin asesor {{ request('asesor') === 'sin' ? '· viendo' : '' }}</small></a>
        <div class="lp-count"><div class="ci"><i class="fa-solid fa-receipt"></i></div><b>{{ $resumen['ventas_hoy'] }}</b><small>Ventas hoy (todas)</small></div>
    </div>

    <div class="lp-box">
        <form class="row g-2 mb-3" method="get">
            <div class="col-md-5"><input name="q" value="{{ $q }}" class="form-control" placeholder="Buscar por nombre, código (LCT-00001), correo, ciudad o código de asesor"></div>
            <div class="col-6 col-md-2">
                <select name="asesor" class="form-select" onchange="this.form.submit()">
                    <option value="">Con y sin asesor</option>
                    <option value="sin" @selected(request('asesor') === 'sin')>Sin asesor</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="estado" class="form-select" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <option value="activa" @selected(request('estado') === 'activa')>Activas</option>
                    <option value="suspendida" @selected(request('estado') === 'suspendida')>Suspendidas</option>
                </select>
            </div>
            <div class="col-md-2 d-grid"><button class="btn btn-dark">Buscar</button></div>
        </form>
        {{-- Celular: tarjetas --}}
        <ul class="lp-list d-md-none">
            @forelse ($tiendas as $t)
            @php $v = $ventas[$t->id] ?? null; $d = $duenos[$t->id][0] ?? null; @endphp
            <li style="flex-wrap:wrap">
                <div class="av"><i class="fa-solid {{ $t->tipo_icono }}"></i></div>
                <div class="t" style="min-width:0">
                    <b><a href="{{ route('admin.tiendas.show', $t) }}" class="text-decoration-none" style="color:inherit">{{ $t->nombre }}</a></b>
                    <small class="lp-cod">{{ $t->codigo }}</small>
                    <small>{{ $d?->name ?? 'Sin dueño' }}{{ $t->ciudad ? ' · ' . $t->ciudad : '' }} · {{ $t->plan->nombre ?? 'Sin plan' }}@if($t->plan_vence) · <span class="{{ $t->plan_vencido ? 'text-danger fw-bold' : '' }}">{{ $t->plan_vencido ? 'venció' : 'vence' }} {{ $t->plan_vence->format('d/m/y') }}</span>@endif · {{ $v->n ?? 0 }} ventas</small>
                </div>
                <div class="w-100 d-flex align-items-center gap-2 mt-1 ps-5">
                    @if ($t->asesor)<small class="text-muted"><i class="fa-solid fa-user-tie me-1"></i>{{ $t->asesor->nombre }} · {{ $t->asesor->codigo }}</small>@else<small class="text-warning fw-bold"><i class="fa-solid fa-user-slash me-1"></i>Sin asesor</small>@endif
                    <button type="button" class="btn btn-sm btn-asg ms-auto" data-asignar="{{ route('admin.tiendas.asesor', $t) }}" data-tienda="{{ $t->nombre }}" data-actual="{{ $t->asesor_id }}">{{ $t->asesor ? 'Cambiar' : 'Asignar' }}</button>
                </div>
                <div class="d-flex gap-1 align-items-center">
                    @if (!$t->onboarding_completo)<span class="badge bg-warning text-dark">Config.</span>@elseif ($t->activa)<span class="badge bg-success">Activa</span>@else<span class="badge bg-danger">Suspendida</span>@endif
                    <form method="post" action="{{ route('admin.tiendas.entrar', $t) }}" class="d-inline">@csrf<button class="btn btn-sm btn-primary" title="Entrar a la tienda"><i class="fa-solid fa-right-to-bracket"></i></button></form>
                </div>
            </li>
            @empty
            <li class="lp-empty">Aún no hay empresas inscritas.</li>
            @endforelse
        </ul>
        <div class="table-responsive d-none d-md-block">
            <table class="table align-middle">
                <thead><tr><th>Tienda</th><th>Dueño</th><th>Plan</th><th>Asesor</th><th>País</th><th>Productos</th><th>Ventas</th><th>Última venta</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse ($tiendas as $t)
                    @php $v = $ventas[$t->id] ?? null; $m = \App\Support\Paises::datos($t->pais); $d = $duenos[$t->id][0] ?? null; @endphp
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="lp-avatar" style="border-radius:12px;width:40px;height:40px;flex:none">@if($t->logo_url)<img src="{{ $t->logo_url }}" style="width:100%;height:100%;object-fit:cover;border-radius:12px">@else<i class="fa-solid {{ $t->tipo_icono }}"></i>@endif</span>
                                <div><a href="{{ route('admin.tiendas.show', $t) }}" class="fw-bold text-decoration-none" style="color:inherit">{{ $t->nombre }}</a><br><small class="lp-cod">{{ $t->codigo }}</small> <small class="text-muted">{{ $t->tipo_nombre }}{{ $t->ciudad ? ' · ' . $t->ciudad : '' }}</small></div>
                            </div>
                        </td>
                        <td>{{ $d?->name ?? '—' }}<br><small class="text-muted">{{ $d?->email }}</small></td>
                        <td>{{ $t->plan->nombre ?? '—' }}@if($t->plan_vence)<br><small class="{{ $t->plan_vencido ? 'text-danger' : 'text-muted' }}">{{ $t->plan_vencido ? 'Venció' : 'Vence' }} {{ $t->plan_vence->format('d/m/y') }}</small>@endif</td>
                        <td class="text-nowrap">
                            @if ($t->asesor)<span class="fw-semibold">{{ \Illuminate\Support\Str::limit($t->asesor->nombre, 18) }}</span><br><small class="lp-cod">{{ $t->asesor->codigo }}</small>@else<small class="text-warning fw-bold">Sin asesor</small>@endif
                            <br><button type="button" class="btn btn-sm btn-asg mt-1" data-asignar="{{ route('admin.tiendas.asesor', $t) }}" data-tienda="{{ $t->nombre }}" data-actual="{{ $t->asesor_id }}"><i class="fa-solid fa-user-plus me-1"></i>{{ $t->asesor ? 'Cambiar' : 'Asignar' }}</button>
                        </td>
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
                    <tr><td colspan="10" class="lp-empty">Aún no hay tiendas inscritas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Ventana para asignar asesor --}}
<div class="asg-ov" id="asgOv" aria-hidden="true">
    <form method="post" class="asg-md" id="asgForm">
        @csrf
        <div class="asg-ic"><i class="fa-solid fa-user-tie"></i></div>
        <h4>Asignar asesor</h4>
        <p id="asgTienda" class="text-muted fw-semibold mb-3"></p>
        <label class="form-label" for="asgSel">Busque por nombre, código (LCP-00001), correo o ciudad</label>
        <select name="asesor_id" id="asgSel" class="form-select" data-buscar data-max="6">
            <option value="" data-sub="{{ auth()->user()->name }} · súper administrador · {{ auth()->user()->email }}">👑 LocalPro — venta directa (sin asesor)</option>
            @foreach ($asesores as $as)
            <option value="{{ $as->id }}" data-sub="{{ $as->cargo_nombre }}{{ $as->ciudad ? ' · ' . $as->ciudad : '' }}{{ $as->user?->email ? ' · ' . $as->user->email : '' }}">{{ $as->codigo }} · {{ $as->nombre }}</option>
            @endforeach
        </select>
        <small class="text-muted d-block mt-2">Desde ahora las comisiones de este negocio (venta nueva y renovaciones) son para ese asesor y sus líderes.</small>
        <div class="asg-bt">
            <button type="button" class="btn btn-light" data-asg-cerrar>Cancelar</button>
            <button class="btn btn-primary">Guardar</button>
        </div>
    </form>
</div>
<style>
.lp-cod{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-weight:700;color:#11702f;background:#eaf8ee;border-radius:6px;padding:.05rem .4rem;font-size:.75rem}
.btn-asg{border:1.5px solid #cfe9d6;background:#f2fbf3;color:#11702f;font-weight:700;border-radius:999px;padding:.2rem .7rem;font-size:.78rem}
.btn-asg:hover{background:#16b34a;color:#fff;border-color:#16b34a}
.asg-ov{position:fixed;inset:0;z-index:1980;background:rgba(11,13,12,.55);display:flex;align-items:center;justify-content:center;padding:16px;opacity:0;pointer-events:none;transition:opacity .18s}
.asg-ov.on{opacity:1;pointer-events:auto}
.asg-md{background:#fff;border-radius:26px;width:min(460px,100%);padding:24px 22px 20px;box-shadow:0 40px 90px -30px rgba(0,0,0,.55);transform:translateY(14px);transition:transform .2s}
.asg-ov.on .asg-md{transform:none}
.asg-ic{width:54px;height:54px;border-radius:18px;display:grid;place-items:center;font-size:1.4rem;background:#e8f8ec;color:#16b34a;margin-bottom:12px}
.asg-md h4{font-weight:800;margin:0 0 2px}
.asg-bt{display:flex;gap:10px;margin-top:18px}.asg-bt .btn{flex:1;border-radius:14px;font-weight:800;padding:.7rem}
</style>
<script>
(function () {
    var ov = document.getElementById('asgOv'), f = document.getElementById('asgForm'), sel = document.getElementById('asgSel');
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-asignar]');
        if (b) {
            f.action = b.dataset.asignar;
            document.getElementById('asgTienda').textContent = b.dataset.tienda;
            sel.value = b.dataset.actual || '';
            ov.classList.add('on'); ov.setAttribute('aria-hidden', 'false');
            setTimeout(function () { var x = document.getElementById('asgSel__lp'); if (x) x.click(); }, 150);
            return;
        }
        if (e.target === ov || e.target.closest('[data-asg-cerrar]')) { ov.classList.remove('on'); ov.setAttribute('aria-hidden', 'true'); }
    });
})();
</script>
@endsection
