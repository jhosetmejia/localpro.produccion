@extends('layouts.app')

@section('title', 'Mi panel de asesor')

@section('content')
@php $f = fn ($v) => $yo->plata($v); /* en la moneda del asesor: dólares si es de Venezuela */ @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div>
            <small><i class="fa-solid {{ $yo->cargo_icono }} me-1"></i>Código <b>{{ $yo->codigo }}</b> · {{ $yo->cargo_nombre }}{{ $yo->zona ? ' · ' . $yo->zona : '' }}{{ $yo->ciudad ? ' · ' . $yo->ciudad : '' }}</small>
            <h1>Hola, <span>{{ strtok($yo->nombre, ' ') }}</span></h1>
            @if ($yo->padre)<span class="text-muted fw-semibold">Su líder: {{ $yo->padre->nombre }} ({{ $yo->padre->cargo_nombre }})</span>@endif
        </div>
        <div class="lp-quick"><a href="{{ route('asesor.vender') }}" class="btn btn-lg" style="background:#a6f34a;color:#0b0d0c;font-weight:900"><i class="fa-solid fa-rocket me-1"></i>Empiece a vender</a><a href="{{ route('asesor.contactos') }}" class="btn btn-success btn-lg"><i class="fa-brands fa-whatsapp me-1"></i>Mis contactos (20 al día)</a><a href="{{ route('asesor.red') }}#mapa" class="btn btn-success btn-lg"><i class="fa-solid fa-circle-nodes me-1"></i>Ver mi red</a><a href="{{ route('asesor.ejemplos') }}" class="btn btn-light btn-lg" style="border:1.5px solid #bfeccb"><i class="fa-solid fa-eye me-1" style="color:#16b34a"></i>Ejemplos para mostrar</a><a href="{{ route('asesor.capacitacion') }}" class="btn btn-dark btn-lg"><i class="fa-solid fa-graduation-cap me-1"></i>Capacita a tu equipo</a><a href="{{ route('aprende.index') }}" class="btn btn-light btn-lg" style="border:1.5px solid #bfeccb"><i class="fa-solid fa-book-open me-1"></i>Aprende (cursos)</a><a href="{{ route('asesor.simulador') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-calculator me-1"></i>Simulador para reclutar</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    {{-- En el registro solo se piden datos básicos: aquí se le recuerda completar cédula y dónde se le paga --}}
    @if ($yo->falta_perfil)
    <a href="{{ route('cuenta.edit') }}#pago" class="lp-box mb-3 d-flex align-items-center gap-3 flex-wrap text-decoration-none" style="background:#fff8e6;border:1.5px solid #f3d27a;color:inherit">
        <div style="font-size:1.8rem">📝</div>
        <div class="flex-grow-1" style="min-width:200px">
            <b>Completa tu información en el perfil</b>
            <div class="small text-muted">Falta: {{ implode(' y ', $yo->falta_perfil) }}. Sin estos datos LocalPro no puede consignarle sus comisiones.</div>
        </div>
        <span class="btn btn-warning fw-bold"><i class="fa-solid fa-user-pen me-1"></i>Completar ahora</span>
    </a>
    @endif

    {{-- Avisos: sus negocios que pagaron en los últimos 7 días --}}
    @foreach ($recientes as $rp)
    <div class="lp-box mb-2" style="background:linear-gradient(135deg,#e9fbe9,#f6fff4);border:1.5px solid #bfe8c8">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div style="font-size:1.8rem">🎉</div>
            <div class="flex-grow-1" style="min-width:200px">
                <b>{{ $rp->tienda->nombre ?? 'Un negocio' }} pagó su plan {{ $rp->plan->nombre ?? '' }}</b>
                <div class="small text-muted">{{ $rp->updated_at->diffForHumans() }}</div>
            </div>
            @if ($rp->mi_comision > 0)
            <div class="text-end"><div class="small text-muted">Usted gana</div><div class="fw-bold" style="font-size:1.35rem;color:#11702f">{{ \App\Support\Moneda::formato($rp->mi_comision, $rp->moneda ?: 'COP') }}</div></div>
            @else
            <div class="small text-muted" style="max-width:260px">Este pago no le generó comisión (solo el plan anual paga, y usted debe estar activo: mínimo una venta nueva en los últimos {{ \App\Support\Comisiones::DIAS_ACTIVO }} días).</div>
            @endif
        </div>
    </div>
    @endforeach

    {{-- Mi curso de asesor y certificado --}}
    @php
        $yoA = auth()->user()->asesor;
        $totLec = count(\App\Support\CursoAsesor::lecciones());
        $hechas = min($totLec, count((array) ($yoA->curso_modulos ?? [])));
        $pctCurso = $yoA?->curso_aprobado_at ? 100 : ($totLec ? (int) round($hechas / ($totLec + 1) * 100) : 0);
    @endphp
    @if ($yoA)
    <div class="lp-box mb-3" style="{{ $yoA->curso_aprobado_at ? 'border-color:#bfeccb;background:#f7fdf8' : 'border:2px solid #f5b544;background:#fff8e8' }}">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div style="font-size:2rem">{{ $yoA->curso_aprobado_at ? '🎓' : '📚' }}</div>
            <div class="flex-grow-1" style="min-width:220px">
                @php $lecNuevas = collect(\App\Support\CursoAsesor::lecciones())->filter(fn ($l, $n) => !empty($l['nuevo']) && !in_array($n, array_map('intval', (array) ($yoA->curso_modulos ?? [])), true))->count(); @endphp
                <b>Mi curso de asesor LocalPro</b>@if ($yoA->curso_aprobado_at && $lecNuevas) <span class="badge rounded-pill" style="background:#a6f34a;color:#0b0d0c;font-size:.65rem">{{ $lecNuevas }} {{ $lecNuevas === 1 ? 'LECCIÓN NUEVA' : 'LECCIONES NUEVAS' }}</span>@endif
                <div class="small text-muted">
                    @if ($yoA->curso_aprobado_at)
                        Aprobado el {{ $yoA->curso_aprobado_at->format('d/m/Y') }}@if ($yoA->curso_nota) con {{ $yoA->curso_nota }}%@endif · Certificado {{ $yoA->certificado }}
                    @else
                        {{ $hechas }} de {{ $totLec }} lecciones y el examen final. Al aprobar recibe su certificado.
                    @endif
                </div>
                <div class="progress mt-2" style="height:8px"><div class="progress-bar bg-success" style="width:{{ $pctCurso }}%"></div></div>
            </div>
            @if ($yoA->curso_aprobado_at)
            <a href="{{ route('curso.certificado') }}" class="btn btn-success"><i class="fa-solid fa-award me-1"></i>Ver mi certificado</a>
            <a href="{{ route('curso.index') }}" class="btn {{ $lecNuevas ? 'btn-warning' : 'btn-light' }}"><i class="fa-solid fa-book-open me-1"></i>{{ $lecNuevas ? 'Ver lecciones nuevas' : 'Repasar el curso' }}</a>
            @else
            <a href="{{ route('curso.index') }}" class="btn btn-dark"><i class="fa-solid fa-play me-1"></i>{{ $hechas ? 'Seguir el curso' : 'Empezar el curso' }}</a>
            @endif
        </div>
    </div>
    @php
        // Tarea de la lección de contactos que dejó "para más tarde"
        $lecTarea = collect(\App\Support\CursoAsesor::lecciones())->filter(fn ($l) => ($l['tarea'] ?? null) === 'contactos')->keys()->first();
        $tareaPend = $lecTarea && in_array($lecTarea, array_map('intval', (array) ($yoA->curso_modulos ?? [])), true) && !\App\Http\Controllers\cursoController::tareaHecha($yoA);
    @endphp
    @if ($tareaPend)
    <div class="lp-box mb-3 d-flex align-items-center gap-3 flex-wrap" style="border:2px dashed #f5b544;background:#fffdf3">
        <div style="font-size:1.7rem">📌</div>
        <div class="flex-fill"><b>Pendiente: subir sus contactos</b>
            <div class="text-muted small">Suba sus contactos y escríbale a {{ \App\Support\CursoAsesor::TAREA_MINIMO }} personas.</div></div>
        <a href="{{ route('asesor.contactos') }}" class="btn btn-warning fw-bold"><i class="fa-solid fa-address-book me-1"></i>Hacerlo ahora</a>
    </div>
    @endif
    @endif

    {{-- Contabilidad: retenciones e IVA (también para el asesor, sin negocio aparte) --}}
    @if ($yoA?->curso_aprobado_at && \Illuminate\Support\Facades\Route::has('contabilidad.index'))
    <div class="lp-box mb-3">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div style="font-size:2rem">🧾</div>
            <div class="flex-grow-1" style="min-width:220px">
                <b>Contabilidad: retenciones e IVA</b> <span class="badge rounded-pill" style="background:#a6f34a;color:#0b0d0c;font-size:.65rem">NUEVO</span>
                <div class="small text-muted">Lleve los clientes de un analista contable: cruce las facturas de la DIAN con Contapyme, Siigo o World Office, revise retenciones e IVA y saque el borrador del 350.</div>
            </div>
            <a href="{{ route('contabilidad.index') }}" class="btn btn-dark"><i class="fa-solid fa-file-invoice-dollar me-1"></i>Abrir Contabilidad</a>
            <a href="{{ route('contabilidad.guia') }}" class="btn btn-light"><i class="fa-solid fa-book me-1"></i>Guía</a>
        </div>
        @php $enlaceContador = url('/contadores/' . $yoA->codigo); @endphp
        <div class="mt-2 small d-flex align-items-center gap-2 flex-wrap">
            <span class="text-muted">Su enlace para contadores:</span> <code>{{ $enlaceContador }}</code>
            <a class="btn btn-success btn-sm" target="_blank" href="https://wa.me/?text={{ rawurlencode('Hola 👋 ¿Eres contador? En LocalPro subes el listado de facturas de la DIAN y la contabilidad de tu cliente y en minutos ves lo que no está contabilizado, las diferencias de IVA y las retenciones, con el borrador del 350 y el calendario tributario de tus clientes. Crea tu cuenta gratis aquí: ' . $enlaceContador) }}"><i class="fa-brands fa-whatsapp me-1"></i>Compartir</a>
        </div>
    </div>
    @endif

    {{-- Mis ganancias del mes --}}
    @php $g = $ganancias; $mesNombre = ucfirst(now()->locale('es')->isoFormat('MMMM')); @endphp
    <div class="lp-box mb-3" style="background:#0f1411;color:#fff;border:none">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <div class="small" style="color:#9fe3b0"><i class="fa-solid fa-chart-line me-1"></i>Mis ganancias de {{ $mesNombre }}</div>
                <div style="font-size:2.4rem;font-weight:900;line-height:1.1;color:#2fd15a">{{ $f($g['mes']) }}</div>
                <div class="small" style="color:#c9d3cc">{{ $g['ventasMes'] }} {{ $g['ventasMes'] === 1 ? 'venta nueva' : 'ventas nuevas' }} este mes · en el año lleva {{ $f($g['anio']) }}</div>
            </div>
            <div class="text-end">
                <div class="small" style="color:#c9d3cc">Si sigue a este ritmo, cierra el mes con</div>
                <div class="fw-bold" style="font-size:1.4rem">{{ $f($g['proyeccion']) }}</div>
                <div class="small" style="color:#c9d3cc">Quedan {{ $g['diasRestantes'] }} días</div>
            </div>
        </div>
        <div class="mt-3">
            <div class="d-flex justify-content-between small mb-1" style="color:#c9d3cc"><span>Meta: un salario mínimo ({{ $f($g['meta']) }})</span><b style="color:#fff">{{ $g['avance'] }}%</b></div>
            <div style="height:14px;background:#26302a;border-radius:99px;overflow:hidden"><div style="height:100%;width:{{ max(3, $g['avance']) }}%;background:linear-gradient(90deg,#16b34a,#2fd15a);border-radius:99px"></div></div>
            <div class="mt-2 fw-semibold">
                @if ($g['faltan'] <= 0)
                    🏆 ¡Ya se ganó un salario mínimo este mes! Todo lo que venda ahora es extra.
                @elseif ($g['mes'] <= 0)
                    🚀 Con solo <b style="color:#2fd15a">{{ $g['ventasFaltan'] }} planes anuales</b> se gana un salario mínimo. Cada negocio que pague le deja <b style="color:#2fd15a">{{ $f($g['porVenta']) }}</b>.
                @else
                    💪 Le faltan <b style="color:#2fd15a">{{ $f($g['faltan']) }}</b>: son solo <b style="color:#2fd15a">{{ $g['ventasFaltan'] }} {{ $g['ventasFaltan'] === 1 ? 'plan anual' : 'planes anuales' }}</b> más.
                @endif
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap mt-3">
            <a class="btn btn-success" href="#mis-negocios"><i class="fa-solid fa-hand-holding-dollar me-1"></i>Cobrar a mis negocios</a>
            <a class="btn btn-light" target="_blank" href="https://wa.me/?text={{ rawurlencode('Hola 👋 Le cuento de LocalPro: un sistema para manejar su negocio desde el celular (ventas, inventario, caja y facturas por WhatsApp). Pruébelo 2 meses gratis aquí: ' . $yo->enlace) }}"><i class="fa-brands fa-whatsapp me-1"></i>Ofrecer LocalPro</a>
            <a class="btn btn-outline-light" href="{{ route('asesor.comisiones') }}"><i class="fa-solid fa-coins me-1"></i>Ver mis comisiones</a>
        </div>
    </div>

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div><div class="lbl">Comisión por cobrar</div><div class="val">{{ $f($resumen['pendiente']) }}</div><div class="sub">Este mes ganó {{ $f($resumen['mes']) }} · ya le pagaron {{ $f($resumen['pagada']) }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-store"></i></div><div class="lbl">Mis negocios</div><div class="val">{{ $negocios->count() }}</div><div class="sub">{{ $yo->cargo === 'asesor' ? 'Negocios que usted inscribió' : $resumen['negociosRed'] . ' en toda su red' }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-users"></i></div><div class="lbl">Mi equipo</div><div class="val">{{ $resumen['equipo'] }}</div><div class="sub">{{ $yo->cargo === 'lider_zona' ? 'Asesores de su zona (máximo ' . \App\Models\Asesor::MAX_ASESORES_ZONA . ')' : ($yo->cargo === 'asesor' ? 'Personas a su cargo' : 'Líderes y asesores debajo de usted') }}</div></div>
    </div>

    <div class="lp-box mb-3">
        <h3><span><i class="fa-solid fa-link me-1"></i> Su enlace para inscribir negocios</span></h3>
        <p class="text-muted small mb-2">El negocio que se registre con este enlace, o escriba su código <b>{{ $yo->codigo }}</b>, queda a su nombre y usted gana la comisión cuando pague su plan anual.</p>
        <p class="small mb-2" style="color:#8a6400;background:#fff5d6;border-radius:12px;padding:.5rem .75rem"><i class="fa-solid fa-circle-info me-1"></i>Para recibir comisiones debe estar activo: mínimo <b>una venta nueva en los últimos {{ \App\Support\Comisiones::DIAS_ACTIVO }} días</b>. Si ese mes no vende, su parte queda para LocalPro.</p>
        <div class="lp-link">
            <code id="miEnlace">{{ $yo->enlace }}</code>
            <button type="button" class="btn btn-dark btn-sm" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('miEnlace').textContent.trim()); LP.toast('Enlace copiado')"><i class="fa-regular fa-copy me-1"></i>Copiar</button>
            <a class="btn btn-success btn-sm" target="_blank" href="https://wa.me/?text={{ rawurlencode('¡Hola! Te recomiendo LocalPro para manejar tu negocio: ventas, caja, inventario y catálogo desde el celular. Inscríbete aquí: ' . $yo->enlace) }}"><i class="fa-brands fa-whatsapp me-1"></i>Compartir</a>
        </div>
        @php $miEnlaceCont = url('/contadores/' . $yo->codigo); @endphp
        <div class="small fw-bold mt-3 mb-1"><i class="fa-solid fa-calculator me-1"></i>Para contadores <span class="fw-normal text-muted">(la cuenta queda como contador, a su nombre)</span></div>
        <div class="lp-link">
            <code id="miEnlaceCont">{{ $miEnlaceCont }}</code>
            <button type="button" class="btn btn-dark btn-sm" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('miEnlaceCont').textContent.trim()); LP.toast('Enlace copiado')"><i class="fa-regular fa-copy me-1"></i>Copiar</button>
            <a class="btn btn-success btn-sm" target="_blank" href="https://wa.me/?text={{ rawurlencode('Hola 👋 ¿Eres contador? En LocalPro subes el listado de facturas de la DIAN y la contabilidad de tu cliente y en minutos ves lo que no está contabilizado, las diferencias de IVA y las retenciones, con el borrador del 350 y el calendario tributario de tus clientes. Pruébalo gratis aquí: ' . $miEnlaceCont) }}"><i class="fa-brands fa-whatsapp me-1"></i>Compartir</a>
        </div>
    </div>

    @if ($aspirantes->isNotEmpty())
    <div class="lp-box mb-3" style="border-color:#bfeccb;background:#f7fdf8">
        <h3><span><i class="fa-solid fa-user-clock me-1"></i> Aspirantes por aprobar ({{ $aspirantes->count() }})</span></h3>
        <ul class="lp-list">
            @foreach ($aspirantes as $p)
            <li style="flex-wrap:wrap">
                <div class="av"><i class="fa-solid fa-user-plus"></i></div>
                <div class="t"><b>{{ $p->nombre }}</b><small>CC {{ $p->documento }} · {{ $p->user->telefono }} · {{ $p->ciudad ?: '' }}{{ $p->zona ? ' · ' . $p->zona : '' }} · {{ $p->created_at->locale('es')->diffForHumans() }}</small></div>
                <div class="d-flex gap-2">
                    <form method="post" action="{{ route('asesor.aspirantes.decidir', $p) }}">@csrf<input type="hidden" name="accion" value="aprobar"><button class="btn btn-sm btn-primary"><i class="fa-solid fa-check me-1"></i>Aprobar</button></form>
                    <form method="post" action="{{ route('asesor.aspirantes.decidir', $p) }}" data-confirm="¿Rechazar a {{ $p->nombre }}?" data-confirm-ok="Sí, rechazar" data-peligro>@csrf<input type="hidden" name="accion" value="rechazar"><button class="btn btn-sm btn-light">Rechazar</button></form>
                </div>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    @if ($enCurso->isNotEmpty())
    <div class="lp-box mb-3" style="border-color:#bfeccb;background:#f7fdf8">
        <h3><span><i class="fa-solid fa-graduation-cap me-1"></i> Haciendo el curso ({{ $enCurso->count() }})</span><small class="text-muted">Al aprobarlo quedan en su equipo solos</small></h3>
        <ul class="lp-list">
            @foreach ($enCurso as $p)
            @php $vistas = count($p->curso_modulos ?? []); @endphp
            <li style="flex-wrap:wrap">
                <div class="av"><i class="fa-solid fa-book-open"></i></div>
                <div class="t"><b>{{ $p->nombre }}</b><small>Lecciones {{ $vistas }}/4{{ $p->curso_nota !== null ? ' · mejor nota examen ' . $p->curso_nota . '%' : '' }} · se inscribió {{ $p->created_at->locale('es')->diffForHumans() }}</small></div>
                <div class="d-flex gap-2">
                    @if ($p->user->telefono)<a class="btn btn-sm btn-success" target="_blank" href="https://wa.me/{{ numero_whatsapp($p->user->telefono) }}?text={{ rawurlencode('¡Hola ' . strtok($p->nombre, ' ') . '! 💪 Te falta poquito para terminar el curso de LocalPro y recibir tu certificado. Entra aquí: ' . route('curso.index')) }}"><i class="fa-brands fa-whatsapp me-1"></i>Animar</a>@endif
                    <form method="post" action="{{ route('asesor.equipo.desactivar', $p) }}" data-confirm="¿Sacar a {{ $p->nombre }} del curso?" data-confirm-ok="Sí, sacar" data-peligro>@csrf<button class="btn btn-sm btn-light">Sacar</button></form>
                </div>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="lp-grid mb-3">
        <div class="lp-box">
            <h3><span><i class="fa-solid fa-user-plus me-1"></i> Suma asesores a tu equipo</span></h3>
            <p class="text-muted small mb-2">Quien se inscriba con este enlace hace un curso corto, recibe su certificado y queda en su equipo automáticamente.</p>
            <div class="lp-link">
                <code id="enlaceRec">{{ $yo->enlace_reclutar }}</code>
                <button type="button" class="btn btn-dark btn-sm" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('enlaceRec').textContent.trim()); LP.toast('Enlace copiado')"><i class="fa-regular fa-copy me-1"></i>Copiar</button>
                <a class="btn btn-success btn-sm" target="_blank" href="https://wa.me/?text={{ rawurlencode('¡Hola! Te invito a trabajar conmigo como asesor de LocalPro: ganas comisión por cada negocio que inscribas, sin inversión. Inscríbete aquí: ' . $yo->enlace_reclutar) }}"><i class="fa-brands fa-whatsapp me-1"></i>Invitar</a>
            </div>
        </div>
        <div class="lp-box">
            @if ($progreso['siguiente'])
            <h3><span><i class="fa-solid fa-ranking-star me-1"></i> Meta para subir a {{ $progreso['siguienteNombre'] }}</span></h3>
            @foreach ([['Negocios activos que pagaron su plan anual', $progreso['negocios'], $progreso['metaN']], ['Asesores que usted sumó (activos)', $progreso['asesores'], $progreso['metaA']]] as [$t, $v, $m])
            <div class="mb-2">
                <div class="d-flex justify-content-between small fw-bold"><span>{{ $t }}</span><span>{{ min($v, $m) }}/{{ $m }}</span></div>
                <div style="height:10px;border-radius:99px;background:#eef3ef;overflow:hidden"><div style="height:100%;width:{{ $m ? min(100, $v / $m * 100) : 0 }}%;background:linear-gradient(90deg,#a6f34a,#16b34a)"></div></div>
            </div>
            @endforeach
            <small class="text-muted">{{ $progreso['listo'] ? '🎉 ¡Cumplió la meta! LocalPro revisará su ascenso.' : 'Las pruebas gratis no cuentan: solo negocios que ya pagaron su plan anual.' }}</small>
            @else
            <h3><span><i class="fa-solid fa-crown me-1"></i> Está en el rango más alto</span></h3>
            <p class="text-muted mb-0">Líder de país. ¡Gracias por llevar LocalPro a todo el país!</p>
            @endif
        </div>
    </div>

    <div class="lp-grid mb-3">
        <div class="lp-box">
            <h3 id="mis-negocios"><span><i class="fa-solid fa-store me-1"></i> Mis negocios</span></h3>
            <ul class="lp-list">
                @forelse ($negocios as $t)
                <li>
                    <div class="av"><i class="fa-solid {{ $t->tipo_icono }}"></i></div>
                    <div class="t"><b>{{ $t->nombre }}</b><small>{{ $t->ciudad ?: 'Sin ciudad' }} · {{ $t->plan->nombre ?? 'Sin plan' }}{{ $t->plan_vence ? ' · ' . ($t->plan_vence->isPast() ? 'venció ' : 'vence ') . $t->plan_vence->format('d/m/Y') : '' }}</small>
                        @php $gn = $porNegocio[$t->id] ?? null; @endphp
                        @if (isset($pagaron[$t->id]))
                            <small class="fw-bold" style="color:#11702f"><i class="fa-solid fa-circle-check me-1"></i>Ya pagó{{ $gn ? ' · le ha dejado ' . \App\Support\Moneda::formato($gn->total, $gn->moneda) . ($gn->pendiente > 0 ? ' (' . \App\Support\Moneda::formato($gn->pendiente, $gn->moneda) . ' por cobrar)' : '') : '' }}</small>
                        @else
                            <small class="fw-bold" style="color:#a86b00"><i class="fa-solid fa-gift me-1"></i>Aún no paga · cuando pague el plan anual usted gana {{ \App\Support\Moneda::formato($porVentaNegocio($t), $t->moneda_cobro) }}</small>
                        @endif
                    </div>
                    @php $msgPago = 'Hola, le escribo de LocalPro 👋 Aquí puede pagar el plan de ' . $t->nombre . ' en línea (tarjeta, PSE o Nequi) y se activa de una vez: ' . $t->url_pago; @endphp
                    <a class="btn btn-sm btn-success" target="_blank" href="https://wa.me/{{ $t->telefono ? numero_whatsapp($t->telefono) : '' }}?text={{ rawurlencode($msgPago) }}" title="Enviar enlace de pago por WhatsApp"><i class="fa-brands fa-whatsapp me-1"></i>Cobrar</a>
                    <a class="btn btn-sm btn-dark" target="_blank" href="{{ $t->url_pago }}" title="Pagar yo el plan de este negocio"><i class="fa-solid fa-credit-card"></i></a>
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
                    <a class="btn btn-sm btn-success" target="_blank" href="https://wa.me/{{ $t->telefono ? numero_whatsapp($t->telefono) : '' }}?text={{ rawurlencode('Hola, le escribo de LocalPro: su plan ' . ($t->plan_vence->isPast() ? 'venció' : 'vence') . ' el ' . $t->plan_vence->format('d/m/Y') . '. Puede renovarlo aquí en línea (tarjeta, PSE o Nequi) y queda activo de una vez: ' . $t->url_pago) }}"><i class="fa-brands fa-whatsapp me-1"></i>Cobrar</a>
                    <a class="btn btn-sm btn-dark" target="_blank" href="{{ $t->url_pago }}" title="Pagar yo la renovación"><i class="fa-solid fa-credit-card"></i></a>
                </li>
                @empty
                <li class="lp-empty">Nada por renovar por ahora.</li>
                @endforelse
            </ul>
        </div>
    </div>

    @if ($sumados->isNotEmpty())
    <div class="lp-box mb-3">
        <h3><span><i class="fa-solid fa-user-check me-1"></i> Personas que usted sumó</span><small class="text-muted">Puede desactivar a quien no le cuadre</small></h3>
        <ul class="lp-list">
            @foreach ($sumados as $p)
            <li>
                <div class="av"><i class="fa-solid {{ $p->cargo_icono }}"></i></div>
                <div class="t"><b>{{ $p->nombre }}</b><small>{{ $p->codigo }} · {{ $p->cargo_nombre }}{{ $p->certificado ? ' · certificado ' . $p->certificado : '' }}{{ $p->activo ? '' : ' · inactivo' }}</small></div>
                @if ($p->activo)
                <form method="post" action="{{ route('asesor.equipo.desactivar', $p) }}" data-confirm="¿Desactivar a {{ $p->nombre }}? Para activarlo de nuevo hay que pedírselo a LocalPro." data-confirm-ok="Sí, desactivar" data-peligro>@csrf<button class="btn btn-sm btn-light">Desactivar</button></form>
                @endif
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="lp-box">
        <h3><span><i class="fa-solid fa-coins me-1"></i> Últimas comisiones</span><a href="{{ route('asesor.comisiones') }}" class="small">Ver todas</a></h3>
        <p class="small text-muted mb-2"><span class="badge bg-warning text-dark">Pendiente</span> LocalPro se la consigna{{ $yo->banco ? ' a ' . $yo->banco : ' en la cuenta que registró' }} · <span class="badge bg-success">Pagada</span> ya se la consignaron.<br><b>Venta nueva:</b> la puede retirar apenas el negocio paga. <b>Renovación:</b> se paga una vez al mes.</p>
        <ul class="lp-list">
            @forelse ($ultimas as $c)
            <li><div class="av"><i class="fa-solid fa-coins"></i></div><div class="t"><b>{{ $c->monto_texto }} · {{ $c->tienda->nombre ?? 'Negocio' }}</b><small>{{ $c->concepto_nombre }} · {{ $c->tipo === 'nueva' ? 'venta nueva' : 'renovación' }} · {{ $c->created_at->format('d/m/Y') }}</small></div>
                <span class="badge {{ $c->estado === 'pagada' ? 'bg-success' : ($c->estado === 'pendiente' ? 'bg-warning text-dark' : 'bg-secondary') }}">{{ ucfirst($c->estado) }}</span></li>
            @empty
            <li class="lp-empty">Todavía no tiene comisiones. Se generan cuando sus negocios pagan el plan.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
