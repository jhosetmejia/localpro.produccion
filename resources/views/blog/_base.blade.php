<!DOCTYPE html>
<html lang="es">
<head>
@include('layouts.partials.landing-head')
<title>@yield('titulo') · Blog LocalProo</title>
<meta name="description" content="@yield('descripcion')">
<meta property="og:type" content="article">
<meta property="og:title" content="@yield('titulo')">
<meta property="og:description" content="@yield('descripcion')">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:site_name" content="LocalProo">
<link rel="canonical" href="{{ url()->current() }}">
<meta name="robots" content="index,follow">
@yield('jsonld')
<style>
.bl{max-width:780px;margin:0 auto;padding:34px 20px 60px;line-height:1.7;color:#151a17;font-size:1.05rem}
.bl a.v{display:inline-flex;gap:.4rem;font-weight:800;color:#11702f;text-decoration:none;margin-bottom:18px}
.bl h1{font-size:clamp(1.7rem,4.5vw,2.4rem);font-weight:900;line-height:1.2;margin-bottom:.4rem}
.bl .f{color:#6b7570;font-weight:600;margin-bottom:26px;font-size:.95rem}
.bl h2{font-size:1.3rem;font-weight:900;margin:32px 0 10px}
.bl ul,.bl ol{padding-left:1.25rem}
.bl li{margin-bottom:.35rem}
.bl .box{background:#f4fcf6;border:1.5px solid #cfe9d6;border-radius:18px;padding:16px 18px;margin:18px 0}
.bl .ojo{background:#fff8e6;border:1.5px solid #f3dca0;border-radius:18px;padding:16px 18px;margin:18px 0}
.bl .tabla{width:100%;border-collapse:collapse;margin:16px 0;font-size:.97rem}
.bl .tabla th,.bl .tabla td{border:1px solid #dfe6e1;padding:10px;vertical-align:top;text-align:left}
.bl .tabla th{background:#f4fcf6}
.bl .tw{overflow-x:auto}
.bl .cta{display:block;text-align:center;background:#11702f;color:#fff;font-weight:800;border-radius:14px;padding:14px;text-decoration:none;margin:30px 0 10px}
.bl .card{display:block;border:1.5px solid #dfe6e1;border-radius:18px;padding:18px;margin:14px 0;text-decoration:none;color:inherit}
.bl .card:hover{border-color:#11702f}
.bl .card b{font-size:1.15rem;display:block;margin-bottom:4px}
</style>
</head>
<body style="background:#fff">
<div class="bl">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:6px">
        <a class="v" style="margin:0" href="{{ url('/') }}">← LocalProo</a>
        <a class="v" style="margin:0" href="{{ route('blog.index') }}">Blog</a>
    </div>
    @yield('volver')
    @yield('cuerpo')
    <p style="margin-top:34px;color:#6b7570;font-size:.95rem">LocalProo · Para negocios que piensan en grande · <a href="{{ url('/') }}">localproo.com</a></p>
</div>
</body>
</html>
