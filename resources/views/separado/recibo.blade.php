<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Recibo de abono {{ $a->numero }}</title>
@include('pos._ticket-css')
</head>
<body>
<div class="acc">
    <button class="p" onclick="window.print()">🖨 Imprimir</button>
    @if ($s->telefono_wa && $a->monto > 0)<a class="w" target="_blank" href="https://wa.me/{{ $s->telefono_wa }}?text={{ rawurlencode($wa) }}">WhatsApp</a>@endif
    <a class="d" href="{{ route('separados.show', $s) }}">Volver</a>
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
    <div class="c b xl">{{ $a->monto < 0 ? 'DEVOLUCIÓN DE ABONO' : 'RECIBO DE ABONO' }}</div>
    <hr>
    <table>
        <tr><td>Recibo</td><td class="r b">{{ $a->numero }}</td></tr>
        <tr><td>Separado</td><td class="r b">{{ $s->numero }}</td></tr>
        <tr><td>Fecha</td><td class="r">{{ $a->created_at->format('d/m/Y h:i a') }}</td></tr>
        <tr><td>Atendió</td><td class="r">{{ $a->user->name ?? '' }}</td></tr>
        <tr><td>Cliente</td><td class="r">{{ $s->cliente_nombre }}</td></tr>
        @if ($s->cliente_telefono)<tr><td>WhatsApp</td><td class="r">{{ $s->cliente_telefono }}</td></tr>@endif
    </table>
    <hr>
    <table>
        @foreach ($s->items as $i)
        <tr><td colspan="2" class="b">{{ $i->descripcion }}</td></tr>
        <tr><td>{{ trim(($i->talla ? 'T:' . $i->talla : '') . ' ' . ($i->color ?? '')) }} &nbsp; {{ cantidad($i->cantidad) }} x {{ dinero($i->precio, false) }}</td><td class="r">{{ dinero($i->subtotal, false) }}</td></tr>
        @endforeach
    </table>
    <hr>
    <table>
        <tr><td>Total del separado</td><td class="r">{{ dinero($s->total, false) }}</td></tr>
        <tr class="b xl"><td>{{ $a->monto < 0 ? 'DEVUELTO' : 'ABONÓ HOY' }}</td><td class="r">{{ dinero(abs($a->monto)) }}</td></tr>
        <tr><td>Forma de pago</td><td class="r">{{ $a->metodo->nombre ?? '' }}</td></tr>
        @php $abonadoAlMomento = $s->total - $a->saldo_despues; @endphp
        <tr><td>Abonado a la fecha</td><td class="r">{{ dinero(max(0, $abonadoAlMomento), false) }}</td></tr>
        <tr class="b big"><td>LE FALTA</td><td class="r">{{ dinero($a->saldo_despues) }}</td></tr>
    </table>
    <hr>
    @if ($a->saldo_despues <= 0 && $a->monto > 0)
    <div class="c b">*** ¡PAGADO COMPLETO! ***<br>Puede reclamar su prenda.</div>
    @elseif ($s->vence_en && $a->monto > 0)
    <div class="c">Plazo para completar el pago:<br><b>{{ $s->vence_en->format('d/m/Y') }}</b></div>
    @endif
    <hr>
    <div class="c" style="font-size:10px">Conserve este recibo. Su prenda está separada a su nombre.</div>
    <div class="c" style="margin-top:2mm;font-size:10px">Sistema LocalProo · localproo.com</div>
</div>
@if (request('imprimir'))
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
@endif
</body>
</html>
