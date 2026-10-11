@php
    // Marca como activo el enlace de la sección actual
    $on = fn ($patron) => request()->routeIs($patron) ? 'active' : '';
    $tiendaMenu = \App\Support\TiendaActual::tienda();
@endphp
<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion lp-side" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">

                @if (auth()->user()->es_superadmin)
                <div class="sb-sidenav-menu-heading">LocalPro</div>
                <a class="nav-link {{ $on('admin.*') }}" href="{{ route('admin.tiendas.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-crown"></i></div>
                    Tiendas inscritas
                </a>
                @endif

                <div class="sb-sidenav-menu-heading">{{ $tiendaMenu ? \Illuminate\Support\Str::limit($tiendaMenu->nombre, 22) : 'Inicio' }}</div>
                <a class="nav-link {{ $on('panel') }}" href="{{ route('panel') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-house"></i></div>
                    Panel
                </a>

                @can('usar-pos')
                <a class="nav-link {{ $on('pos.index') }}" href="{{ route('pos.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-cash-register"></i></div>
                    Vender (POS)
                </a>
                <a class="nav-link {{ $on('cajas.show') || $on('cajas.abrir') || $on('cajas.cerrar') ? 'active' : '' }}" href="{{ route('cajas.actual') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-vault"></i></div>
                    Mi caja
                </a>
                @endcan
                @canany(['usar-pos', 'ver-cajas'])
                <a class="nav-link {{ $on('pos.ventas') }}" href="{{ route('pos.ventas') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-receipt"></i></div>
                    Ventas
                </a>
                @endcanany
                @can('ver-cajas-todas')
                <a class="nav-link {{ $on('cajas.index') }}" href="{{ route('cajas.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-scale-balanced"></i></div>
                    Cierres de caja
                </a>
                @endcan

                @canany(['ver-inventario', 'ver-producto', 'ver-categoria', 'ver-entradas'])
                <div class="sb-sidenav-menu-heading">Inventario</div>
                @endcanany
                @can('ver-producto')
                <a class="nav-link {{ $on('productos.*') }}" href="{{ route('productos.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid {{ $tiendaMenu && $tiendaMenu->usa_tallas ? 'fa-shirt' : 'fa-box' }}"></i></div>
                    Productos
                </a>
                @endcan
                @can('ver-inventario')
                <a class="nav-link {{ $on('inventario.*') }}" href="{{ route('inventario.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-warehouse"></i></div>
                    Existencias
                </a>
                @endcan
                @can('ver-entradas')
                <a class="nav-link {{ $on('entradas.*') }}" href="{{ route('entradas.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-truck-ramp-box"></i></div>
                    Entradas de mercancía
                </a>
                @endcan
                @can('ver-categoria')
                <a class="nav-link {{ $on('categorias.*') }}" href="{{ route('categorias.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-tags"></i></div>
                    Categorías
                </a>
                @endcan

                @canany(['ver-cliente', 'ver-proveedore'])
                <div class="sb-sidenav-menu-heading">Contactos</div>
                @endcanany
                @can('ver-cliente')
                <a class="nav-link {{ $on('clientes.*') }}" href="{{ route('clientes.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-people-group"></i></div>
                    Clientes
                </a>
                @endcan
                @can('ver-proveedore')
                <a class="nav-link {{ $on('proveedores.*') }}" href="{{ route('proveedores.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-handshake"></i></div>
                    Proveedores
                </a>
                @endcan

                @canany(['ver-user', 'ver-metodo-pago', 'editar-negocio'])
                <div class="sb-sidenav-menu-heading">Mi negocio</div>
                @endcanany
                @can('editar-negocio')
                <a class="nav-link {{ $on('negocio.*') }}" href="{{ route('negocio.edit') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-store"></i></div>
                    Datos del negocio
                </a>
                @endcan
                @can('ver-metodo-pago')
                <a class="nav-link {{ $on('metodos-pago.*') }}" href="{{ route('metodos-pago.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-credit-card"></i></div>
                    Métodos de pago
                </a>
                @endcan
                @can('ver-user')
                <a class="nav-link {{ $on('users.*') }}" href="{{ route('users.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-user-gear"></i></div>
                    Usuarios
                </a>
                @endcan

            </div>
        </div>
        <div class="sb-sidenav-footer">
            <span class="lp-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            <div style="min-width:0;flex:1">
                <div class="small">Sesión iniciada</div>
                <b style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ auth()->user()->name }}</b>
            </div>
        </div>
        <div class="lp-side-acciones">
            @can('ver-perfil')
            <a href="{{ route('profile.index') }}"><i class="fa-regular fa-user"></i> Mi perfil</a>
            @endcan
            <a href="{{ route('logout') }}" class="salir"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </div>
    </nav>
</div>
