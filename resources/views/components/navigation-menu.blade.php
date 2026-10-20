@php
    // Marca como activo el enlace de la sección actual
    $on = fn ($patron) => request()->routeIs($patron) ? 'active' : '';
    $tiendaMenu = \App\Support\TiendaActual::tienda();
    $ver = fn ($k) => !$tiendaMenu || $tiendaMenu->enMenu($k);
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
                <a class="nav-link" href="{{ route('marketplace') }}" target="_blank" rel="noopener">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-store"></i></div>
                    Ver todas las tiendas
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
                <a class="nav-link {{ $on('admin.auditoria') }}" href="{{ route('admin.auditoria') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-stethoscope"></i></div>
                    Auditoría
                </a>
                <a class="nav-link {{ $on('admin.facturacion') }}" href="{{ route('admin.facturacion') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-file-invoice"></i></div>
                    Facturación DIAN
                </a>
                <a class="nav-link {{ $on('admin.pagos') }}" href="{{ route('admin.pagos') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                    Pagos recibidos
                </a>
                <a class="nav-link {{ $on('admin.usuarios') }}" href="{{ route('admin.usuarios') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-users"></i></div>
                    Usuarios del sistema
                </a>
                <a class="nav-link {{ $on('admin.prospectos*') }}" href="{{ route('admin.prospectos') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-bullhorn"></i></div>
                    Prospectos
                </a>
                <a class="nav-link {{ $on('admin.red.*') }}" href="{{ route('admin.red.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-sitemap"></i></div>
                    Red comercial
                </a>
                <a class="nav-link" href="{{ route('asesor.simulador') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-calculator"></i></div>
                    Simulador de ganancias
                </a>
                <a class="nav-link {{ $on('asesor.ejemplos') }}" href="{{ route('asesor.ejemplos') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-eye"></i></div>
                    Ejemplos para mostrar
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
                <a class="nav-link {{ $on('asesor.vender') }}" href="{{ route('asesor.vender') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-rocket"></i></div>
                    Empiece a vender <span class="badge rounded-pill ms-auto" style="background:#a6f34a;color:#0b0d0c;font-size:.6rem">20 AL DÍA</span>
                </a>
                <a class="nav-link {{ $on('asesor.red') }}" href="{{ route('asesor.red') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-sitemap"></i></div>
                    Mi red
                </a>
                <a class="nav-link {{ $on('asesor.comisiones') }}" href="{{ route('asesor.comisiones') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-coins"></i></div>
                    Mis comisiones
                </a>
                <a class="nav-link {{ $on('curso.*') }}" href="{{ route('curso.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-user-graduate"></i></div>
                    Mi curso y certificado @if (auth()->user()->asesor->curso_aprobado_at)<i class="fa-solid fa-circle-check ms-auto" style="color:#a6f34a"></i>@else<span class="badge rounded-pill ms-auto" style="background:#f5b544;color:#0b0d0c;font-size:.6rem">PENDIENTE</span>@endif
                </a>
                @if (!auth()->user()->tienda_id)
                <a class="nav-link {{ $on('aprende.*') }}" href="{{ route('aprende.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-book-open"></i></div>
                    Aprende (cursos de ventas)
                </a>
                @endif
                <a class="nav-link" href="{{ route('asesor.simulador') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-calculator"></i></div>
                    Simulador de ganancias
                </a>
                <a class="nav-link {{ $on('asesor.ejemplos') }}" href="{{ route('asesor.ejemplos') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-eye"></i></div>
                    Ejemplos para mostrar
                </a>
                <a class="nav-link" href="{{ route('asesor.capacitacion') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    Capacitación del equipo
                </a>
                @if (auth()->user()->asesor->curso_aprobado_at && auth()->user()->asesor->activo && !$tiendaMenu?->mod_contable && \Illuminate\Support\Facades\Route::has('contabilidad.index'))
                <a class="nav-link {{ $on('contabilidad.*') }}" href="{{ route('contabilidad.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                    Contabilidad <span class="badge rounded-pill ms-auto" style="background:#a6f34a;color:#0b0d0c;font-size:.6rem">NUEVO</span>
                </a>
                @endif
                @endif

                @if ($tiendaMenu || (!auth()->user()->asesor && !auth()->user()->es_superadmin))
                <div class="sb-sidenav-menu-heading">{{ $tiendaMenu ? \Illuminate\Support\Str::limit($tiendaMenu->nombre, 22) : 'Inicio' }}</div>
                @php $esConsultorio = $tiendaMenu?->tipo_negocio === 'consultorio' && $tiendaMenu?->mod_consultorio; @endphp
                <a class="nav-link {{ $on('panel') }}" href="{{ $esConsultorio ? route('panel', ['ventas' => 1]) : route('panel') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid {{ $esConsultorio ? 'fa-chart-line' : 'fa-house' }}"></i></div>
                    {{ $esConsultorio ? 'Ventas del consultorio' : 'Panel' }}
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
                <a class="nav-link {{ $on('beneficios.*') }}" href="{{ route('beneficios.index') }}"><div class="sb-nav-link-icon"><i class="fa-solid fa-gift"></i></div>Comparta y gane</a>
                @endcan
                @can('ver-user')
                <a class="nav-link {{ $on('users.*') }}" href="{{ route('users.index') }}"><div class="sb-nav-link-icon"><i class="fa-solid fa-user-gear"></i></div>Usuarios</a>
                @endcan
                @else
                @if ($tiendaMenu?->mod_contable && (auth()->user()->can('contabilidad') || auth()->user()->can('editar-negocio')) && \Illuminate\Support\Facades\Route::has('contabilidad.index') && $ver('contabilidad'))
                <a class="nav-link {{ $on('contabilidad.*') }}" href="{{ route('contabilidad.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-calculator"></i></div>
                    Contabilidad <span class="badge rounded-pill ms-auto" style="background:#a6f34a;color:#0b0d0c;font-size:.6rem">NUEVO</span>
                </a>
                @endif
                @if ($tiendaMenu?->mod_consultorio && (auth()->user()->can('agenda-consultorio') || auth()->user()->can('historia-clinica')) && $ver('consultorio'))
                <div class="sb-sidenav-menu-heading">Consultorio</div>
                <a class="nav-link {{ $on('consultorio.inicio') }}" href="{{ route('consultorio.inicio') }}"><div class="sb-nav-link-icon"><i class="fa-solid fa-house-medical"></i></div>Hoy en consulta</a>
                <a class="nav-link {{ $on('consultorio.agenda') }}" href="{{ route('consultorio.agenda') }}"><div class="sb-nav-link-icon"><i class="fa-solid fa-calendar-check"></i></div>Agenda de citas</a>
                <a class="nav-link {{ $on('consultorio.pacientes*') }} {{ $on('consultorio.paciente') }} {{ $on('consultorio.atencion*') }}" href="{{ route('consultorio.pacientes') }}"><div class="sb-nav-link-icon"><i class="fa-solid fa-users"></i></div>Pacientes</a>
                @endif
                @if ($tiendaMenu?->mod_obituarios && (auth()->user()->can('gestionar-obituarios') || auth()->user()->can('editar-negocio')) && \Illuminate\Support\Facades\Route::has('obituarios.index'))
                @if ($ver('obituarios'))<a class="nav-link {{ $on('obituarios.*') }}" href="{{ route('obituarios.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-dove"></i></div>
                    Obituarios
                </a>@endif
                @endif
                @can('usar-pos')
                @if ($ver('pos'))<a class="nav-link {{ $on('pos.index') }}" href="{{ route('pos.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-cash-register"></i></div>
                    Vender (POS)
                </a>@endif
                @php $sepAct = \Illuminate\Support\Facades\Schema::hasTable('separados') ? \App\Models\Separado::where('estado', 'activo')->count() : 0; @endphp
                @if ($ver('separados'))<a class="nav-link {{ $on('separados.*') }}" href="{{ route('separados.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-box-archive"></i></div>
                    Separados y abonos @if ($sepAct)<span class="badge rounded-pill ms-auto" style="background:#a6f34a;color:#0b0d0c">{{ $sepAct }}</span>@endif
                </a>@endif
                @if ($tiendaMenu?->mod_alquiler)
                @php $alqHoy = \App\Models\Alquiler::where('estado', 'programado')->whereDate('fecha', today())->count(); @endphp
                @if ($ver('alquiler'))<a class="nav-link {{ $on('alquileres.*') }}" href="{{ route('alquileres.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-calendar-days"></i></div>
                    Alquiler @if ($alqHoy)<span class="badge rounded-pill ms-auto" style="background:#a6f34a;color:#0b0d0c" title="Alquileres para hoy">{{ $alqHoy }}</span>@endif
                </a>@endif
                @endif
                @if ($ver('caja'))<a class="nav-link {{ $on('cajas.show') || $on('cajas.abrir') || $on('cajas.cerrar') ? 'active' : '' }}" href="{{ route('cajas.actual') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-vault"></i></div>
                    Mi caja
                </a>@endif
                @endcan
                @canany(['usar-pos', 'ver-cajas'])
                @if ($ver('ventas'))<a class="nav-link {{ $on('pos.ventas') }}" href="{{ route('pos.ventas') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-receipt"></i></div>
                    Ventas
                </a>@endif
                @endcanany
                @canany(['registrar-egresos', 'ver-egresos-todos'])
                @if ($ver('egresos'))<a class="nav-link {{ $on('egresos.*') }}" href="{{ route('egresos.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-arrow-trend-down"></i></div>
                    Egresos
                </a>@endif
                @endcanany
                @can('ver-reportes')
                @if ($ver('reportes'))<a class="nav-link {{ $on('reportes.*') }}" href="{{ route('reportes.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-chart-pie"></i></div>
                    Reportes
                </a>@endif
                @endcan
                @if (auth()->user()->can('gestionar-nomina') || auth()->user()->can('editar-negocio'))
                @if ($ver('nomina'))<a class="nav-link {{ $on('nomina.*') }}" href="{{ route('nomina.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-users-gear"></i></div>
                    Nómina
                </a>@endif
                @endif
                @can('ver-cajas-todas')
                @if ($ver('cierres'))<a class="nav-link {{ $on('cajas.index') }}" href="{{ route('cajas.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-scale-balanced"></i></div>
                    Cierres de caja
                </a>@endif
                @endcan

                @canany(['ver-inventario', 'ver-producto', 'ver-categoria', 'ver-entradas'])
                @if ($ver('productos') || $ver('existencias') || $ver('entradas') || $ver('categorias'))<div class="sb-sidenav-menu-heading">Inventario</div>@endif
                @endcanany
                @can('ver-producto')
                @if ($ver('productos'))<a class="nav-link {{ $on('productos.*') }}" href="{{ route('productos.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid {{ $tiendaMenu?->icono_producto ?? 'fa-box' }}"></i></div>
                    Productos
                </a>@endif
                @endcan
                @can('ver-inventario')
                @if ($ver('existencias'))<a class="nav-link {{ $on('inventario.index') }} {{ $on('inventario.movimientos') }}" href="{{ route('inventario.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-warehouse"></i></div>
                    Existencias
                </a>@endif
                @if ($ver('existencias'))<a class="nav-link {{ $on('inventario.resumen') }} {{ $on('inventario.conteos.*') }}" href="{{ route('inventario.resumen') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                    Resumen y conteo
                </a>@endif
                @endcan
                @can('ver-entradas')
                @if ($ver('entradas'))<a class="nav-link {{ $on('entradas.*') }}" href="{{ route('entradas.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-truck-ramp-box"></i></div>
                    Entradas de mercancía
                </a>@endif
                @endcan
                @can('ver-categoria')
                @if ($ver('categorias'))<a class="nav-link {{ $on('categorias.*') }}" href="{{ route('categorias.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-tags"></i></div>
                    Categorías
                </a>@endif
                @endcan

                @canany(['responder-redes', 'publicar-redes', 'editar-negocio'])
                @php $redesNl = \Illuminate\Support\Facades\Schema::hasTable('redes_conversaciones') ? \App\Models\RedConversacion::where('no_leidos', '>', 0)->count() : 0; @endphp
                @if ($ver('redes'))<a class="nav-link {{ $on('redes.*') }}" href="{{ route('redes.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-hashtag"></i></div>
                    Mis redes @if ($redesNl)<span class="badge rounded-pill ms-auto" style="background:#ff3b6b;color:#fff">{{ $redesNl }}</span>@else<span class="badge rounded-pill ms-auto" style="background:#a6f34a;color:#0b0d0c;font-size:.6rem">NUEVO</span>@endif
                </a>@endif
                @endcanany

                @canany(['ver-cliente', 'ver-proveedore'])
                @if ($ver('clientes') || $ver('proveedores'))<div class="sb-sidenav-menu-heading">Contactos</div>@endif
                @endcanany
                @if (auth()->user()->tienda_id && \Illuminate\Support\Facades\Route::has('aprende.index'))
                <a class="nav-link {{ $on('aprende.*') }}" href="{{ route('aprende.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    Aprende <span class="badge rounded-pill ms-auto" style="background:#a6f34a;color:#0b0d0c;font-size:.6rem">CURSOS</span>
                </a>
                @endif
                @can('ver-cliente')
                @if ($ver('clientes'))<a class="nav-link {{ request()->routeIs('clientes.*') && !request()->routeIs('clientes.contactar', 'clientes.contactos', 'clientes.importar*') ? 'active' : '' }}" href="{{ route('clientes.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-people-group"></i></div>
                    Clientes
                </a>
                <a class="nav-link {{ request()->routeIs('vender.*', 'clientes.contactar', 'clientes.contactos', 'clientes.importar*') ? 'active' : '' }}" href="{{ route('vender.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-rocket"></i></div>
                    Vender más <span class="badge rounded-pill ms-auto" style="background:#a6f34a;color:#0b0d0c;font-size:.6rem">NUEVO</span>
                </a>@endif
                @endcan
                @can('ver-proveedore')
                @if ($ver('proveedores'))<a class="nav-link {{ $on('proveedores.*') }}" href="{{ route('proveedores.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-handshake"></i></div>
                    Proveedores
                </a>@endif
                @endcan

                @canany(['ver-user', 'ver-metodo-pago', 'editar-negocio', 'gestionar-enlaces'])
                <div class="sb-sidenav-menu-heading">Mi negocio</div>
                @endcanany
                @can('editar-negocio')
                <a class="nav-link {{ $on('negocio.*') }}" href="{{ route('negocio.edit') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-store"></i></div>
                    Datos del negocio
                </a>
                @endcan
                @can('facturacion-dian')
                @if (!$tiendaMenu?->pais || $tiendaMenu->pais === 'CO')
                @if ($ver('facturacion'))<a class="nav-link {{ $on('facturacion.*') }}" href="{{ route('facturacion.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                    Facturación DIAN
                </a>@endif
                @endif
                @endcan
                @canany(['editar-negocio', 'gestionar-enlaces'])
                @if (\Illuminate\Support\Facades\Route::has('enlaces.index'))
                @if ($ver('enlaces'))<a class="nav-link {{ $on('enlaces.*') }}" href="{{ route('enlaces.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-link"></i></div>
                    Mis enlaces <span class="badge rounded-pill ms-auto" style="background:#a6f34a;color:#0b0d0c;font-size:.6rem">NUEVO</span>
                </a>@endif
                @endif
                @endcanany
                @if ($tiendaMenu && $tiendaMenu->catalogo_activo && $tiendaMenu->slug)
                @if ($ver('catalogo'))<a class="nav-link" href="{{ $tiendaMenu->url_catalogo_navegador }}" target="_blank" rel="noopener">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-globe"></i></div>
                    Mi catálogo en línea
                </a>@endif
                @endif
                @can('ver-metodo-pago')
                @if ($ver('metodos'))<a class="nav-link {{ $on('metodos-pago.*') }}" href="{{ route('metodos-pago.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-credit-card"></i></div>
                    Métodos de pago
                </a>@endif
                @endcan
                @can('editar-negocio')
                @if (\Illuminate\Support\Facades\Route::has('menu.edit'))
                <a class="nav-link {{ $on('menu.*') }}" href="{{ route('menu.edit') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-sliders"></i></div>
                    Personalizar menú
                </a>
                @endif
                <a class="nav-link {{ $on('plan.*') }}" href="{{ route('plan.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-gem"></i></div>
                    Mi plan
                </a>
                <a class="nav-link {{ $on('beneficios.*') }}" href="{{ route('beneficios.index') }}">
                    <div class="sb-nav-link-icon"><i class="fa-solid fa-gift"></i></div>
                    Comparta y gane
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
        <details class="lp-cuenta" id="lpCuenta">
            <summary class="sb-sidenav-footer" aria-label="Opciones de la cuenta">
                <span class="lp-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <div style="min-width:0;flex:1">
                    <div class="small">Sesión iniciada</div>
                    <b style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ auth()->user()->name }}</b>
                </div>
                <i class="fa-solid fa-chevron-up lp-cuenta-flecha"></i>
            </summary>
            <div class="lp-cuenta-menu">
                <a href="#" class="lp-solo-navegador" onclick="event.preventDefault();window.lpInstalarApp&&window.lpInstalarApp()">
                    <span class="ic"><i class="fa-solid fa-mobile-screen-button"></i></span>
                    <span class="tx"><b>Instalar la app</b><small>Úsela en el celular como una app</small></span>
                </a>
                <a href="{{ route('cuenta.edit') }}">
                    <span class="ic"><i class="fa-regular fa-user"></i></span>
                    <span class="tx"><b>Mi cuenta y clave</b><small>Datos y contraseña</small></span>
                </a>
                <a href="{{ route('logout') }}" class="salir">
                    <span class="ic"><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                    <span class="tx"><b>Cerrar sesión</b><small>Salir de LocalPro</small></span>
                </a>
            </div>
        </details>
        <script>
        (function () {
            var d = document.getElementById('lpCuenta');
            if (!d) return;
            document.addEventListener('click', function (e) { if (d.open && !d.contains(e.target)) d.open = false; });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') d.open = false; });
        })();
        </script>
    </nav>
</div>
