@extends('layouts.app')

@section('title', $evento->nombre)

@push('css')
<style>
.ev-kpis{grid-template-columns:repeat(4,minmax(0,1fr))!important}
.ev-kpis .lp-kpi{grid-column:auto!important;grid-row:auto!important}
.ev-kpis .val{font-size:clamp(1.3rem,2.4vw,2rem)!important;white-space:nowrap}
@media (max-width:900px){.ev-kpis{grid-template-columns:repeat(2,minmax(0,1fr))!important}}
.ev-share{display:flex;gap:.5rem;flex-wrap:wrap;align-items:center}
.ev-share code{background:#f4f6f5;border-radius:10px;padding:.5rem .7rem;font-size:.9rem;word-break:break-all;flex:1;min-width:220px}
.ev-tabs{display:flex;gap:.4rem;flex-wrap:wrap;margin:1rem 0}
.ev-tabs a{border:1.5px solid var(--lp-line);border-radius:999px;padding:.4rem .9rem;text-decoration:none;font-weight:700;color:inherit;font-size:.9rem}
.ev-tabs a.on{background:var(--lp-black);color:#fff;border-color:var(--lp-black)}
.ev-p{background:#fff;border:1.5px solid #e3efe5;border-radius:18px;padding:.9rem 1rem;margin-bottom:.7rem}
.ev-p.aviso{border-color:#25d366;box-shadow:0 0 0 3px rgba(37,211,102,.12)}
.ev-p .top{display:flex;gap:.8rem;align-items:flex-start;flex-wrap:wrap}
.ev-p .tx{flex:1;min-width:220px}
.ev-p .tx b{font-size:1.05rem}
.ev-p .meta{display:flex;flex-wrap:wrap;gap:.3rem .9rem;font-size:.85rem;color:#5d6862;margin-top:.25rem}
.ev-p .acc{display:flex;gap:.4rem;flex-wrap:wrap;align-items:center}
.ev-p .acc input{max-width:160px}
.ev-chip{font-size:.75rem;font-weight:800;border-radius:999px;padding:.2rem .6rem;display:inline-block}
.c-wait{background:#fff5d6;color:#8a6400}.c-ok{background:#e7f8ec;color:#167a3a}.c-bad{background:#ffe9f3;color:#9b1c5a}.c-in{background:#e0ecff;color:#1d4ed8}.c-wa{background:#dcfce7;color:#166534}
</style>
@endpush

@section('content')
@php
    $tel = fn ($n) => ($d = preg_replace('/\D/', '', (string) $n)) ? (strlen($d) === 10 ? '57' . $d : $d) : null;
    $avisar = session('avisar');
    $msgWa = fn ($e) => '¡Hola ' . \Illuminate\Support\Str::of($e->asistente->nombre)->explode(' ')->first() . '! Tu pago para ' . $evento->nombre . " fue aprobado ✅\nYa puedes ver tu entrada con QR aquí: " . route('mis-entradas.ver', $e->uuid) . "\nEntra con tu correo y tu clave. No compartas tu QR: es único e intransferible.";
    $av = $avisar ? \App\Models\EventoEntrada::with('asistente')->where('evento_id', $evento->id)->find($avisar) : null;
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Evento · {{ \App\Models\Evento::ESTADOS[$evento->estado] ?? $evento->estado }}</small><h1>{{ $evento->nombre }}</h1>
            <p class="text-muted mb-0">{{ $evento->fecha_evento ? ucfirst($evento->fecha_evento->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY · h:mm a')) : '' }} @if ($evento->lugar)· {{ $evento->lugar }}@endif · {{ $evento->etapa ?: 'Entrada' }}: {{ dinero($evento->precio) }}</p></div>
        <div class="lp-quick">
            <a href="{{ route('eventos.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>Eventos</a>
            @if ($p['admin'] || $p['info'] || $p['diseno'])<a href="{{ route('eventos.edit', $evento) }}" class="btn btn-light"><i class="fa-solid fa-pen me-1"></i>Editar</a>@endif
            @if ($p['admin'] || $p['puerta'])<a href="{{ route('eventos.puerta', $evento) }}" class="btn btn-dark"><i class="fa-solid fa-qrcode me-1"></i>Puerta (escanear)</a>@endif
        </div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($av && $av->estado === 'aprobada' && ($waAv = $tel($av->asistente->celular)))
    <div class="alert border-0 d-flex flex-wrap align-items-center gap-2" style="background:#dcfce7;color:#166534;border-radius:16px">
        <i class="fa-solid fa-circle-check"></i> <span class="fw-bold">Entrada de {{ $av->asistente->nombre }} lista.</span>
        <a class="btn btn-success btn-sm ms-auto" target="_blank" rel="noopener" href="https://wa.me/{{ $waAv }}?text={{ rawurlencode($msgWa($av)) }}"><i class="fa-brands fa-whatsapp me-1"></i>Avisarle por WhatsApp</a>
    </div>
    @endif

    <div class="lp-kpis ev-kpis">
        <div class="lp-kpi hero"><div class="lbl">Por aprobar</div><div class="val">{{ $stats['pendiente'] }}</div><div class="sub">{{ $stats['aviso'] }} ya mandaron comprobante</div><div class="ic"><i class="fa-solid fa-hourglass-half"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Aprobadas</div><div class="val">{{ $stats['aprobada'] }}</div><div class="sub">{{ $evento->cupo ? 'de ' . $evento->cupo . ' de cupo' : 'entradas con QR' }}</div><div class="ic"><i class="fa-solid fa-check"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Ingresaron</div><div class="val">{{ $stats['ingreso'] }}</div><div class="sub">pasaron por la puerta</div><div class="ic"><i class="fa-solid fa-door-open"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Recaudado</div><div class="val">{{ dinero($stats['recaudo']) }}</div><div class="sub">pagos aprobados</div><div class="ic"><i class="fa-solid fa-sack-dollar"></i></div></div>
        <div class="lp-kpi"><div class="lbl">Visitas</div><div class="val">{{ number_format((int) $evento->visitas, 0, ',', '.') }}</div><div class="sub">{{ $evento->visitas ? round(($stats['aprobada'] + $stats['pendiente']) * 100 / max(1, $evento->visitas)) . '% se registró' : 'a la página del evento' }}</div><div class="ic"><i class="fa-regular fa-eye"></i></div></div>
    </div>

    <div class="lp-box mb-3">
        <h3><i class="fa-solid fa-share-nodes me-1"></i>Página del evento @if ($evento->estado === 'borrador')<span class="ev-chip c-wait ms-1">Borrador: solo usted la ve</span>@endif</h3>
        <div class="ev-share">
            <code id="ev-url">{{ $evento->url }}</code>
            <button type="button" class="btn btn-light" id="ev-copiar"><i class="fa-regular fa-copy me-1"></i>Copiar</button>
            <a class="btn btn-light" href="https://wa.me/?text={{ rawurlencode('🎟️ ' . $evento->nombre . ' · compra tu entrada aquí: ' . $evento->url) }}" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp me-1"></i>Compartir</a>
            <a class="btn btn-primary" href="{{ $evento->url }}" target="_blank" rel="noopener"><i class="fa-solid fa-up-right-from-square me-1"></i>Ver página</a>
        </div>
        <small class="text-muted d-block mt-2">Póngala en la biografía de Instagram y TikTok y en los estados de WhatsApp.</small>
    </div>

    <div class="d-flex flex-wrap gap-2 align-items-center">
        <div class="ev-tabs">
            @foreach (['pendiente' => 'Por aprobar (' . $stats['pendiente'] . ')', 'aprobada' => 'Aprobadas (' . $stats['aprobada'] . ')', 'ingreso' => 'Ingresaron (' . $stats['ingreso'] . ')', 'rechazada' => 'Rechazadas / anuladas (' . $stats['rechazada'] . ')', 'todas' => 'Todas'] as $k => $t)
            <a href="{{ route('eventos.show', [$evento, 'ver' => $k]) }}" class="{{ $ver === $k ? 'on' : '' }}">{{ $t }}</a>
            @endforeach
        </div>
        <form class="d-flex gap-2 ms-auto" method="get"><input type="hidden" name="ver" value="{{ $ver }}">
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Nombre, documento, correo…">
            <button class="btn btn-light"><i class="fa-solid fa-magnifying-glass"></i></button></form>
        <a class="btn btn-light" href="{{ route('eventos.exportar', $evento) }}"><i class="fa-solid fa-file-csv me-1"></i>Excel</a>
    </div>

    @forelse ($entradas as $e)
    @php $a = $e->asistente; $wa = $tel($a->celular); @endphp
    <div class="ev-p {{ $e->estado === 'pendiente' && $e->aviso_pago_at ? 'aviso' : '' }}" id="e{{ $e->id }}">
        <div class="top">
            <div class="tx">
                <b>{{ $a->nombre }}</b>
                @if ($e->estado === 'pendiente' && $e->aviso_pago_at)<span class="ev-chip c-wa ms-1"><i class="fa-brands fa-whatsapp me-1"></i>Mandó comprobante {{ $e->aviso_pago_at->locale('es')->diffForHumans() }}</span>
                @elseif ($e->estado === 'pendiente')<span class="ev-chip c-wait ms-1">Registrado, sin avisar pago</span>
                @elseif ($e->estado === 'aprobada' && $e->ingreso_at)<span class="ev-chip c-in ms-1"><i class="fa-solid fa-door-open me-1"></i>Ingresó {{ $e->ingreso_at->format('d/m h:i a') }}</span>
                @elseif ($e->estado === 'aprobada')<span class="ev-chip c-ok ms-1"><i class="fa-solid fa-check me-1"></i>Aprobada</span>
                @else<span class="ev-chip c-bad ms-1">{{ $e->estado_texto }}</span>@endif
                <div class="meta">
                    <span><i class="fa-regular fa-id-card me-1"></i>{{ $a->tipo_documento }} {{ $a->documento }}</span>
                    @if ($a->edad !== null)<span>{{ $a->edad }} años @if ($a->edad < 18)<span class="ev-chip c-wait">menor</span>@endif</span>@endif
                    @foreach ($evento->camposFormulario() as $cp)@if (filled(($e->respuestas ?? [])[$cp['id']] ?? null))<span><b>{{ $cp['etiqueta'] }}:</b> {{ $e->respuestas[$cp['id']] }}</span>@endif @endforeach
                    @if ($wa)<a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" class="text-success text-decoration-none"><i class="fa-brands fa-whatsapp me-1"></i>{{ $a->celular }}</a>@endif
                    <span><i class="fa-regular fa-envelope me-1"></i>{{ $a->email }}</span>
                    <span>Registro N.° {{ $e->id }} · {{ $e->created_at->format('d/m h:i a') }}</span>
                    <span>{{ dinero($e->precio) }}@if ($e->etapa) · {{ $e->etapa }}@endif</span>
                    @if ($e->aprobada_at)<span>Aprobó {{ $e->aprobador?->name ?? '—' }} el {{ $e->aprobada_at->format('d/m h:i a') }}@if ($e->referencia_pago) · Ref. {{ $e->referencia_pago }}@endif</span>@endif
                    @if ($e->nota)<span>Nota: {{ $e->nota }}</span>@endif
                </div>
            </div>
            <div class="acc">
                @if ($p['admin'] || $p['pagos'])
                @if (in_array($e->estado, ['pendiente', 'rechazada'], true))
                <form method="post" action="{{ route('eventos.aprobar', [$evento, $e]) }}" class="d-flex gap-1" data-confirm="¿Aprobar el pago de {{ $a->nombre }}?" data-confirm-texto="Revise que el comprobante sea por {{ dinero($e->precio) }}. Se le genera la entrada con QR." data-confirm-ok="Sí, aprobar pago">
                    @csrf
                    <input class="form-control form-control-sm" name="referencia_pago" placeholder="Ref. Nequi (opcional)" maxlength="80">
                    <button class="btn btn-success"><i class="fa-solid fa-check me-1"></i>Aprobar pago</button>
                </form>
                @endif
                @if ($e->estado === 'pendiente')
                <form method="post" action="{{ route('eventos.rechazar', [$evento, $e]) }}" data-confirm="¿Rechazar este pago?" data-confirm-texto="La persona verá que su pago no se pudo confirmar." data-confirm-ok="Rechazar" data-peligro>@csrf<button class="btn btn-light btn-sm">Rechazar</button></form>
                @endif
                @if ($e->estado === 'aprobada')
                    @if ($wa)
                    <a class="btn btn-sm {{ $avisar == $e->id ? 'btn-success' : 'btn-light' }}" target="_blank" rel="noopener"
                       href="https://wa.me/{{ $wa }}?text={{ rawurlencode($msgWa($e)) }}"><i class="fa-brands fa-whatsapp me-1"></i>Avisarle</a>
                    @endif
                    @unless ($e->ingreso_at)
                    <form method="post" action="{{ route('eventos.pendiente', [$evento, $e]) }}" data-confirm="¿Devolver a Por aprobar?" data-confirm-texto="El QR deja de servir hasta que lo vuelva a aprobar." data-confirm-ok="Sí, devolver">@csrf<button class="btn btn-light btn-sm">Deshacer</button></form>
                    <form method="post" action="{{ route('eventos.rechazar', [$evento, $e]) }}" data-confirm="¿Anular esta entrada?" data-confirm-texto="El QR ya no servirá para entrar." data-confirm-ok="Anular" data-peligro>@csrf<button class="btn btn-light btn-sm text-danger">Anular</button></form>
                    @endunless
                @endif
                <form method="post" action="{{ route('eventos.clave', [$evento, $e]) }}" data-confirm="¿Restablecer la clave de {{ $a->nombre }}?" data-confirm-texto="Su nueva clave será su número de documento ({{ $a->documento }})." data-confirm-ok="Restablecer">@csrf<button class="btn btn-link btn-sm text-muted" title="Olvidó la clave">Restablecer clave</button></form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="lp-box text-center text-muted">No hay compradores en esta lista.</div>
    @endforelse
    {{ $entradas->links() }}

    @if ($p['admin'] && $evento->entradas()->count() === 0)
    <form method="post" action="{{ route('eventos.destroy', $evento) }}" class="mt-4 text-end" data-confirm="¿Borrar este evento?" data-confirm-ok="Sí, borrar" data-peligro>@csrf @method('delete')<button class="btn btn-link text-danger btn-sm">Borrar evento</button></form>
    @endif
</div>
@endsection

@push('js')
<script>
document.getElementById('ev-copiar').addEventListener('click', function () {
    var b = this, t = document.getElementById('ev-url').textContent;
    if (navigator.clipboard) navigator.clipboard.writeText(t).then(function () { b.innerHTML = '<i class="fa-solid fa-check me-1"></i>Copiado'; });
});
@if ($avisar)
(function () { var el = document.getElementById('e{{ (int) $avisar }}'); if (el) el.scrollIntoView({ block: 'center' }); })();
@endif
</script>
@endpush
