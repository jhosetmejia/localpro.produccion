<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Factura {{ $venta->numero }} · {{ $negocio->nombre }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
  :root{--g:linear-gradient(135deg,#a6f34a,#2fd15a 45%,#16b34a);--ink:#151a17;--mut:#5b665e;--line:#e3efe5}
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:#f4f8f5;color:var(--ink);padding:24px 14px}
  .f{max-width:640px;margin:0 auto;background:#fff;border-radius:28px;overflow:hidden;box-shadow:0 30px 60px -36px rgba(22,179,74,.6);border:1.5px solid var(--line)}
  .hd{background:#0b0d0c;color:#fff;padding:26px;display:flex;gap:16px;align-items:center;position:relative;overflow:hidden}
  .hd::after{content:"";position:absolute;width:240px;height:240px;border-radius:50%;background:radial-gradient(circle,rgba(47,209,90,.45),transparent 70%);right:-70px;top:-90px}
  .logo{width:68px;height:68px;border-radius:18px;background:#fff;display:grid;place-items:center;overflow:hidden;flex:none;position:relative;z-index:1}
  .logo img{max-width:90%;max-height:90%;object-fit:contain}
  .logo span{font-weight:800;font-size:1.6rem;color:#16b34a}
  .hd h1{font-size:1.35rem;font-weight:800;position:relative;z-index:1}
  .hd p{color:#a9b5ad;font-size:.85rem;position:relative;z-index:1}
  .bd{padding:24px 26px}
  .meta{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px}
  .meta div{background:#f7fbf8;border-radius:14px;padding:10px 14px}
  .meta small{display:block;color:var(--mut);font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em}
  .meta b{font-size:.95rem}
  .it{display:flex;justify-content:space-between;gap:12px;padding:12px 0;border-bottom:1px dashed var(--line)}
  .it small{color:var(--mut);display:block;font-size:.82rem}
  .it b{white-space:nowrap}
  .tot{margin-top:16px}
  .tot div{display:flex;justify-content:space-between;padding:4px 0;color:var(--mut);font-weight:600}
  .tot .big{background:var(--g);color:#0b0d0c;border-radius:16px;padding:14px 16px;margin-top:10px;font-size:1.25rem;font-weight:800}
  .pay{margin-top:16px;font-size:.88rem;color:var(--mut)}
  .anul{background:#ffe9f3;color:#b0206a;text-align:center;font-weight:800;padding:10px;border-radius:12px;margin-bottom:16px}
  .ft{text-align:center;padding:0 26px 24px;color:var(--mut);font-size:.85rem}
  .acc{max-width:640px;margin:16px auto 0;display:flex;gap:10px}
  .acc button,.acc a{flex:1;border:0;border-radius:999px;padding:13px;font:inherit;font-weight:800;cursor:pointer;text-align:center;text-decoration:none}
  .p{background:var(--g);color:#0b0d0c}.d{background:#0b0d0c;color:#fff}
  .sig{text-align:center;color:#93a097;font-size:.75rem;margin-top:14px}
  @media print{body{background:#fff;padding:0}.f{box-shadow:none;border:0}.acc,.sig{display:none}}
</style>
</head>
<body>
<div class="f">
    <div class="hd">
        <div class="logo">@if ($negocio->logo_url)<img src="{{ $negocio->logo_url }}" alt="{{ $negocio->nombre }}">@else<span>{{ mb_substr($negocio->nombre, 0, 1) }}</span>@endif</div>
        <div>
            <h1>{{ $negocio->nombre }}</h1>
            <p>@if($negocio->nit)NIT {{ $negocio->nit }} · @endif{{ $negocio->direccion }}@if($negocio->ciudad), {{ $negocio->ciudad }}@endif @if($negocio->telefono)<br>Tel. {{ $negocio->telefono }}@endif</p>
        </div>
    </div>
    <div class="bd">
        @if ($venta->estado === 'anulada')<div class="anul">Esta venta fue anulada</div>@endif
        <div class="meta">
            <div><small>Comprobante</small><b>{{ $venta->numero }}</b></div>
            <div><small>Fecha</small><b>{{ $venta->created_at->format('d/m/Y h:i a') }}</b></div>
            <div><small>Cliente</small><b>{{ $venta->cliente_nombre ?: 'Consumidor final' }}</b></div>
            <div><small>Atendió</small><b>{{ $venta->user->name ?? '' }}</b></div>
        </div>
        @foreach ($venta->items as $i)
        <div class="it">
            <div><b style="white-space:normal">{{ $i->descripcion }}</b><small>{{ trim(($i->talla ? 'Talla ' . $i->talla : '') . ($i->color ? ' · ' . $i->color : '')) }} — {{ $i->cantidad }} × ${{ number_format($i->precio, 0, ',', '.') }}</small></div>
            <b>${{ number_format($i->subtotal, 0, ',', '.') }}</b>
        </div>
        @endforeach
        <div class="tot">
            <div><span>Subtotal</span><span>${{ number_format($venta->subtotal, 0, ',', '.') }}</span></div>
            @if ($venta->descuento > 0)<div><span>Descuento</span><span>-${{ number_format($venta->descuento, 0, ',', '.') }}</span></div>@endif
            <div class="big"><span>Total</span><span>${{ number_format($venta->total, 0, ',', '.') }}</span></div>
        </div>
        <div class="pay">Pagado con: {{ $venta->pagos->map(fn ($p) => $p->metodo->nombre)->implode(', ') }}@if($venta->cambio > 0) · Cambio ${{ number_format($venta->cambio, 0, ',', '.') }}@endif</div>
    </div>
    <div class="ft">{{ $negocio->mensaje_tiquete }}</div>
</div>
<div class="acc">
    <button class="p" onclick="window.print()">Guardar PDF / Imprimir</button>
    @if ($negocio->telefono)
    <a class="d" target="_blank" href="https://wa.me/{{ (strlen(preg_replace('/\D/', '', $negocio->telefono)) === 10 ? '57' : '') . preg_replace('/\D/', '', $negocio->telefono) }}">Escribir a la tienda</a>
    @endif
</div>
<p class="sig">Desarrollado por Jhoset Mejía - DiCreWeb</p>
</body>
</html>
