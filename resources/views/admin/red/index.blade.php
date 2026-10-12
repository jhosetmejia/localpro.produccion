@extends('layouts.app')

@section('title', 'Red comercial')

@section('content')
@php $f = fn ($v) => '$' . number_format((float) $v, 0, ',', '.'); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Red <span>comercial</span></h1>
            <span class="text-muted fw-semibold">País → departamento → ciudad → zona (comuna) → asesores → negocios</span></div>
        <div class="lp-quick"><button type="button" class="mr-btn" data-ver-red><span class="mr-pulso"></span> Ver mi red</button><a href="{{ route('admin.red.comisiones') }}" class="btn btn-dark btn-lg"><i class="fa-solid fa-coins me-1"></i>Comisiones</a><a href="{{ route('curso.index') }}" class="btn btn-success btn-lg"><i class="fa-solid fa-graduation-cap me-1"></i>Ver el curso</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="lbl">Comisiones por pagar</div><div class="val">{{ $f($kpi['pendiente']) }}</div><div class="sub"><a href="{{ route('admin.red.comisiones') }}" style="color:inherit">Ver y marcar pagadas</a></div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-users"></i></div><div class="lbl">Personas activas</div><div class="val">{{ $kpi['gente'] }}</div><div class="sub">Asesores y líderes</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-store"></i></div><div class="lbl">Negocios con asesor</div><div class="val">{{ $kpi['negocios'] }}</div><div class="sub">{{ $kpi['sinAsesor'] }} sin asesor</div></div>
    </div>

    @if ($candidatos->isNotEmpty())
    <div class="lp-box mb-3" style="border-color:#bfeccb;background:#f7fdf8">
        <h3><span><i class="fa-solid fa-ranking-star me-1"></i> Ascensos sugeridos ({{ $candidatos->count() }})</span><small class="text-muted">Cumplen {{ $metas['negocios'] }} negocios pagados y {{ $metas['asesores'] }} asesores sumados</small></h3>
        <ul class="lp-list">
            @foreach ($candidatos as $c)
            <li style="flex-wrap:wrap">
                <div class="av"><i class="fa-solid {{ $c->cargo_icono }}"></i></div>
                <div class="t"><b><a href="{{ route('admin.red.show', $c) }}" class="text-decoration-none" style="color:inherit">{{ $c->nombre }}</a></b>
                    <small>{{ $c->cargo_nombre }} → <b>{{ \App\Models\Asesor::CARGOS[$c->cargo_siguiente][0] }}</b> · {{ $c->meta_negocios }} negocios pagados · {{ $c->meta_asesores }} asesores</small></div>
                <form method="post" action="{{ route('admin.red.ascender', $c) }}" data-confirm="¿Subir a {{ $c->nombre }} a {{ \App\Models\Asesor::CARGOS[$c->cargo_siguiente][0] }}?" data-confirm-texto="Las personas que él sumó pasan a su equipo." data-confirm-ok="Sí, ascender">@csrf<button class="btn btn-sm btn-primary"><i class="fa-solid fa-arrow-up me-1"></i>Aprobar ascenso</button></form>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    @if ($pendientes->isNotEmpty())
    <div class="lp-box mb-3">
        <h3><span><i class="fa-solid fa-user-clock me-1"></i> Aspirantes esperando aprobación ({{ $pendientes->count() }})</span><small class="text-muted">Los aprueba quien los invitó</small></h3>
        <ul class="lp-list">
            @foreach ($pendientes as $p)
            <li><div class="av"><i class="fa-solid fa-user-plus"></i></div><div class="t"><b>{{ $p->nombre }}</b><small>Invitado por {{ $p->reclutador->nombre ?? '—' }} · {{ $p->ciudad }} · {{ $p->created_at->locale('es')->diffForHumans() }}</small></div></li>
            @endforeach
        </ul>
    </div>
    @endif

    @if ($enCurso->isNotEmpty() || $sinCurso)
    <div class="lp-box mb-3">
        <h3><span><i class="fa-solid fa-graduation-cap me-1"></i> Haciendo el curso de asesores ({{ $enCurso->count() }})</span><small class="text-muted">{{ $certificados }} certificados entregados · {{ $sinCurso }} activos aún sin certificado (no entran a su panel hasta aprobar)</small></h3>
        <ul class="lp-list">
            @foreach ($enCurso as $p)
            <li><div class="av"><i class="fa-solid fa-book-open"></i></div><div class="t"><b>{{ $p->nombre }}</b><small>Invitado por {{ $p->reclutador->nombre ?? '—' }} · lecciones {{ count($p->curso_modulos ?? []) }}/4{{ $p->curso_nota !== null ? ' · mejor nota ' . $p->curso_nota . '%' : '' }} · {{ $p->ciudad }} · {{ $p->created_at->locale('es')->diffForHumans() }}</small></div>
                @if ($p->user->telefono)<a class="btn btn-sm btn-light" target="_blank" href="https://wa.me/{{ numero_whatsapp($p->user->telefono) }}"><i class="fa-brands fa-whatsapp"></i></a>@endif
            </li>
            @endforeach
        </ul>
    </div>
    @endif

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
        @include('red._mapa', ['raices' => $raices, 'hijos' => $hijos, 'negocios' => $negocios, 'admin' => true, 'raiz' => 'LocalPro'])
    </div>

    <details class="lp-box" open>
        <summary class="fw-bold" style="cursor:pointer;font-size:1.05rem"><i class="fa-solid fa-sliders me-1" style="color:#16b34a"></i> Valores de comisión</summary>
        @php
            $filas = ['venta' => ['Asesor que vende', 'venta'], 'lider_zona' => ['Líder de zona', 'zona'], 'lider_ciudad' => ['Líder de ciudad', 'ciudad'], 'lider_departamento' => ['Líder de departamento', 'departamento'], 'lider_pais' => ['Líder de país', 'pais']];
            $resto = fn ($t) => $valores['valor_plan'] - array_sum($valores[$t]);
        @endphp
        <form method="post" action="{{ route('admin.red.valores') }}" class="mt-2" id="fValores">
            @csrf
            <div class="small fw-bold mb-2" style="color:#11702f"><i class="fa-solid fa-circle-info me-1"></i>Solo el plan anual paga comisión. Mensualidades y servicios adicionales son 100% para LocalPro. La renovación se paga cada año, de por vida, mientras el negocio y el asesor sigan activos.</div>
            <div class="row g-2 align-items-end mb-3">
                <div class="col-6 col-md-4"><label class="form-label">Meta para subir: negocios pagados</label><input type="number" min="1" name="red_meta_negocios" class="form-control" value="{{ $metas['negocios'] }}"></div>
                <div class="col-6 col-md-4"><label class="form-label">Meta para subir: asesores sumados</label><input type="number" min="0" name="red_meta_asesores" class="form-control" value="{{ $metas['asesores'] }}"></div>
                <div class="col-6 col-md-4"><label class="form-label">Salario mínimo (capacitación)</label><input data-dinero type="number" min="0" name="red_salario_minimo" class="form-control" value="{{ (int) $salario }}"></div>
                <div class="col-md-4"><label class="form-label">Valor del plan anual con asesor</label><input data-dinero type="number" name="red_valor_plan" class="form-control fw-bold" value="{{ (int) $valores['valor_plan'] }}"></div>
            </div>
            <div class="rv">
                <div class="rv-h"><span>Quién gana</span><span>Venta nueva</span><span>Renovación anual</span></div>
                @foreach ($filas as $k => [$nombre, $clave])
                <div class="rv-f">
                    <b>{{ $nombre }}</b>
                    <label><small>Venta nueva</small><input data-dinero type="number" min="0" name="red_nueva_{{ $clave }}" class="form-control vn" value="{{ (int) $valores['nueva'][$k] }}"></label>
                    <label><small>Renovación anual</small><input data-dinero type="number" min="0" name="red_renov_{{ $clave }}" class="form-control vr" value="{{ (int) $valores['renovacion'][$k] }}"></label>
                </div>
                @endforeach
                <div class="rv-f rv-t">
                    <b>LocalPro (el resto)</b>
                    <label><small>Venta nueva</small><span id="restoN">${{ number_format($resto('nueva'), 0, ',', '.') }}</span></label>
                    <label><small>Renovación anual</small><span id="restoR">${{ number_format($resto('renovacion'), 0, ',', '.') }}</span></label>
                </div>
            </div>
            <style>
                .rv{margin-bottom:10px}
                .rv-h,.rv-f{display:grid;grid-template-columns:1.2fr 1fr 1fr;gap:10px;align-items:center}
                .rv-h{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#5b665e;padding:0 0 6px;border-bottom:1.5px solid #e3efe5}
                .rv-f{padding:10px 0;border-bottom:1px solid #edf3ee}
                .rv-f label{margin:0;display:block;min-width:0}
                .rv-f label small{display:none;font-weight:700;color:#5b665e;font-size:.75rem}
                .rv-f input{width:100%;min-width:0}
                .rv-t{background:#f2fbf3;border-radius:14px;padding:12px;border:0;margin-top:6px}
                .rv-t b{color:#11702f}.rv-t span{font-weight:800;font-size:1.15rem}
                @media (max-width:576px){
                    .rv-h{display:none}
                    .rv-f{grid-template-columns:1fr 1fr}
                    .rv-f b{grid-column:1/-1}
                    .rv-f label small{display:block;margin-bottom:2px}
                }
            </style>
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
