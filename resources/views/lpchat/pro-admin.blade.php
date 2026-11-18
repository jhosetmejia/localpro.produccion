<!DOCTYPE html>
<html lang="es">
<head>
@include('lpchat._head')
<title>Plan Pro · LP Chat</title>
<style>
.doc{max-width:900px;margin:0 auto;padding:22px 16px 40px}
h1{font-family:Montserrat,Figtree,sans-serif;font-weight:900;margin:.2rem 0 1rem}
h1 b{color:var(--verde)}
.f{display:flex;gap:14px;align-items:flex-start;background:var(--panel2);border:1px solid var(--borde);border-radius:16px;padding:14px;margin-bottom:12px;flex-wrap:wrap}
.f img{width:140px;max-height:200px;object-fit:cover;border-radius:10px;cursor:zoom-in;background:#000}
.f .tx{flex:1;min-width:200px}
.e{display:inline-block;font-size:.72rem;font-weight:800;border-radius:999px;padding:2px 10px}
.e.pendiente{background:rgba(245,158,11,.15);color:#f5b942}.e.activo{background:var(--chip);color:var(--verde)}.e.rechazado{background:rgba(255,59,107,.15);color:#ff8aa6}
.bt{border:0;border-radius:12px;padding:8px 14px;font-weight:800;background:var(--verde);color:#0b0d0c;cursor:pointer}
.bt.rojo{background:#ff3b6b;color:#fff}
.ok{background:var(--chip);border:1px solid rgba(166,243,74,.3);border-radius:12px;padding:10px 14px;margin-bottom:14px}
</style>
</head>
<body>
<div class="doc">
    <a href="{{ route('lpchat.index') }}" style="text-decoration:none">← LP Chat</a>
    <h1>Plan <b>Pro</b> · comprobantes</h1>
    <p style="color:var(--txt2)">Al aprobar, la persona queda Pro por {{ config('lpchat.pro.dias') }} días (si ya era Pro, se le suman). Valor: ${{ number_format((int) config('lpchat.pro.precio'), 0, ',', '.') }} · Nequi {{ config('lpchat.pro.nequi') }}.</p>
    @if (session('ok'))<div class="ok">{{ session('ok') }}</div>@endif
    @forelse ($planes as $p)
        @php $u = $us[$p->usuario_id] ?? null; @endphp
        <div class="f">
            @if ($p->comprobante)<a href="{{ route('lpchat.pro.comprobante', $p->id) }}" target="_blank"><img src="{{ route('lpchat.pro.comprobante', $p->id) }}" alt="Comprobante"></a>@endif
            <div class="tx">
                <div style="font-weight:800;font-size:1.05rem">{{ $u->nombre ?? 'Cuenta borrada' }} <span class="e {{ $p->estado }}">{{ ucfirst($p->estado) }}</span></div>
                <div style="color:var(--txt2);font-size:.9rem">{{ $u->email ?? '' }} {{ $u->celular ?? '' }} · {{ $u->etiqueta ?? '' }}</div>
                <div style="color:var(--txt2);font-size:.85rem;margin-top:4px">Enviado {{ \Illuminate\Support\Carbon::parse($p->created_at)->timezone('America/Bogota')->format('d/m/Y g:i a') }}@if ($p->hasta) · Pro hasta {{ \Illuminate\Support\Carbon::parse($p->hasta)->timezone('America/Bogota')->format('d/m/Y') }}@endif</div>
                @if ($p->estado === 'pendiente')
                    <form method="post" action="{{ route('lpchat.pro.revisar', $p->id) }}" style="display:flex;gap:8px;margin-top:10px">
                        @csrf
                        <button class="bt" name="accion" value="aprobar">Aprobar {{ config('lpchat.pro.dias') }} días</button>
                        <button class="bt rojo" name="accion" value="rechazar" onclick="return confirm('¿Rechazar este comprobante?')">Rechazar</button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <p style="color:var(--txt2)">Todavía no hay comprobantes.</p>
    @endforelse
</div>
</body>
</html>
