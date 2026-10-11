<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="LocalPro - Sistema de gestión para negocios" />
    <meta name="author" content="Jhoset Mejía - DiCreWeb" />
    <title>@yield('title') · LocalPro</title>
    @stack('css-datatable')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4bw+/aepP/YC94hEpVNVgiZdgIC5+VKNBQNGCHeKRQN+PtmoHDEXuppvnDJzQIu9" crossorigin="anonymous">
    <link href="{{ asset('css/styles.css') }}" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="{{ asset('css/panel.css') }}?v=11" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    @include('layouts.partials.pwa')
    @include('layouts.partials.lp-ui')
    @stack('css')
</head>

<body class="sb-nav-fixed">

    <x-navigation-header />

    <div id="layoutSidenav">

        <x-navigation-menu />

        <div id="layoutSidenav_content">

            @if (auth()->user()->es_superadmin && session()->has('tienda_visitada'))
            <div style="background:#0b0d0c;color:#fff;padding:.6rem 1.2rem;display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;font-weight:700;font-size:.9rem">
                <span><i class="fa-solid fa-eye me-1" style="color:#a6f34a"></i> Está viendo la tienda <b style="color:#a6f34a">{{ \App\Support\TiendaActual::tienda()?->nombre }}</b> como administrador</span>
                <form method="post" action="{{ route('admin.tiendas.salir') }}">@csrf<button class="btn btn-sm btn-primary py-1">Volver al panel de tiendas</button></form>
            </div>
            @endif
            @php $tPlan = \App\Support\TiendaActual::tienda(); @endphp
            @if ($tPlan && $tPlan->plan_vence && $tPlan->dias_para_vencer !== null && $tPlan->dias_para_vencer <= 5 && $tPlan->dias_para_vencer >= 0 && !request()->routeIs('plan.*'))
            <div style="background:#fff5d6;color:#8a6400;padding:.6rem 1.2rem;font-weight:700;font-size:.9rem;display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:center">
                <span><i class="fa-solid fa-hourglass-half me-1"></i> Su plan vence {{ $tPlan->dias_para_vencer === 0 ? 'hoy' : 'en ' . $tPlan->dias_para_vencer . ' día(s)' }} ({{ $tPlan->plan_vence->format('d/m/Y') }}).</span>
                @can('editar-negocio')<a href="{{ route('plan.index') }}" class="btn btn-sm btn-dark py-1">Renovar</a>@endcan
            </div>
            @endif
            <main>
                @yield('content')
            </main>

            <x-footer />

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-HwwvtgBNo3bZJJLYd8oVXjrBZt8cqVSpeBNS5n7C8IVInixGAoxmnlMuBnhbgrkm" crossorigin="anonymous"></script>  
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script>
    // Celular: tocar la zona oscura o elegir una opción cierra el menú
    (function () {
        var movil = function () { return window.innerWidth < 992; };
        var abierto = function () { return document.body.classList.contains('sb-sidenav-toggled'); };
        var cerrar = function () { document.body.classList.remove('sb-sidenav-toggled'); };
        var contenido = document.getElementById('layoutSidenav_content');
        if (contenido) {
            contenido.addEventListener('click', function (e) {
                if (movil() && abierto()) { e.preventDefault(); e.stopPropagation(); cerrar(); }
            }, true);
        }
        document.querySelectorAll('#sidenavAccordion a.nav-link[href]:not([href="#"])').forEach(function (a) {
            a.addEventListener('click', function () { if (movil()) cerrar(); });
        });
    })();
    </script>
    @stack('js')

</body>


</html>