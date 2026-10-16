@extends('layouts.app')

@section('title', 'Separados y abonos')

@push('css')
<style>
.sp-fil{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1rem}
.sp-fil a{border-radius:999px;padding:.45rem .9rem;font-weight:800;font-size:.85rem;text-decoration:none;background:#fff;border:1.5px solid #e3ebe6;color:#151a17}
.sp-fil a.on{background:#0b0d0c;color:#a6f34a;border-color:#0b0d0c}
.sp-card{display:flex;gap:1rem;align-items:center;flex-wrap:wrap;background:#fff;border:1.5px solid #e3ebe6;border-radius:20px;padding:.9rem 1rem;margin-bottom:.6rem;text-decoration:none;color:inherit;transition:border-color .15s}
.sp-card:hover{border-color:#2fd15a;color:inherit}
.sp-card .num{font-weight:900;font-size:.8rem;background:#f1f5f2;border-radius:8px;padding:.2rem .5rem}
.sp-card .t{flex:1 1 220px;min-width:0}
.sp-card .t b{display:block;font-size:1rem}
.sp-card .t small{color:#6b7570;font-weight:600}
.sp-bar{height:8px;border-radius:99px;background:#e8eee9;overflow:hidden;margin-top:.4rem;max-width:260px}
.sp-bar i{display:block;height:100%;background:linear-gradient(90deg,#a6f34a,#16b34a)}
.sp-card .m{text-align:right;min-width:130px}
.sp-card .m b{display:block;font-size:1.05rem}
.sp-card .m small{color:#6b7570;font-weight:700}
.sp-est{font-size:.72rem;font-weight:800;border-radius:999px;padding:.2rem .6rem;white-space:nowrap}
.sp-est.listo{background:#dcfce7;color:#14753a}.sp-est.vencido{background:#ffe9f3;color:#b0173f}.sp-est.activo{background:#fff5d6;color:#8a6400}
.sp-row{display:flex;gap:.5rem;align-items:stretch;margin-bottom:.6rem}
.sp-row .sp-card{flex:1 1 auto;margin-bottom:0;min-width:0}
.sp-wa{flex:none;width:54px;border-radius:20px;background:#25d366;color:#fff;display:grid;place-items:center;font-size:1.45rem;text-decoration:none;transition:background .15s}
.sp-wa:hover{background:#1ebe5b;color:#fff}
.sp-wa.off{background:#eef1ef;color:#a3aca7;cursor:not-allowed;font-size:1.2rem}
.sp-est.entregado{background:#e8f8ec;color:#11702f}.sp-est.cancelado{background:#eef1ef;color:#6b7570}
</style>
@endpush

@section('content')
@php $f = fn ($v) => '$' . number_format((float) $v, 0, ',', '.'); $negocioNombre = \App\Models\Negocio::datos()->nombre ?? 'nuestro negocio'; @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-box-archive me-1"></i>Ventas</small><h1>Separados y <span>abonos</span></h1>
            <span class="text-muted fw-semibold">El cliente separa la prenda, abona por partes y se la lleva cuando completa el pago.</span></div>
        <div class="lp-quick"><a href="{{ route('separados.create') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-plus me-1"></i>Nuevo separado</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-hand-holding-dollar"></i></div><div class="lbl">Por cobrar en separados</div><div class="val">{{ $f($kpi['porCobrar']) }}</div><div class="sub">{{ $kpi['activos'] }} separados activos · ya abonaron {{ $f($kpi['abonado']) }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-circle-check"></i></div><div class="lbl">Listos para entregar</div><div class="val">{{ $kpi['listos'] }}</div><div class="sub">Pagados completos</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-calendar-xmark"></i></div><div class="lbl">Vencidos</div><div class="val">{{ $kpi['vencidos'] }}</div><div class="sub">Abonos recibidos hoy: {{ $f($kpi['abonosHoy']) }}</div></div>
    </div>

    <div class="lp-box">
        <form method="get" class="d-flex gap-2 mb-3 flex-wrap">
            <input type="hidden" name="ver" value="{{ $filtro }}">
            <input name="q" value="{{ $q }}" class="form-control" style="max-width:320px" placeholder="Buscar: cliente, celular o SEP-000…">
            <button class="btn btn-dark">Buscar</button>
        </form>
        <div class="sp-fil">
            @foreach (['activos' => 'Activos', 'listos' => 'Listos para entregar', 'vencidos' => 'Vencidos', 'entregados' => 'Entregados', 'cancelados' => 'Cancelados', 'todos' => 'Todos'] as $k => $v)
            <a href="{{ route('separados.index', ['ver' => $k, 'q' => $q ?: null]) }}" class="{{ $filtro === $k ? 'on' : '' }}">{{ $v }}</a>
            @endforeach
        </div>
        @forelse ($lista as $s)
        @php $est = $s->estado !== 'activo' ? $s->estado : ($s->pagado ? 'listo' : ($s->vencido ? 'vencido' : 'activo')); @endphp
        @php
            $nom = strtok((string) $s->cliente_nombre, ' ');
            $msgWa = match ($est) {
                'listo' => "Hola {$nom} 👋 Su separado {$s->numero} en {$negocioNombre} ya está pagado y listo para recoger. ¡Lo esperamos!",
                'activo', 'vencido' => "Hola {$nom} 👋 Le recordamos su separado {$s->numero} en {$negocioNombre}: le falta " . $f($s->saldo) . ($s->vence_en ? ' y tiene plazo hasta el ' . $s->vence_en->format('d/m/Y') : '') . '. Vea sus abonos aquí: ' . \App\Http\Controllers\separadoController::urlPublica($s),
                default => "Hola {$nom} 👋, le escribimos de {$negocioNombre} por su separado {$s->numero}.",
            };
        @endphp
        <div class="sp-row">
        <a class="sp-card" href="{{ route('separados.show', $s) }}">
            <span class="num">{{ $s->numero }}</span>
            <div class="t">
                <b>{{ $s->cliente_nombre }}</b>
                <small>{{ $s->items->map(fn ($i) => $i->descripcion . ($i->talla ? ' T' . $i->talla : ''))->implode(', ') }} · {{ $s->created_at->format('d/m/Y') }}{{ $s->vence_en && $s->estado === 'activo' ? ' · vence ' . $s->vence_en->format('d/m/Y') : '' }}</small>
                @if ($s->estado === 'activo')<div class="sp-bar"><i style="width:{{ max(3, $s->porcentaje) }}%"></i></div>@endif
            </div>
            <div class="m">
                @if ($s->estado === 'activo')
                <small>Le falta</small><b>{{ $f($s->saldo) }}</b><small>de {{ $f($s->total) }}</small>
                @else
                <b>{{ $f($s->total) }}</b>
                @endif
            </div>
            <span class="sp-est {{ $est }}">{{ ['listo' => '✅ Listo para entregar', 'vencido' => 'Vencido', 'activo' => 'Abonando', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'][$est] }}</span>
        </a>
        @if ($s->telefono_wa)
        <a class="sp-wa" target="_blank" rel="noopener" title="Escribirle por WhatsApp" href="https://wa.me/{{ $s->telefono_wa }}?text={{ rawurlencode($msgWa) }}"><i class="fa-brands fa-whatsapp"></i></a>
        @else
        <span class="sp-wa off" title="Este separado no tiene celular del cliente"><i class="fa-brands fa-whatsapp"></i></span>
        @endif
        </div>
        @empty
        <div class="lp-empty text-center py-4">
            <div style="font-size:2.2rem">📦</div>
            <b>No hay separados aquí.</b>
            <div class="text-muted">Cuando un cliente quiera apartar una prenda, toque <b>"Nuevo separado"</b>.</div>
        </div>
        @endforelse
        {{ $lista->links() }}
    </div>
</div>
@endsection
