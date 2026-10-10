@php
    // Marca como activo el enlace de la sección actual
    $on = fn ($patron) => request()->routeIs($patron) ? 'active' : '';
@endphp
<div id="layoutSidenav_nav">
    <nav class="sb-sidenav accordion lp-side" id="sidenavAccordion">
        <div class="sb-sidenav-menu">
            <div class="nav">

                <div class="sb-sidenav-menu-heading">Inicio</div>
                <a class="nav-link {{ $on('panel') }}" href="{{ route('panel') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-house"></i></div>
                    Panel
                </a>

                @can('usar-pos')
                <div class="sb-sidenav-menu-heading">Punto de venta</div>
                <a class="nav-link {{ $on('pos.index') }}" href="{{ route('pos.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-cash-register"></i></div>
                    Vender (POS)
                </a>
                <a class="nav-link {{ $on('cajas.*') }}" href="{{ route('cajas.actual') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-vault"></i></div>
                    Mi caja
                </a>
                @endcan
                @canany(['usar-pos', 'ver-cajas'])
                <a class="nav-link {{ $on('pos.ventas') }}" href="{{ route('pos.ventas') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-receipt"></i></div>
                    Ventas POS
                </a>
                @endcanany
                @can('ver-cajas-todas')
                <a class="nav-link {{ $on('cajas.index') }}" href="{{ route('cajas.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-scale-balanced"></i></div>
                    Cierres de caja
                </a>
                @endcan

                <div class="sb-sidenav-menu-heading">Compras</div>

                @can('ver-compra')
                <a class="nav-link {{ request()->routeIs('compras.*') ? '' : 'collapsed' }}" href="#" data-bs-toggle="collapse" data-bs-target="#collapseCompras" aria-expanded="{{ request()->routeIs('compras.*') ? 'true' : 'false' }}" aria-controls="collapseCompras">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-truck-ramp-box"></i></div>
                    Compras
                    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                </a>
                <div class="collapse {{ request()->routeIs('compras.*') ? 'show' : '' }}" id="collapseCompras" data-bs-parent="#sidenavAccordion">
                    <nav class="sb-sidenav-menu-nested nav">
                        <a class="nav-link {{ $on('compras.index') }}" href="{{ route('compras.index') }}">Ver compras</a>
                        @can('crear-compra')
                        <a class="nav-link {{ $on('compras.create') }}" href="{{ route('compras.create') }}">Nueva compra</a>
                        @endcan
                    </nav>
                </div>
                @endcan

                <div class="sb-sidenav-menu-heading">Inventario</div>

                @can('ver-inventario')
                <a class="nav-link {{ $on('inventario.*') }}" href="{{ route('inventario.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-warehouse"></i></div>
                    Inventario
                </a>
                @endcan

                @can('ver-producto')
                <a class="nav-link {{ $on('productos.*') }}" href="{{ route('productos.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
                    Productos
                </a>
                @endcan

                @can('ver-categoria')
                <a class="nav-link {{ $on('categorias.*') }}" href="{{ route('categorias.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-tags"></i></div>
                    Categorías
                </a>
                @endcan

                @can('ver-marca')
                <a class="nav-link {{ $on('marcas.*') }}" href="{{ route('marcas.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-award"></i></div>
                    Marcas
                </a>
                @endcan

                @can('ver-presentacione')
                <a class="nav-link {{ $on('presentaciones.*') }}" href="{{ route('presentaciones.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-box-open"></i></div>
                    Presentaciones
                </a>
                @endcan

                <div class="sb-sidenav-menu-heading">Contactos</div>

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

                @canany(['ver-user', 'ver-role', 'ver-metodo-pago', 'editar-negocio'])
                <div class="sb-sidenav-menu-heading">Administración</div>
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

                @can('ver-role')
                <a class="nav-link {{ $on('roles.*') }}" href="{{ route('roles.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-user-shield"></i></div>
                    Roles y permisos
                </a>
                @endcan

            </div>
        </div>
        <div class="sb-sidenav-footer">
            <span class="lp-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            <div>
                <div class="small">Sesión iniciada</div>
                <b>{{ auth()->user()->name }}</b>
            </div>
        </div>
    </nav>
</div>
