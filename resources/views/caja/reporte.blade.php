<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Cuadre de caja #{{ $caja->id }}</title>
@include('pos._ticket-css')
</head>
<body>
<div class="acc">
    <button class="p" onclick="window.print()">🖨 Imprimir</button>
    <a class="d" href="{{ route('cajas.index') }}">Volver</a>
</div>
<div class="tk">
    @if ($negocio->logo_url)<img class="logo" src="{{ $negocio->logo_url }}" alt="">@endif
    <div class="c b big">{{ $negocio->nombre }}</div>
    <div class="c b">CUADRE DE CAJA #{{ $caja->id }}</div>
    <hr>
    <table>
        <tr><td>Vendedor</td><td class="r">{{ $caja->user->name }}</td></tr>
        <tr><td>Apertura</td><td class="r">{{ $caja->abierta_en->format('d/m/Y h:i a') }}</td></tr>
        <tr><td>Cierre</td><td class="r">{{ $caja->cerrada_en?->format('d/m/Y h:i a') ?? 'ABIERTA' }}</td></tr>
        <tr><td>Ventas</td><td class="r">{{ $numVentas }} @if($anuladas)({{ $anuladas }} anuladas)@endif</td></tr>
        @php $abCaja = $caja->totalAbonos(); @endphp
        @if ($abCaja != 0)<tr><td>Abonos de separados</td><td class="r">{{ dinero($abCaja, false) }}</td></tr>@endif
    </table>
    <hr>
    <div class="b">VENTAS POR MÉTODO</div>
    <table>
        @forelse ($totales as $t)
        <tr><td>{{ $t->nombre }}</td><td class="r">{{ dinero($t->total, false) }}</td></tr>
        @empty
        <tr><td>Sin ventas</td><td class="r">0</td></tr>
        @endforelse
        @foreach ($reembolsos as $r)
        <tr><td>Devuelto en {{ $r->nombre }}</td><td class="r">-{{ dinero($r->total, false) }}</td></tr>
        @endforeach
        <tr class="b"><td>TOTAL VENDIDO</td><td class="r">{{ dinero($totales->sum('total')) }}</td></tr>
    </table>
    <hr>
    <div class="b">EFECTIVO</div>
    <table>
        <tr><td>Base inicial</td><td class="r">{{ dinero($caja->monto_inicial, false) }}</td></tr>
        <tr><td>Esperado en caja</td><td class="r">{{ dinero($caja->efectivo_esperado ?? $caja->efectivoEsperado(), false) }}</td></tr>
        <tr><td>Contado</td><td class="r">{{ $caja->efectivo_contado !== null ? dinero($caja->efectivo_contado, false) : '—' }}</td></tr>
        @if ($caja->diferencia !== null)
        <tr class="b xl"><td>{{ abs($caja->diferencia) < 1 ? 'CUADRA' : ($caja->diferencia < 0 ? 'FALTANTE' : 'SOBRANTE') }}</td><td class="r">{{ dinero(abs($caja->diferencia), false) }}</td></tr>
        @endif
    </table>
    @if ($caja->nota_cierre)<hr><div>Nota: {{ $caja->nota_cierre }}</div>@endif
    <hr>
    <br><br>
    <div class="c">______________________<br>Firma vendedor</div>
    <br>
    <div class="c">______________________<br>Firma encargado</div>
</div>
</body>
</html>
