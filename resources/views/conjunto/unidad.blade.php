@extends('layouts.app')

@section('title', $u->nombre)

@section('content')
@php
    $pend = $cargos->whereIn('estado', ['pendiente', 'parcial']);
    $txt = "Hola " . ($u->persona ?: '') . ", le compartimos el estado de cuenta de " . $u->nombre . " en " . $conj->nombre . ":\n\n";
    foreach ($pend as $c) { $txt .= "• " . $c->concepto . ": " . dinero($c->saldo) . ($c->vence ? " (vence " . $c->vence->format('d/m/Y') . ")" : '') . "\n"; }
    $txt .= "\nTotal a pagar: " . dinero($saldo) . "\n¡Gracias por estar al día!";
    $tel = numero_whatsapp($u->contacto);
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('conjunto.unidades') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Unidades</a></small><h1>{{ $u->nombre }}</h1>
            <span class="text-muted fw-semibold">{{ $u->propietario ?: 'Sin propietario' }}{{ $u->residente ? ' · reside: ' . $u->residente : '' }}{{ $u->contacto ? ' · ' . $u->contacto : '' }}</span></div>
        <div class="lp-quick">
            @if ($tel)<a class="btn btn-success" target="_blank" href="https://wa.me/{{ $tel }}?text={{ rawurlencode($saldo > 0 ? $txt : 'Hola ' . ($u->persona ?: '') . ', ' . $u->nombre . ' está al día con la administración de ' . $conj->nombre . '. ¡Gracias!') }}"><i class="fa-brands fa-whatsapp me-1"></i>Enviar estado de cuenta</a>@endif
        </div>
    </div>
    @include('layouts.partials.lp-flash')

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-scale-balanced"></i></div><div class="lbl">Saldo pendiente</div><div class="val">{{ $saldo > 0 ? dinero($saldo) : 'Al día ✅' }}</div><div class="sub">{{ $pend->count() }} cobro(s) pendiente(s){{ $favor > 0 ? ' · saldo a favor ' . dinero($favor) : '' }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-file-invoice"></i></div><div class="lbl">Cuota mensual</div><div class="val">{{ dinero($u->cuota) }}</div><div class="sub">{{ \App\Models\Conjunto\Unidad::TIPOS[$u->tipo] ?? '' }}{{ $u->coeficiente ? ' · coeficiente ' . $u->coeficiente . '%' : '' }}</div></div>
        <div class="lp-kpi">
            <div class="lbl mb-2">Registrar pago</div>
            <form method="post" action="{{ route('conjunto.pago.store', $u) }}" class="row g-2">
                @csrf
                <div class="col-6"><input data-dinero type="number" name="valor" class="form-control fw-bold" min="1" required placeholder="{{ (int) $saldo }}" value="{{ old('valor', $saldo > 0 ? (int) $saldo : '') }}"></div>
                <div class="col-6"><input type="date" name="fecha" class="form-control" value="{{ old('fecha', today()->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}"></div>
                <div class="col-6"><select name="metodo" class="form-select">@foreach ($metodos->isNotEmpty() ? $metodos : collect(['Efectivo', 'Transferencia']) as $m)<option>{{ $m }}</option>@endforeach</select></div>
                <div class="col-6"><input name="referencia" class="form-control" maxlength="60" placeholder="Referencia"></div>
                <div class="col-12 d-grid"><button class="btn btn-primary">Registrar y generar recibo</button></div>
            </form>
        </div>
    </div>

    <div class="lp-grid mb-3">
        <div class="lp-box">
            <h3>Estado de cuenta</h3>
            <ul class="lp-list">
                @forelse ($cargos as $c)
                <li>
                    <div class="av" style="{{ $c->vencido ? 'background:#ffe9f3;color:#b0206a' : '' }}"><i class="fa-solid {{ $c->estado === 'pagado' ? 'fa-circle-check' : 'fa-file-invoice' }}"></i></div>
                    <div class="t"><b>{{ $c->concepto }}</b><small>{{ dinero($c->valor) }}{{ $c->pagado > 0 && $c->estado !== 'pagado' ? ' · abonado ' . dinero($c->pagado) : '' }}{{ $c->vence ? ' · vence ' . $c->vence->format('d/m/Y') : '' }}</small></div>
                    <span class="badge {{ $c->estado === 'pagado' ? 'bg-success' : ($c->vencido ? 'bg-danger' : 'bg-warning text-dark') }}">{{ $c->estado === 'pagado' ? 'Pagado' : ($c->vencido ? 'Vencido' : ucfirst($c->estado)) }}</span>
                    @if ($c->pagado <= 0 && $c->estado !== 'pagado')
                    <form method="post" action="{{ route('conjunto.cobros.anular', $c) }}" data-confirm="¿Anular este cobro?" data-confirm-ok="Sí, anular" data-peligro>@csrf<button class="btn btn-sm btn-light" title="Anular"><i class="fa-solid fa-xmark"></i></button></form>
                    @endif
                </li>
                @empty
                <li class="lp-empty">Sin cobros todavía.</li>
                @endforelse
            </ul>
        </div>
        <div class="lp-box">
            <h3>Pagos</h3>
            <ul class="lp-list">
                @forelse ($pagos as $p)
                <li style="{{ $p->anulado ? 'opacity:.5' : '' }}"><div class="av"><i class="fa-solid fa-receipt"></i></div>
                    <div class="t"><b><a href="{{ route('conjunto.recibo', $p) }}" class="text-decoration-none" style="color:inherit">{{ dinero($p->valor) }} · {{ $p->numero }}</a></b><small>{{ $p->fecha->format('d/m/Y') }} · {{ $p->metodo }}{{ $p->anulado ? ' · ANULADO' : '' }}</small></div></li>
                @empty
                <li class="lp-empty">Sin pagos todavía.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <details class="lp-box">
        <summary class="fw-bold" style="cursor:pointer"><i class="fa-solid fa-pen me-1" style="color:#16b34a"></i> Editar datos de la unidad</summary>
        <form method="post" action="{{ route('conjunto.unidades.update', $u) }}" class="row g-2 mt-1">
            @csrf @method('PUT')
            @include('conjunto._form_unidad', ['u' => $u])
            <div class="col-12"><input type="hidden" name="activa" value="0"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="activa" value="1" id="act" @checked($u->activa)><label class="form-check-label" for="act">Activa (se le genera el cobro mensual)</label></div></div>
            <div class="col-12"><button class="btn btn-primary">Guardar</button></div>
        </form>
    </details>
</div>
@endsection
