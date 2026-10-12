@extends('layouts.app')

@section('title', 'Mi panel de asesor')

@section('content')
@php $f = fn ($v) => '$' . number_format((float) $v, 0, ',', '.'); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div>
            <small><i class="fa-solid {{ $yo->cargo_icono }} me-1"></i>Código <b>{{ $yo->codigo }}</b> · {{ $yo->cargo_nombre }}{{ $yo->zona ? ' · ' . $yo->zona : '' }}{{ $yo->ciudad ? ' · ' . $yo->ciudad : '' }}</small>
            <h1>Hola, <span>{{ strtok($yo->nombre, ' ') }}</span></h1>
            @if ($yo->padre)<span class="text-muted fw-semibold">Su líder: {{ $yo->padre->nombre }} ({{ $yo->padre->cargo_nombre }})</span>@endif
        </div>
        <div class="lp-quick"><a href="{{ route('asesor.red') }}#mapa" class="btn btn-success btn-lg"><i class="fa-solid fa-circle-nodes me-1"></i>Ver mi red</a><a href="{{ route('asesor.capacitacion') }}" class="btn btn-dark btn-lg"><i class="fa-solid fa-graduation-cap me-1"></i>Capacita a tu equipo</a><a href="{{ route('asesor.simulador') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-calculator me-1"></i>Simulador para reclutar</a></div>
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
        <p class="small mb-2" style="color:#8a6400;background:#fff5d6;border-radius:12px;padding:.5rem .75rem"><i class="fa-solid fa-circle-info me-1"></i>Para seguir activo y recibir comisiones debe tener <b>al menos un negocio con pago</b>. Si no, la comisión queda para LocalPro.</p>
        <div class="lp-link">
            <code id="miEnlace">{{ $yo->enlace }}</code>
            <button type="button" class="btn btn-dark btn-sm" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('miEnlace').textContent.trim()); LP.toast('Enlace copiado')"><i class="fa-regular fa-copy me-1"></i>Copiar</button>
            <a class="btn btn-success btn-sm" target="_blank" href="https://wa.me/?text={{ rawurlencode('¡Hola! Te recomiendo LocalPro para manejar tu negocio: ventas, caja, inventario y catálogo desde el celular. Inscríbete aquí: ' . $yo->enlace) }}"><i class="fa-brands fa-whatsapp me-1"></i>Compartir</a>
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
