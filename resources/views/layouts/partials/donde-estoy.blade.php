{{-- Para quien es asesor de LocalPro y también trabaja en una tienda (ej. Abril Store):
     le dice claro en cuál de los dos está y le deja pasar al otro con un toque. --}}
@php
    $deUser = auth()->user();
    $deTienda = \App\Support\TiendaActual::tienda();
    $deMostrar = $deUser && $deUser->asesor && $deTienda && !session()->has('tienda_visitada');
@endphp
@if ($deMostrar)
@php
    $enAsesor = request()->routeIs('asesor.*') || request()->routeIs('curso.*');
    $deRol = $deUser->hasRole('dueno') ? 'dueño(a)' : 'vendedor(a)';
@endphp
<div class="lp-donde {{ $enAsesor ? 'asesor' : 'tienda' }}">
    <div class="lp-donde-i">
        @if ($enAsesor)
            <span class="lp-donde-ic"><i class="fa-solid fa-handshake"></i></span>
        @elseif ($deTienda->logo_url)
            <img class="lp-donde-ic" src="{{ $deTienda->logo_url }}" alt="">
        @else
            <span class="lp-donde-ic"><i class="fa-solid fa-store"></i></span>
        @endif
        <div class="lp-donde-t">
            <small>{{ $enAsesor ? 'Usted está en' : 'Usted está en la tienda' }}</small>
            <b>{{ $enAsesor ? 'Su panel de asesor LocalPro' : $deTienda->nombre }}</b>
            <span>{{ $enAsesor ? 'Aquí NO se vende ni se cobra de la tienda' : 'Trabajando como ' . $deRol . ' · lo que venda queda en esta tienda' }}</span>
        </div>
    </div>
    @if ($enAsesor)
        <a href="{{ route('panel') }}" class="lp-donde-b"><i class="fa-solid fa-store me-1"></i>Ir a {{ \Illuminate\Support\Str::limit($deTienda->nombre, 18) }}</a>
    @else
        <a href="{{ route('asesor.index') }}" class="lp-donde-b"><i class="fa-solid fa-handshake me-1"></i>Ir a asesor LocalPro</a>
    @endif
</div>
<style>
.lp-donde{display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;padding:.65rem 1.1rem;border-bottom:1px solid rgba(0,0,0,.06)}
.lp-donde.tienda{background:linear-gradient(90deg,#eafbe0,#f6fff0);color:#0b0d0c;border-left:6px solid #2fd15a}
.lp-donde.asesor{background:linear-gradient(90deg,#14112a,#231a45);color:#fff;border-left:6px solid #a78bfa}
.lp-donde-i{display:flex;align-items:center;gap:.7rem;min-width:0}
.lp-donde-ic{width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex:0 0 42px;object-fit:cover;font-size:1.15rem}
.lp-donde.tienda .lp-donde-ic{background:#2fd15a;color:#fff}
.lp-donde.asesor .lp-donde-ic{background:#a78bfa;color:#14112a}
.lp-donde-t{display:flex;flex-direction:column;line-height:1.15;min-width:0}
.lp-donde-t small{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;opacity:.7}
.lp-donde-t b{font-size:1.1rem;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.lp-donde.tienda .lp-donde-t b{color:#15803d}
.lp-donde.asesor .lp-donde-t b{color:#c4b5fd}
.lp-donde-t span{font-size:.78rem;opacity:.8}
.lp-donde-b{font-weight:800;font-size:.82rem;border-radius:99px;padding:.4rem .9rem;text-decoration:none;white-space:nowrap}
.lp-donde.tienda .lp-donde-b{background:#14112a;color:#c4b5fd}
.lp-donde.asesor .lp-donde-b{background:#2fd15a;color:#0b0d0c}
@media (max-width:575px){.lp-donde-b{width:100%;text-align:center}}
</style>
@endif
