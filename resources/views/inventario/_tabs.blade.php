{{-- Pestañas de Inventario: Existencias · Resumen · Conteo físico · Movimientos --}}
<style>
.inv-tabs{display:flex;gap:6px;overflow-x:auto;margin:-.6rem 0 1.2rem;padding-bottom:2px;scrollbar-width:none}
.inv-tabs::-webkit-scrollbar{display:none}
.inv-tabs a{flex:none;display:flex;align-items:center;gap:.45rem;padding:.55rem 1rem;border-radius:999px;border:1.5px solid var(--lp-line);background:#fff;color:var(--lp-ink);font-weight:800;font-size:.86rem;text-decoration:none;transition:.15s}
.inv-tabs a:hover{border-color:#bfeccb;color:var(--lp-ink)}
.inv-tabs a.on{background:var(--lp-black);border-color:var(--lp-black);color:#fff}
.inv-tabs a.on i{color:var(--lp-lime)}
.inv-tabs .pt{background:#ffcc33;color:#111;border-radius:999px;font-size:.68rem;padding:0 .45rem;font-weight:900}
</style>
@php $abiertos = \App\Models\InventarioConteo::where('estado', 'abierto')->count(); @endphp
<nav class="inv-tabs">
    <a href="{{ route('inventario.index') }}" class="{{ request()->routeIs('inventario.index') ? 'on' : '' }}"><i class="fa-solid fa-warehouse"></i>Existencias</a>
    <a href="{{ route('inventario.resumen') }}" class="{{ request()->routeIs('inventario.resumen') ? 'on' : '' }}"><i class="fa-solid fa-chart-simple"></i>Resumen por talla y categoría</a>
    <a href="{{ route('inventario.conteos.index') }}" class="{{ request()->routeIs('inventario.conteos.*') ? 'on' : '' }}"><i class="fa-solid fa-clipboard-check"></i>Conteo físico @if ($abiertos)<span class="pt">{{ $abiertos }} abierto{{ $abiertos > 1 ? 's' : '' }}</span>@endif</a>
    <a href="{{ route('inventario.movimientos') }}" class="{{ request()->routeIs('inventario.movimientos') ? 'on' : '' }}"><i class="fa-solid fa-clock-rotate-left"></i>Movimientos</a>
</nav>
