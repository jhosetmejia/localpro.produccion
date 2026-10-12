{{-- Pestañas de "Mis redes" --}}
@php $sinLeerTab = \App\Models\RedConversacion::where('no_leidos', '>', 0)->count(); @endphp
<div class="rd-tabs">
    @canany(['responder-redes', 'editar-negocio'])
    <a href="{{ route('redes.index') }}" class="{{ request()->routeIs('redes.index') ? 'on' : '' }}"><i class="fa-regular fa-comments"></i> Mensajes @if ($sinLeerTab)<span class="n">{{ $sinLeerTab }}</span>@endif</a>
    @endcanany
    @canany(['publicar-redes', 'editar-negocio'])
    <a href="{{ route('redes.publicar') }}" class="{{ request()->routeIs('redes.publicar') ? 'on' : '' }}"><i class="fa-regular fa-paper-plane"></i> Publicar</a>
    @endcanany
    @can('editar-negocio')
    <a href="{{ route('redes.cuentas') }}" class="{{ request()->routeIs('redes.cuentas') ? 'on' : '' }}"><i class="fa-solid fa-link"></i> Cuentas</a>
    @endcan
</div>
@once
<style>
.rd-tabs{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1rem;background:#fff;border:1.5px solid #e3ebe6;border-radius:999px;padding:.3rem;width:fit-content;max-width:100%}
.rd-tabs a{display:inline-flex;align-items:center;gap:.45rem;border-radius:999px;padding:.55rem 1.1rem;font-weight:800;text-decoration:none;color:#33403a;font-size:.92rem}
.rd-tabs a.on{background:#0b0d0c;color:#a6f34a}
.rd-tabs .n{background:#ff3b6b;color:#fff;border-radius:999px;font-size:.72rem;padding:.05rem .45rem;min-width:20px;text-align:center}
.rd-fb{color:#1877f2}.rd-ig{background:linear-gradient(45deg,#f9a825,#e91e63,#8e24aa);-webkit-background-clip:text;background-clip:text;color:transparent}
.rd-ico{width:20px;height:20px;border-radius:6px;display:inline-grid;place-items:center;color:#fff;font-size:.72rem;flex:none}
.rd-ico.facebook{background:#1877f2}.rd-ico.instagram{background:linear-gradient(45deg,#f9a825,#e91e63 55%,#8e24aa)}
</style>
@endonce
