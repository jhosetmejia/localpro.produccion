<!DOCTYPE html>
<html lang="es">
<head>
@include('layouts.partials.landing-head')
<title>@yield('titulo') · LocalProo</title>
<style>
.lg{max-width:820px;margin:0 auto;padding:40px 20px 60px;line-height:1.65;color:#151a17}
.lg a.v{display:inline-flex;gap:.4rem;font-weight:800;color:#11702f;text-decoration:none;margin-bottom:18px}
.lg h1{font-size:clamp(1.8rem,4vw,2.5rem);font-weight:900;margin-bottom:.3rem}
.lg .f{color:#6b7570;font-weight:600;margin-bottom:24px}
.lg h2{font-size:1.15rem;font-weight:900;margin:26px 0 8px}
.lg h3{font-size:1.02rem;font-weight:800;margin:18px 0 6px}
.lg ul{padding-left:1.2rem}
.lg .box{background:#f4fcf6;border:1.5px solid #cfe9d6;border-radius:18px;padding:16px 18px;margin:16px 0}
</style>
</head>
<body style="background:#fff">
<div class="lg">
    <a class="v" href="{{ url('/') }}">← LocalProo</a>
    <h1>@yield('titulo')</h1>
    <div class="f">Última actualización: @yield('fecha', '12 de octubre de 2026')</div>
    @yield('cuerpo')
    @php $resp = \App\Models\Ajuste::valor('legal_responsable'); $nit = \App\Models\Ajuste::valor('legal_nit'); @endphp
    <p style="margin-top:30px;color:#6b7570">Responsable: LocalProo{{ $resp ? ' (' . $resp . ($nit ? ', NIT ' . $nit : '') . ')' : '' }} · Pasto, Nariño, Colombia · Contacto: <a href="mailto:{{ $correo }}">{{ $correo }}</a></p>
</div>
</body>
</html>
