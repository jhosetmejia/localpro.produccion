@extends('layouts.app')

@section('title', 'Primeros pasos')

@push('css')
<style>
.pp-hero{background:#0b0d0c;color:#fff;border-radius:26px;padding:1.4rem 1.5rem;position:relative;overflow:hidden;margin-bottom:1rem}
.pp-hero::after{content:"";position:absolute;right:-60px;top:-60px;width:220px;height:220px;border-radius:50%;background:linear-gradient(135deg,#a6f34a,#16b34a);opacity:.35;filter:blur(6px)}
.pp-hero h1{font-weight:900;font-size:clamp(1.5rem,4vw,2.1rem);margin:.2rem 0 .4rem;position:relative;z-index:1}
.pp-hero p{color:#cfd8d2;margin:0;position:relative;z-index:1}
.pp-barra{height:12px;border-radius:99px;background:rgba(255,255,255,.15);overflow:hidden;margin-top:1rem;position:relative;z-index:1}
.pp-barra i{display:block;height:100%;background:linear-gradient(90deg,#a3f04a,#16b84e)}
.pp-paso{background:#fff;border:1.5px solid #e3ebe6;border-radius:22px;padding:1rem 1.1rem;margin-bottom:.8rem;display:flex;gap:1rem;align-items:flex-start}
.pp-paso.ok{border-color:#bfeccb;background:#f7fdf8}
.pp-n{width:48px;height:48px;border-radius:16px;background:#f1f5f2;display:grid;place-items:center;font-size:1.2rem;flex:none;color:#55605a}
.pp-paso.ok .pp-n{background:linear-gradient(135deg,#a6f34a,#16b34a);color:#0b0d0c}
.pp-paso h3{font-size:1.08rem;font-weight:900;margin:0 0 .25rem;display:flex;gap:.5rem;align-items:center;flex-wrap:wrap}
.pp-paso h3 span{font-size:.75rem;font-weight:800;border-radius:999px;padding:.15rem .6rem;background:#fef3c7;color:#92600a}
.pp-paso.ok h3 span{background:#dcfce7;color:#14753a}
.pp-paso p{margin:.15rem 0;color:#55605a;font-weight:500}
.pp-paso p b{color:#0b0d0c}
</style>
@endpush

@section('content')
<div class="lp-dash" style="max-width:860px;margin:0 auto">
    <div class="pp-hero">
        <small style="color:#a6f34a;font-weight:800;letter-spacing:.1em;text-transform:uppercase;font-size:.72rem;position:relative;z-index:1">Curso del empresario</small>
        <h1>Primeros pasos para vender más</h1>
        <p>{{ $res['hechos'] }} de {{ $res['total'] }} tareas hechas. El sistema revisa solo cada tarea cuando usted la hace.</p>
        <div class="pp-barra"><i style="width:{{ round($res['hechos'] / $res['total'] * 100) }}%"></i></div>
    </div>
    @if ($res['hechos'] === $res['total'])<div class="alert alert-success fw-bold">🎉 ¡Completó todos los primeros pasos! Su negocio ya está listo para vender más con LocalPro.</div>@endif

    @foreach ($pasos as $i => $p)
    <div class="pp-paso {{ $p['hecho'] ? 'ok' : '' }}">
        <div class="pp-n">@if ($p['hecho'])<i class="fa-solid fa-check"></i>@else<i class="{{ !empty($p['brand']) ? 'fa-brands' : 'fa-solid' }} {{ $p['icono'] }}"></i>@endif</div>
        <div class="flex-grow-1">
            <h3>{{ $i + 1 }}. {{ $p['titulo'] }} <span>{{ $p['hecho'] ? '✓ Hecho' : $p['avance'] }}</span></h3>
            <p><b>Para qué:</b> {{ $p['por'] }}</p>
            <p><b>Cómo:</b> {{ $p['como'] }}</p>
            @unless ($p['hecho'])<a href="{{ $p['url'] }}" class="btn btn-primary rounded-pill fw-bold mt-2">{{ $p['boton'] }} <i class="fa-solid fa-arrow-right ms-1"></i></a>@endunless
        </div>
    </div>
    @endforeach
</div>
@endsection
