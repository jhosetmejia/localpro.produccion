<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Comanda #{{ $pedido->numero }}</title>
<style>
*{box-sizing:border-box}
body{font-family:ui-monospace,Menlo,Consolas,monospace;margin:0;padding:10px;color:#000;max-width:300px}
h1{font-size:22px;margin:0 0 2px;text-align:center}
.c{text-align:center}.s{font-size:12px}
hr{border:0;border-top:1px dashed #000;margin:8px 0}
.it{display:flex;gap:6px;font-size:16px;font-weight:700;margin:5px 0}
.it .q{min-width:30px}
.n{font-size:13px;font-weight:700;margin:-2px 0 4px 36px}
.no-print{margin-top:14px;display:flex;gap:6px}
.no-print button{flex:1;padding:10px;border-radius:10px;border:1px solid #999;background:#f4f4f4;font-weight:700}
@media print{.no-print{display:none}@page{margin:0}body{padding:4px}}
</style>
</head>
<body>
    <div class="c s">{{ $tienda?->nombre }}</div>
    <h1>PEDIDO #{{ $pedido->numero }}</h1>
    @if ($estacion)<div class="c"><b>{{ mb_strtoupper($estacion->nombre) }}</b></div>@endif
    <hr>
    @if ($pedido->cliente_nombre)<div><b>Cliente:</b> {{ $pedido->cliente_nombre }}</div>@endif
    @if ($pedido->mesa)<div><b>Mesa:</b> {{ $pedido->mesa }}</div>@endif
    <div class="s">{{ $pedido->mesero?->name }} · {{ now()->locale('es')->isoFormat('D MMM h:mm a') }}</div>
    @if ($pedido->nota)<div class="s"><b>Nota:</b> {{ $pedido->nota }}</div>@endif
    <hr>
    @foreach ($items->groupBy(fn ($i) => $i->estacion?->nombre ?: '') as $est => $grupo)
        @if (!$estacion && $est)<div class="s"><b>— {{ mb_strtoupper($est) }} —</b></div>@endif
        @foreach ($grupo as $i)
        <div class="it"><span class="q">{{ cantidad($i->cantidad) }}x</span><span>{{ $i->nombre }}@if ($i->etiqueta) ({{ $i->etiqueta }})@endif</span></div>
        @if ($i->nota)<div class="n">** {{ $i->nota }}</div>@endif
        @endforeach
    @endforeach
    <hr>
    <div class="c s">{{ $pedido->pagado ? 'PAGADO' : 'PENDIENTE DE PAGO' }}</div>
    <div class="no-print"><button onclick="window.print()">Imprimir</button><button onclick="window.close()">Cerrar</button></div>
    @if (request()->boolean('imprimir'))<script>window.addEventListener('load', function () { window.print(); setTimeout(function () { window.close(); }, 800); });</script>@endif
</body>
</html>
