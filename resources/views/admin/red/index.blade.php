@extends('layouts.app')

@section('title', 'Red comercial')

@section('content')
@php $f = fn ($v) => '$' . number_format((float) $v, 0, ',', '.'); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Red <span>comercial</span></h1>
            <span class="text-muted fw-semibold">País → departamento → ciudad → zona (comuna) → asesores → negocios</span></div>
        <div class="lp-quick"><button type="button" class="mr-btn" data-ver-red><span class="mr-pulso"></span> Ver mi red</button><a href="{{ route('admin.red.comisiones') }}" class="btn btn-dark btn-lg"><i class="fa-solid fa-coins me-1"></i>Comisiones</a><a href="{{ route('admin.red.contactos') }}" class="btn btn-success btn-lg"><i class="fa-brands fa-whatsapp me-1"></i>Contactos para asesores</a><a href="{{ route('curso.index') }}" class="btn btn-success btn-lg"><i class="fa-solid fa-graduation-cap me-1"></i>Ver el curso</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="lbl">Comisiones por pagar</div><div class="val">{{ $f($kpi['pendiente']) }}</div>@foreach ($kpi['pendienteOtras'] as $mon => $tot)<div class="fw-bold" style="font-size:1.05rem">+ {{ \App\Support\Moneda::formato($tot, $mon, true) }} <small>(Venezuela)</small></div>@endforeach<div class="sub"><a href="{{ route('admin.red.comisiones') }}" style="color:inherit">Ver y marcar pagadas</a></div></div>
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

    @php $enlacesAdmin = [['unete', 'enlaceUnete', 'Sumar asesores', 'fa-user-plus', '¡Hola! Te invito a trabajar como asesor comercial de LocalPro: ganas comisión por cada negocio que inscribas, sin inversión. Te registras, haces un curso corto y recibes tu certificado: '], ['r', 'enlaceNeg', 'Inscribir negocios', 'fa-store', '¡Hola! Te recomiendo LocalPro para manejar tu negocio: ventas, caja, inventario y catálogo desde el celular, con 3 meses gratis. Inscríbete aquí: '], ['contadores', 'enlaceCont', 'Inscribir contadores', 'fa-calculator', 'Hola 👋 ¿Eres contador? En LocalPro subes el listado de facturas de la DIAN y la contabilidad de tu cliente y en minutos ves lo que no está contabilizado, las diferencias de IVA y las retenciones, con el borrador del 350 y el calendario tributario de tus clientes. Pruébalo gratis aquí: ']]; @endphp
    <div class="lp-box mb-3">
        <h3><span><i class="fa-solid fa-key me-1"></i> Su código de LocalPro: <b>{{ \App\Models\Asesor::CODIGO_LOCALPRO }}</b></span><small class="text-muted">Quien entra con él queda directo con LocalPro; luego usted le asigna líder</small></h3>
        @foreach ($enlacesAdmin as [$ruta, $id, $titulo, $icono, $texto])
        @php $url = url('/' . $ruta . '/' . \App\Models\Asesor::CODIGO_LOCALPRO); @endphp
        <div class="small fw-bold mt-2 mb-1"><i class="fa-solid {{ $icono }} me-1"></i>{{ $titulo }}</div>
        <div class="lp-link">
            <code id="{{ $id }}">{{ $url }}</code>
            <button type="button" class="btn btn-dark btn-sm" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('{{ $id }}').textContent.trim()); LP.toast('Enlace copiado')"><i class="fa-regular fa-copy me-1"></i>Copiar</button>
            <a class="btn btn-success btn-sm" target="_blank" href="https://wa.me/?text={{ rawurlencode($texto . $url) }}"><i class="fa-brands fa-whatsapp me-1"></i>Compartir</a>
        </div>
        @endforeach
    </div>

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
            <li><div class="av"><i class="fa-solid fa-book-open"></i></div><div class="t"><b>{{ $p->nombre }}</b><small>Invitado por {{ $p->reclutador->nombre ?? 'LocalPro' }} · lecciones {{ count($p->curso_modulos ?? []) }}/4{{ $p->curso_nota !== null ? ' · mejor nota ' . $p->curso_nota . '%' : '' }} · {{ $p->ciudad }} · {{ $p->created_at->locale('es')->diffForHumans() }}</small></div>
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
            $filas = ['venta' => ['Asesor que vende', 'venta'], 'arriba' => ['El de arriba (quien lo sumó)', 'arriba'], 'lider_zona' => ['Líder de zona', 'zona'], 'lider_ciudad' => ['Líder de ciudad', 'ciudad'], 'lider_departamento' => ['Líder de departamento', 'departamento'], 'lider_pais' => ['Líder de país', 'pais']];
            $resto = fn ($t) => $valores['valor_plan'] - array_sum($valores[$t]);
        @endphp
        <form method="post" action="{{ route('admin.red.valores') }}" class="mt-2" id="fValores">
            @csrf
            <div class="small fw-bold mb-2" style="color:#11702f"><i class="fa-solid fa-circle-info me-1"></i>Solo el plan anual paga comisión. Mensualidades y servicios adicionales son 100% para LocalPro. Son valores fijos: si sube el precio del plan, la diferencia queda para LocalPro. La renovación se paga cada año, de por vida.</div>
            <div class="small fw-bold mb-2" style="color:#8a6400;background:#fff5d6;border-radius:12px;padding:.5rem .75rem"><i class="fa-solid fa-triangle-exclamation me-1"></i>Para cobrar, cada persona debe estar activa: mínimo <b>una venta nueva en los últimos {{ \App\Support\Comisiones::DIAS_ACTIVO }} días</b>. Si no, su parte queda para LocalPro.</div>
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
            <div class="small text-muted mb-3">"El de arriba" es quien sumó al asesor a la red (si no hay, su líder directo). Si un líder vende directamente, cobra la parte de asesor más la de su cargo y las de los cargos de abajo. Si un cargo está vacío o inactivo, esa parte queda para LocalPro. Un líder de zona maneja máximo {{ \App\Models\Asesor::MAX_ASESORES_ZONA }} asesores.</div>
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

    <details class="lp-box">
        <summary class="fw-bold" style="cursor:pointer;font-size:1.05rem"><i class="fa-solid fa-dollar-sign me-1" style="color:#16b34a"></i> Valores en dólares · Venezuela</summary>
        @php $restoU = fn ($t) => $valoresUsd['valor_plan'] - array_sum($valoresUsd[$t]); @endphp
        <form method="post" action="{{ route('admin.red.valores') }}" class="mt-2" id="fValoresUsd">
            @csrf
            <input type="hidden" name="moneda" value="USD">
            <div class="small fw-bold mb-2" style="color:#11702f"><i class="fa-solid fa-circle-info me-1"></i>Los negocios de Venezuela pagan el plan en dólares y las comisiones de esas ventas se pagan en dólares a toda la cadena (asesor y líderes). Los asesores de Venezuela ven su panel, su meta y la capacitación en dólares.</div>
            <div class="row g-2 align-items-end mb-3">
                <div class="col-6 col-md-4"><label class="form-label">Valor del plan anual (US$)</label><input type="number" step="0.01" min="0" name="red_usd_valor_plan" class="form-control fw-bold" value="{{ $valoresUsd['valor_plan'] }}"></div>
                <div class="col-6 col-md-4"><label class="form-label">Meta mensual del asesor (US$)</label><input type="number" step="0.01" min="0" name="red_usd_salario_minimo" class="form-control" value="{{ $salarioUsd }}"></div>
            </div>
            <div class="rv">
                <div class="rv-h"><span>Quién gana</span><span>Venta nueva (US$)</span><span>Renovación anual (US$)</span></div>
                @foreach ($filas as $k => [$nombre, $clave])
                <div class="rv-f">
                    <b>{{ $nombre }}</b>
                    <label><small>Venta nueva</small><input type="number" step="0.01" min="0" name="red_usd_nueva_{{ $clave }}" class="form-control un" value="{{ $valoresUsd['nueva'][$k] }}"></label>
                    <label><small>Renovación anual</small><input type="number" step="0.01" min="0" name="red_usd_renov_{{ $clave }}" class="form-control ur" value="{{ $valoresUsd['renovacion'][$k] }}"></label>
                </div>
                @endforeach
                <div class="rv-f rv-t">
                    <b>LocalPro (el resto)</b>
                    <label><small>Venta nueva</small><span id="restoUN">{{ \App\Support\Moneda::formato($restoU('nueva'), 'USD') }}</span></label>
                    <label><small>Renovación anual</small><span id="restoUR">{{ \App\Support\Moneda::formato($restoU('renovacion'), 'USD') }}</span></label>
                </div>
            </div>
            <button class="btn btn-dark">Guardar valores en dólares</button>
        </form>
        <script>
        document.addEventListener('input', function (e) {
            var f = document.getElementById('fValoresUsd'); if (!f || !f.contains(e.target)) return;
            var plan = +f.querySelector('[name=red_usd_valor_plan]').value || 0;
            var suma = function (c) { return [].reduce.call(f.querySelectorAll('input.' + c), function (a, i) { return a + (+i.value || 0); }, 0); };
            var fmt = {!! \App\Support\Moneda::js('USD') !!};
            document.getElementById('restoUN').textContent = fmt(plan - suma('un'));
            document.getElementById('restoUR').textContent = fmt(plan - suma('ur'));
        });
        </script>
    </details>
</div>
@endsection
