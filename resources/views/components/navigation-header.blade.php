<nav class="sb-topnav navbar navbar-expand lp-top">
    <a class="navbar-brand ps-3" href="{{ route('panel') }}"><span class="lp-mark"></span>LOCALPRO</a>
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-3 me-lg-0" id="sidebarToggle" aria-label="Mostrar u ocultar menú"><i class="fas fa-bars"></i></button>

    <div class="lp-search d-none d-md-block ms-3">
        <i class="fas fa-search"></i>
        <input type="search" placeholder="Buscar en el menú..." aria-label="Buscar en el menú" id="lpMenuSearch" autocomplete="off" />
    </div>

    <ul class="navbar-nav ms-auto me-3 me-lg-4">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle lp-user" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <span class="lp-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="d-none d-sm-inline">{{ \Illuminate\Support\Str::words(auth()->user()->name, 1, '') }}</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li><a class="dropdown-item" href="{{ route('cuenta.edit') }}"><i class="fa-regular fa-user me-2"></i>Mi cuenta y clave</a></li>
                <li><hr class="dropdown-divider" /></li>
                <li><a class="dropdown-item text-danger" href="{{ route('logout') }}"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Cerrar sesión</a></li>
            </ul>
        </li>
    </ul>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var q = document.getElementById('lpMenuSearch');
    if (!q) return;
    q.addEventListener('input', function () {
        var t = q.value.trim().toLowerCase();
        document.querySelectorAll('#sidenavAccordion .nav > .nav-link').forEach(function (a) {
            var hit = !t || a.textContent.toLowerCase().indexOf(t) !== -1;
            a.style.display = hit ? '' : 'none';
        });
    });
    q.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        var first = Array.prototype.find.call(document.querySelectorAll('#sidenavAccordion .nav > .nav-link'), function (a) { return a.style.display !== 'none' && a.getAttribute('href') && a.getAttribute('href') !== '#'; });
        if (first) window.location = first.href;
    });
});
</script>
