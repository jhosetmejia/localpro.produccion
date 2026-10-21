{{-- Pestañas de Pedidos según lo que puede hacer cada rol --}}
@php
    $u = auth()->user();
    $adm = $u->es_superadmin || $u->can('pedidos-administrar');
    $tabs = array_filter([
        ($adm || $u->can('pedidos-tomar')) ? ['pedidos.mesero', 'pedidos.mesero', 'fa-bell-concierge', 'Mesero'] : null,
        ($adm || $u->can('pedidos-cobrar')) ? ['pedidos.caja', 'pedidos.caja', 'fa-cash-register', 'Caja'] : null,
        ($adm || $u->can('pedidos-preparar')) ? ['pedidos.estacion', 'pedidos.estacion', 'fa-fire-burner', 'Estaciones'] : null,
        $adm ? ['pedidos.cierre', 'pedidos.cierre', 'fa-scale-balanced', 'Cierre'] : null,
        $adm ? ['pedidos.configurar', 'pedidos.configurar', 'fa-sliders', 'Configurar'] : null,
        ['pedidos.guia', 'pedidos.guia', 'fa-book-open', 'Cómo funciona'],
    ]);
@endphp
<style>
.pd-nav{display:flex;gap:.4rem;flex-wrap:wrap;margin:-.2rem 0 1rem}
.pd-nav a{border-radius:999px;padding:.45rem .95rem;font-weight:800;font-size:.86rem;text-decoration:none;background:#fff;border:1.5px solid #e3ebe6;color:#151a17}
.pd-nav a.on{background:#0b0d0c;color:#a6f34a;border-color:#0b0d0c}
.pd-nav i{margin-right:.35rem}
.pd-est{display:inline-block;border-radius:999px;padding:.15rem .6rem;font-size:.75rem;font-weight:800}
</style>
<nav class="pd-nav">
    @foreach ($tabs as [$r, $pat, $ic, $txt])
    <a href="{{ route($r) }}" class="{{ request()->routeIs($pat) ? 'on' : '' }}"><i class="fa-solid {{ $ic }}"></i>{{ $txt }}</a>
    @endforeach
</nav>
