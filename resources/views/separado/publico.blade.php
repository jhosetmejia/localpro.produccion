<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Mi separado {{ $s->numero }} · {{ $tienda->nombre ?? 'LocalPro' }}</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:#f4f8f5;color:#151a17;padding:18px}
.c{max-width:520px;margin:0 auto}
.hd{text-align:center;margin-bottom:14px}.hd img{width:60px;height:60px;border-radius:16px;object-fit:cover}
.hd b{display:block;font-size:1.2rem;margin-top:6px}
.card{background:#0b0d0c;color:#fff;border-radius:24px;padding:20px}
.card small{color:#9fe3b0;font-weight:700}
.card h1{font-size:1.4rem;margin:2px 0 14px}
.n{display:flex;gap:8px}.n div{flex:1;background:#1a211d;border-radius:14px;padding:10px}
.n span{font-size:.72rem;color:#9aa69e;font-weight:700}.n b{display:block;font-size:1.15rem}
.n .f b{color:#ffcf5c}.n .a b{color:#2fd15a}
.bar{height:12px;border-radius:99px;background:#26302a;overflow:hidden;margin-top:14px}.bar i{display:block;height:100%;background:linear-gradient(90deg,#a6f34a,#16b34a)}
.est{margin-top:12px;font-weight:800;text-align:center}
.box{background:#fff;border-radius:20px;padding:16px;margin-top:12px;border:1.5px solid #e3efe5}
.box h2{font-size:1rem;margin-bottom:8px}
.it{display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid #eef3ef;font-size:.92rem}
.it:last-child{border:0}.it small{display:block;color:#6b7570;font-weight:600}
.pie{text-align:center;color:#6b7570;font-size:.78rem;margin-top:16px}
</style>
</head>
<body>
@php $f = fn ($v) => dinero($v); @endphp
<div class="c">
    <div class="hd">@if ($tienda?->logo_url)<img src="{{ $tienda->logo_url }}" alt="">@endif<b>{{ $tienda->nombre ?? '' }}</b></div>
    <div class="card">
        <small>Separado {{ $s->numero }}</small>
        <h1>Hola, {{ strtok($s->cliente_nombre, ' ') }} 👋</h1>
        <div class="n">
            <div><span>Total</span><b>{{ $f($s->total) }}</b></div>
            <div class="a"><span>Abonado</span><b>{{ $f($s->abonado) }}</b></div>
            <div class="f"><span>Le falta</span><b>{{ $f($s->saldo) }}</b></div>
        </div>
        @if ($s->estado === 'activo')<div class="bar"><i style="width:{{ max(3, $s->porcentaje) }}%"></i></div>@endif
        <div class="est">
            @if ($s->estado === 'entregado') ✅ Ya recibió su prenda. ¡Gracias por su compra!
            @elseif ($s->estado === 'cancelado') Este separado fue cancelado.
            @elseif ($s->pagado) 🎉 ¡Pagado completo! Ya puede pasar por su prenda.
            @else Tiene su prenda separada{{ $s->vence_en ? ' hasta el ' . $s->vence_en->format('d/m/Y') : '' }}.
            @endif
        </div>
    </div>
    <div class="box">
        <h2>Prendas separadas</h2>
        @foreach ($s->items as $i)
        <div class="it"><div>{{ $i->descripcion }}<small>{{ $i->etiqueta ?: 'Única' }} · {{ cantidad($i->cantidad) }} und</small></div><b>{{ $f($i->subtotal) }}</b></div>
        @endforeach
    </div>
    <div class="box">
        <h2>Sus abonos</h2>
        @forelse ($s->abonos as $a)
        <div class="it"><div>{{ $a->monto < 0 ? 'Devolución' : 'Abono' }} {{ $a->numero }}<small>{{ $a->created_at->format('d/m/Y') }}</small></div><b>{{ $f($a->monto) }}</b></div>
        @empty
        <div class="it">Aún no hay abonos.</div>
        @endforelse
    </div>
    <div class="pie">{{ $tienda->direccion ?? '' }} · Sistema LocalProo</div>
</div>
</body>
</html>
