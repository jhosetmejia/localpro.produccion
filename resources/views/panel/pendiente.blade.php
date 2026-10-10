<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.partials.landing-head')
    <title>Cuenta en revisión — LocalPRO</title>
</head>
<body>
<div class="pending">
    <div class="box rise">
        <div class="ico"><i class="fa-regular fa-clock"></i></div>
        <h1 class="serif">Su cuenta está en revisión</h1>
        <p>Hola {{ auth()->user()->name }}, ya recibimos su registro. En cuanto activemos su acceso podrá entrar a todos los módulos de LocalPRO.</p>
        <a class="btn btn-ink" href="{{ route('logout') }}"><i class="fa-solid fa-arrow-right-from-bracket"></i> Cerrar sesión</a>
    </div>
</div>
</body>
</html>
