<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $dev->codigo }}</title>
@include('pos._ticket-css')
</head>
<body>
<div class="acc">
    <button class="p" onclick="window.print()">🖨 Imprimir</button>
    @if ($dev->tipo === 'cambio')<a class="p" href="{{ route('pos.index') }}">Ir al POS</a>@endif
    <a class="d" href="{{ route('pos.ventas') }}">Volver</a>
</div>
@if (session('success'))<div class="acc" style="font-weight:700;text-align:center;display:block">{{ session('success') }}</div>@endif
<div class="tk">
    @if ($negocio->logo_url)<img class="logo" src="{{ $negocio->logo_url }}" alt="">@endif
    <div class="c b big">{{ $negocio->nombre }}</div>
    @if ($negocio->nit)<div class="c">NIT {{ $negocio->nit }}</div>@endif
    <hr>
    <div class="c b xl">{{ $dev->tipo === 'cambio' ? 'NOTA CRÉDITO' : 'DEVOLUCIÓN' }}</div>
    <div class="c b big">{{ $dev->codigo }}</div>
    <hr>
    <table>
        <tr><td>Venta original</td><td class="r">{{ $dev->venta->numero }}</td></tr>
        <tr><td>Fecha</td><td class="r">{{ $dev->created_at->format('d/m/Y h:i a') }}</td></tr>
        <tr><td>Atendió</td><td class="r">{{ $dev->user->name ?? '' }}</td></tr>
        @if ($dev->venta->cliente_nombre)<tr><td>Cliente</td><td class="r">{{ $dev->venta->cliente_nombre }}</td></tr>@endif
    </table>
    <hr>
    <table>
        @foreach ($dev->items as $i)
        <tr><td colspan="2" class="b">{{ $i->ventaItem->descripcion ?? '' }}</td></tr>
        <tr><td>{{ trim(($i->ventaItem->talla ? 'T:' . $i->ventaItem->talla : '') . ' ' . ($i->ventaItem->color ?? '')) }} &nbsp; {{ $i->cantidad }} und</td><td class="r">{{ dinero($i->subtotal, false) }}</td></tr>
        @endforeach
    </table>
    <hr>
    <table>
        <tr class="b xl"><td>TOTAL</td><td class="r">{{ dinero($dev->total) }}</td></tr>
        @if ($dev->tipo === 'cambio')
        <tr class="b"><td>Saldo disponible</td><td class="r">{{ dinero($dev->saldo_disponible) }}</td></tr>
        @else
        <tr><td>Devuelto en</td><td class="r">{{ $dev->metodo->nombre ?? '' }}</td></tr>
        @endif
    </table>
    @if ($dev->motivo)<hr><div>Motivo: {{ $dev->motivo }}</div>@endif
    <hr>
    @if ($dev->tipo === 'cambio')<div class="c">Presente el código <b>{{ $dev->codigo }}</b> para usar su saldo en otra compra.</div>@endif
    <div class="c" style="margin-top:2mm;font-size:10px">Desarrollado por Jhoset Mejía - DiCreWeb</div>
</div>
</body>
</html>
