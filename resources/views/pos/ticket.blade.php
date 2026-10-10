<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tiquete {{ $venta->numero }}</title>
@include('pos._ticket-css')
</head>
<body>
<div class="acc">
    <button class="p" onclick="window.print()">🖨 Imprimir</button>
    @if ($venta->whatsapp_numero)
    <a class="w" target="_blank" href="https://wa.me/{{ $venta->whatsapp_numero }}?text={{ rawurlencode('Hola ' . ($venta->cliente_nombre ?: '') . ', gracias por su compra en ' . $negocio->nombre . '. Aquí está su factura ' . $venta->numero . ' por ' . dinero($venta->total) . ': ' . $venta->url_factura) }}">WhatsApp</a>
    @endif
    <a class="d" href="{{ route('pos.index') }}">Volver</a>
</div>

<div class="tk">
    @if ($negocio->logo_url)<img class="logo" src="{{ $negocio->logo_url }}" alt="">@endif
    <div class="c b big">{{ $negocio->nombre }}</div>
    <div class="c">
        @if ($negocio->nit)NIT {{ $negocio->nit }}<br>@endif
        @if ($negocio->direccion){{ $negocio->direccion }}@if($negocio->ciudad), {{ $negocio->ciudad }}@endif<br>@endif
        @if ($negocio->telefono)Tel. {{ $negocio->telefono }}@endif
    </div>
    <hr>
    <table>
        <tr><td>Venta</td><td class="r b">{{ $venta->numero }}</td></tr>
        <tr><td>Fecha</td><td class="r">{{ $venta->created_at->format('d/m/Y h:i a') }}</td></tr>
        <tr><td>Vendedor</td><td class="r">{{ $venta->user->name ?? '' }}</td></tr>
        <tr><td>Cliente</td><td class="r">{{ $venta->cliente_nombre ?: 'Consumidor final' }}</td></tr>
    </table>
    @if ($venta->estado === 'anulada')<hr><div class="c b xl">*** ANULADA ***</div>@endif
    <hr>
    <table>
        @foreach ($venta->items as $i)
        <tr><td colspan="2" class="b">{{ $i->descripcion }}</td></tr>
        <tr>
            <td>{{ trim(($i->talla ? 'T:' . $i->talla : '') . ' ' . ($i->color ?? '')) }} &nbsp; {{ $i->cantidad }} x {{ dinero($i->precio, false) }}</td>
            <td class="r">{{ dinero($i->subtotal, false) }}</td>
        </tr>
        @endforeach
    </table>
    <hr>
    <table>
        <tr><td>Subtotal</td><td class="r">{{ dinero($venta->subtotal, false) }}</td></tr>
        @if ($venta->descuento > 0)<tr><td>Descuento</td><td class="r">-{{ dinero($venta->descuento, false) }}</td></tr>@endif
        <tr class="b xl"><td>TOTAL</td><td class="r">{{ dinero($venta->total) }}</td></tr>
    </table>
    <hr>
    <table>
        @foreach ($venta->pagos as $p)
        <tr><td>{{ $p->metodo->nombre }}</td><td class="r">{{ dinero($p->monto + ($p->metodo->es_efectivo && $loop->first ? $venta->cambio : 0), false) }}</td></tr>
        @endforeach
        @if ($venta->cambio > 0)<tr class="b"><td>Cambio</td><td class="r">{{ dinero($venta->cambio, false) }}</td></tr>@endif
    </table>
    <hr>
    <div class="c">{{ $negocio->mensaje_tiquete }}</div>
    <div class="c" style="margin-top:2mm;font-size:10px">Desarrollado por Jhoset Mejía - DiCreWeb</div>
</div>

@if (request('imprimir'))
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
@endif
</body>
</html>
