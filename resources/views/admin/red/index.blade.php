@extends('layouts.app')

@section('title', 'Red comercial')

@section('content')
@php $f = fn ($v) => '$' . number_format((float) $v, 0, ',', '.'); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Red <span>comercial</span></h1>
            <span class="text-muted fw-semibold">Líderes de departamento → líderes de ciudad → asesores → negocios</span></div>
        <div class="lp-quick"><a href="{{ route('admin.red.comisiones') }}" class="btn btn-dark btn-lg"><i class="fa-solid fa-coins me-1"></i>Comisiones</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="lbl">Comisiones por pagar</div><div class="val">{{ $f($kpi['pendiente']) }}</div><div class="sub"><a href="{{ route('admin.red.comisiones') }}" style="color:inherit">Ver y marcar pagadas</a></div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-users"></i></div><div class="lbl">Personas activas</div><div class="val">{{ $kpi['gente'] }}</div><div class="sub">Asesores y líderes</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-store"></i></div><div class="lbl">Negocios con asesor</div><div class="val">{{ $kpi['negocios'] }}</div><div class="sub">{{ $kpi['sinAsesor'] }} sin asesor</div></div>
    </div>

    <details class="lp-box mb-3" @if ($errors->any() && !$errors->has('red_valor_plan')) open @endif>
        <summary class="fw-bold" style="cursor:pointer;font-size:1.05rem"><i class="fa-solid fa-user-plus me-1" style="color:#16b34a"></i> Inscribir asesor o líder</summary>
        <form method="post" action="{{ route('admin.red.store') }}" class="row g-3 mt-1">
            @csrf
            @include('admin.red._form', ['a' => null])
            <div class="col-12"><button class="btn btn-primary btn-lg">Inscribir en la red</button></div>
        </form>
    </details>

    <div class="lp-box mb-3">
        <h3><span><i class="fa-solid fa-sitemap me-1"></i> Diagrama de la red</span>
            @if ($departamentos->count() > 1)
            <form method="get" class="d-inline"><select name="departamento" class="form-select form-select-sm" onchange="this.form.submit()"><option value="">Todos los departamentos</option>@foreach ($departamentos as $d)<option @selected($departamento === $d)>{{ $d }}</option>@endforeach</select></form>
            @endif
        </h3>
        <div class="rn-ley"><span style="--c:#16b34a">Plan al día</span><span style="--c:#f2b705">Vence pronto</span><span style="--c:#e0245e">Vencido</span><span style="--c:#b8c2bc">Plan gratis</span></div>
        @if ($raices->isEmpty())
            <p class="lp-empty mb-0">Aún no hay nadie en la red. Empiece inscribiendo a sus líderes de departamento.</p>
        @else
        <ul class="rn-tree">
            @foreach ($raices as $n)
                @include('red._nodo', ['n' => $n, 'hijos' => $hijos, 'negocios' => $negocios, 'admin' => true, 'nivel' => 0])
            @endforeach
        </ul>
        @endif
    </div>

    <details class="lp-box" @if ($errors->has('red_valor_plan')) open @endif>
        <summary class="fw-bold" style="cursor:pointer;font-size:1.05rem"><i class="fa-solid fa-sliders me-1" style="color:#16b34a"></i> Valores de comisión</summary>
        <form method="post" action="{{ route('admin.red.valores') }}" class="row g-3 mt-1">
            @csrf
            <div class="col-md-4"><label class="form-label">Valor del plan anual con asesor</label><input data-dinero type="number" name="red_valor_plan" class="form-control" value="{{ (int) $valores['valor_plan'] }}"></div>
            <div class="col-12"><b>Venta nueva</b> <small class="text-muted">— LocalPro se queda con el resto</small></div>
            <div class="col-md-4"><label class="form-label">Asesor que vende</label><input data-dinero type="number" name="red_nueva_venta" class="form-control" value="{{ (int) $valores['nueva']['venta'] }}"></div>
            <div class="col-md-4"><label class="form-label">Líder de ciudad</label><input data-dinero type="number" name="red_nueva_ciudad" class="form-control" value="{{ (int) $valores['nueva']['lider_ciudad'] }}"></div>
            <div class="col-md-4"><label class="form-label">Líder de departamento</label><input data-dinero type="number" name="red_nueva_departamento" class="form-control" value="{{ (int) $valores['nueva']['lider_departamento'] }}"></div>
            <div class="col-12"><b>Renovación (cada año)</b></div>
            <div class="col-md-4"><label class="form-label">Asesor del negocio</label><input data-dinero type="number" name="red_renov_venta" class="form-control" value="{{ (int) $valores['renovacion']['venta'] }}"></div>
            <div class="col-md-4"><label class="form-label">Líder de ciudad</label><input data-dinero type="number" name="red_renov_ciudad" class="form-control" value="{{ (int) $valores['renovacion']['lider_ciudad'] }}"></div>
            <div class="col-md-4"><label class="form-label">Líder de departamento</label><input data-dinero type="number" name="red_renov_departamento" class="form-control" value="{{ (int) $valores['renovacion']['lider_departamento'] }}"></div>
            <div class="col-12 small text-muted">Si un líder vende directamente, cobra la parte de asesor más la de su cargo. Si un cargo está vacío, esa parte queda para LocalPro. Si el negocio paga otro valor (por ejemplo un plan mensual), las comisiones se calculan en proporción.</div>
            <div class="col-12"><button class="btn btn-dark">Guardar valores</button></div>
        </form>
    </details>
</div>
@endsection
