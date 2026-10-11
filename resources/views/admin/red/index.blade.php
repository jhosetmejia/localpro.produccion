@extends('layouts.app')

@section('title', 'Red comercial')

@section('content')
@php $f = fn ($v) => '$' . number_format((float) $v, 0, ',', '.'); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Red <span>comercial</span></h1>
            <span class="text-muted fw-semibold">País → departamento → ciudad → zona (comuna) → asesores → negocios</span></div>
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
            <p class="lp-empty mb-0">Aún no hay nadie en la red. Empiece inscribiendo al líder de país o a sus líderes de departamento.</p>
        @else
        <ul class="rn-tree">
            @foreach ($raices as $n)
                @include('red._nodo', ['n' => $n, 'hijos' => $hijos, 'negocios' => $negocios, 'admin' => true, 'nivel' => 0])
            @endforeach
        </ul>
        @endif
    </div>

    <details class="lp-box" open>
        <summary class="fw-bold" style="cursor:pointer;font-size:1.05rem"><i class="fa-solid fa-sliders me-1" style="color:#16b34a"></i> Valores de comisión</summary>
        @php
            $filas = ['venta' => ['Asesor que vende', 'venta'], 'lider_zona' => ['Líder de zona', 'zona'], 'lider_ciudad' => ['Líder de ciudad', 'ciudad'], 'lider_departamento' => ['Líder de departamento', 'departamento'], 'lider_pais' => ['Líder de país', 'pais']];
            $resto = fn ($t) => $valores['valor_plan'] - array_sum($valores[$t]);
        @endphp
        <form method="post" action="{{ route('admin.red.valores') }}" class="mt-2" id="fValores">
            @csrf
            <div class="small fw-bold mb-2" style="color:#11702f"><i class="fa-solid fa-circle-info me-1"></i>Solo el plan anual paga comisión. Mensualidades y servicios adicionales son 100% para LocalPro.</div>
            <div class="row g-2 align-items-end mb-3">
                <div class="col-md-4"><label class="form-label">Valor del plan anual con asesor</label><input data-dinero type="number" name="red_valor_plan" class="form-control fw-bold" value="{{ (int) $valores['valor_plan'] }}"></div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle mb-2" style="min-width:420px">
                    <thead><tr><th>Quién gana</th><th style="width:34%">Venta nueva</th><th style="width:34%">Renovación anual</th></tr></thead>
                    <tbody>
                    @foreach ($filas as $k => [$nombre, $clave])
                    <tr>
                        <td class="fw-bold">{{ $nombre }}</td>
                        <td><input data-dinero type="number" name="red_nueva_{{ $clave }}" class="form-control vn" value="{{ (int) $valores['nueva'][$k] }}"></td>
                        <td><input data-dinero type="number" name="red_renov_{{ $clave }}" class="form-control vr" value="{{ (int) $valores['renovacion'][$k] }}"></td>
                    </tr>
                    @endforeach
                    <tr style="background:#f2fbf3">
                        <td class="fw-bold" style="color:#11702f">LocalPro (el resto)</td>
                        <td class="fw-bold fs-5" id="restoN">${{ number_format($resto('nueva'), 0, ',', '.') }}</td>
                        <td class="fw-bold fs-5" id="restoR">${{ number_format($resto('renovacion'), 0, ',', '.') }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <div class="small text-muted mb-3">Si un líder vende directamente, cobra la parte de asesor más la de su cargo y las de los cargos de abajo. Si un cargo está vacío o inactivo, esa parte queda para LocalPro. Un líder de zona maneja máximo {{ \App\Models\Asesor::MAX_ASESORES_ZONA }} asesores.</div>
            <button class="btn btn-dark">Guardar valores</button>
        </form>
        <script>
        document.addEventListener('input', function (e) {
            var f = document.getElementById('fValores'); if (!f || !f.contains(e.target)) return;
            var plan = +f.querySelector('[name=red_valor_plan]').value || 0;
            var suma = function (c) { return [].reduce.call(f.querySelectorAll('input.' + c + '[type=hidden], input.' + c + '[type=number]'), function (a, i) { return a + (+i.value || 0); }, 0); };
            var fmt = function (n) { return (n < 0 ? '-$' : '$') + Math.abs(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
            document.getElementById('restoN').textContent = fmt(plan - suma('vn'));
            document.getElementById('restoR').textContent = fmt(plan - suma('vr'));
        });
        </script>
    </details>
</div>
@endsection
