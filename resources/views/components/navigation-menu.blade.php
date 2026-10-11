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
                <a class="nav-link {{ $on('admin.inicio') }}" href="{{ route('admin.inicio') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-gauge-high"></i></div>
                    Panel LocalPro
                </a>
                <a class="nav-link {{ $on('admin.tiendas.*') }}" href="{{ route('admin.tiendas.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-crown"></i></div>
                    Empresas inscritas
                </a>
                <a class="nav-link {{ $on('admin.planes.*') }}" href="{{ route('admin.planes.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-gem"></i></div>
                    Planes y cobros
                </a>
                @php $impPend = \App\Models\Importacion::where('tipo', 'solicitud')->where('estado', 'pendiente')->count(); @endphp
                <a class="nav-link {{ $on('admin.importaciones') }}" href="{{ route('admin.importaciones') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-file-import"></i></div>
                    Cargar productos @if ($impPend)<span class="badge rounded-pill ms-auto" style="background:#a6f34a;color:#0b0d0c">{{ $impPend }}</span>@endif
                </a>
                <a class="nav-link {{ $on('admin.pagos') }}" href="{{ route('admin.pagos') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                    Pagos recibidos
                </a>
                <a class="nav-link {{ $on('admin.usuarios') }}" href="{{ route('admin.usuarios') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-users"></i></div>
                    Usuarios del sistema
                </a>
                <a class="nav-link {{ $on('admin.red.*') }}" href="{{ route('admin.red.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-sitemap"></i></div>
                    Red comercial
                </a>
                <a class="nav-link" href="{{ route('asesor.simulador') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-calculator"></i></div>
                    Simulador de ganancias
                </a>
                <a class="nav-link" href="{{ route('asesor.capacitacion') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    Capacitación del equipo
                </a>
                @endif

                @if (auth()->user()->asesor)
                <div class="sb-sidenav-menu-heading">Red comercial</div>
                <a class="nav-link {{ $on('asesor.index') }}" href="{{ route('asesor.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-gauge"></i></div>
                    Mi panel
                </a>
                <a class="nav-link {{ $on('asesor.red') }}" href="{{ route('asesor.red') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-sitemap"></i></div>
                    Mi red
                </a>
                <a class="nav-link {{ $on('asesor.comisiones') }}" href="{{ route('asesor.comisiones') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-coins"></i></div>
                    Mis comisiones
                </a>
                <a class="nav-link" href="{{ route('asesor.simulador') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-calculator"></i></div>
                    Simulador de ganancias
                </a>
                <a class="nav-link" href="{{ route('asesor.capacitacion') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    Capacitación del equipo
                </a>
                @endif

                @if ($tiendaMenu || (!auth()->user()->asesor && !auth()->user()->es_superadmin))
                <div class="sb-sidenav-menu-heading">{{ $tiendaMenu ? \Illuminate\Support\Str::limit($tiendaMenu->nombre, 22) : 'Inicio' }}</div>
                <a class="nav-link {{ $on('panel') }}" href="{{ route('panel') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-house"></i></div>
                    Panel
                </a>

                @if ($tiendaMenu && $tiendaMenu->es_conjunto)
                <div class="sb-sidenav-menu-heading">Administración</div>
                @php $menuConj = [
                    ['conjunto.inicio', 'conjunto.inicio', 'fa-building', 'Resumen del conjunto'],
                    ['conjunto.unidades', 'conjunto.unidad*', 'fa-door-closed', 'Unidades y residentes'],
                    ['conjunto.cobros', 'conjunto.cobros*', 'fa-file-invoice', 'Cobros de administración'],
                    ['conjunto.pagos', 'conjunto.pagos', 'fa-receipt', 'Pagos y recibos'],
                    ['conjunto.cartera', 'conjunto.cartera', 'fa-triangle-exclamation', 'Cartera y morosos'],
                    ['conjunto.gastos', 'conjunto.gastos', 'fa-wallet', 'Gastos'],
                    ['conjunto.comunicados', 'conjunto.comunicados', 'fa-bullhorn', 'Comunicados'],
                    ['conjunto.pqrs', 'conjunto.pqrs', 'fa-headset', 'PQRS'],
                    ['conjunto.reservas', 'conjunto.reservas', 'fa-calendar-check', 'Zonas comunes'],
                ]; @endphp
                @foreach ($menuConj as [$r, $pat, $ic, $txt])
                <a class="nav-link {{ $on($pat) }}" href="{{ route($r) }}"><div class="sb-nav-link-icon"><i class="fa-solid {{ $ic }}"></i></div>{{ $txt }}</a>
                @endforeach
                @can('editar-negocio')
                <a class="nav-link {{ $on('negocio.*') }}" href="{{ route('negocio.edit') }}"><div class="sb-nav-link-icon"><i class="fa-solid fa-gear"></i></div>Datos del conjunto</a>
                <a class="nav-link {{ $on('plan.*') }}" href="{{ route('plan.index') }}"><div class="sb-nav-link-icon"><i class="fa-solid fa-gem"></i></div>Mi plan</a>
                @endcan
                @can('ver-user')
                <a class="nav-link {{ $on('users.*') }}" href="{{ route('users.index') }}"><div class="sb-nav-link-icon"><i class="fa-solid fa-user-gear"></i></div>Usuarios</a>
                @endcan
                @else
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
                @can('ver-reportes')
                <a class="nav-link {{ $on('reportes.*') }}" href="{{ route('reportes.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-chart-pie"></i></div>
                    Reportes
                </a>
                @endcan
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
                @if ($tiendaMenu && $tiendaMenu->catalogo_activo && $tiendaMenu->slug)
                <a class="nav-link" href="{{ $tiendaMenu->url_catalogo }}" target="_blank">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-globe"></i></div>
                    Mi catálogo en línea
                </a>
                @endif
                @can('ver-metodo-pago')
                <a class="nav-link {{ $on('metodos-pago.*') }}" href="{{ route('metodos-pago.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-credit-card"></i></div>
                    Métodos de pago
                </a>
                @endcan
                @can('editar-negocio')
                <a class="nav-link {{ $on('plan.*') }}" href="{{ route('plan.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-gem"></i></div>
                    Mi plan
                </a>
                @endcan
                @can('ver-user')
                <a class="nav-link {{ $on('users.*') }}" href="{{ route('users.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-user-gear"></i></div>
                    Usuarios
                </a>
                @endcan
                @endif
                @endif

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
            <a href="{{ route('cuenta.edit') }}"><i class="fa-regular fa-user"></i> Mi cuenta y clave</a>
            <a href="{{ route('logout') }}" class="salir"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
        </div>
    </nav>
</div>
