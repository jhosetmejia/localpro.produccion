{{-- Pantalla al terminar la prueba gratis (o vencer el plan): pagar mensual o anual. Si es prueba, muestra cuándo se borra la información. --}}
@php
    $mensual = $planes->first(fn ($p) => $p->dias && $p->dias < 365);
    $mc = $tienda->moneda_cobro; // Venezuela: dólares o bolívares
    $fmt = fn ($v) => \App\Support\Moneda::formato($v, $mc);
    $diasBorrar = $fechaBorrado ? max(0, (int) today()->diffInDays($fechaBorrado, false)) : null;
    $ultimoDia = $fechaBorrado?->copy()->subDay();
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
@include('layouts.partials.landing-head')
<meta name="robots" content="noindex" />
<title>Active su plan · LocalProo</title>
<style>
*{box-sizing:border-box}
body{margin:0;min-height:100vh;font-family:'Plus Jakarta Sans',system-ui,sans-serif;color:#151a17;background:radial-gradient(60% 50% at 10% 0%,rgba(166,243,74,.35),transparent 70%),radial-gradient(50% 50% at 100% 100%,rgba(47,209,90,.22),transparent 70%),#f4fcf6}
.pv{max-width:920px;margin:0 auto;padding:22px 16px 40px}
.pv-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px;gap:10px}
.pv-brand{display:flex;align-items:center;gap:.55rem;font-weight:900;font-size:1.15rem;color:#0b0d0c;text-decoration:none}
.pv-brand span{width:34px;height:34px;border-radius:11px;display:grid;place-items:center;background:linear-gradient(135deg,#a6f34a 0%,#2fd15a 45%,#16b34a 100%)}
.pv-salir{color:#5b665e;font-weight:700;text-decoration:none;font-size:.9rem}
.pv-hero{background:#0b0d0c;color:#fff;border-radius:28px;padding:28px 26px;position:relative;overflow:hidden}
.pv-hero::after{content:"";position:absolute;width:240px;height:240px;border-radius:50%;border:40px solid rgba(47,209,90,.14);right:-80px;top:-90px}
.pv-hero small{color:#a6f34a;font-weight:800;letter-spacing:.04em;text-transform:uppercase;font-size:.78rem}
.pv-hero h1{font-size:clamp(1.6rem,4.5vw,2.4rem);font-weight:900;line-height:1.1;margin:.4rem 0 .6rem;position:relative;z-index:1}
.pv-hero p{color:#c9d3cc;margin:0;max-width:560px;line-height:1.5;position:relative;z-index:1}
.pv-aviso{margin-top:16px;background:#ffe9f3;border:2px solid #ffc3de;color:#9b1c5a;border-radius:22px;padding:16px 18px;display:flex;gap:14px;align-items:center;flex-wrap:wrap}
.pv-aviso .n{flex:none;width:76px;height:76px;border-radius:20px;background:#9b1c5a;color:#fff;display:grid;place-items:center;text-align:center;line-height:1}
.pv-aviso .n b{font-size:1.9rem;font-weight:900;display:block}
.pv-aviso .n span{font-size:.7rem;font-weight:700}
.pv-aviso p{margin:0;flex:1 1 260px;line-height:1.5;font-weight:600}
.pv-ok{margin-top:16px;background:#e8f8ec;color:#11702f;border-radius:22px;padding:16px 18px;font-weight:600;line-height:1.5}
.pv-planes{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:14px;margin-top:18px}
.pv-plan{background:#fff;border:2px solid #e3efe5;border-radius:24px;padding:22px 20px;display:flex;flex-direction:column;position:relative}
.pv-plan.top{border-color:#2fd15a;box-shadow:0 20px 50px -30px rgba(22,179,74,.6)}
.pv-plan .tag{position:absolute;top:-12px;right:18px;background:linear-gradient(135deg,#a6f34a,#2fd15a);color:#0b0d0c;font-weight:900;font-size:.75rem;border-radius:99px;padding:.3rem .7rem}
.pv-plan h3{margin:0;font-size:1.1rem;font-weight:800;color:#5b665e}
.pv-plan .p{font-size:2.3rem;font-weight:900;letter-spacing:-.03em;margin:.3rem 0 0}
.pv-plan .p small{font-size:.9rem;color:#6b7570;font-weight:700;letter-spacing:0}
.pv-plan .d{color:#6b7570;font-weight:600;font-size:.9rem;margin:.3rem 0 1rem;flex:1}
.pv-plan button,.pv-plan a.b{width:100%;height:56px;border:0;border-radius:18px;font-weight:900;font-size:1.05rem;cursor:pointer;font-family:inherit;background:#0b0d0c;color:#fff;display:flex;align-items:center;justify-content:center;gap:.5rem;text-decoration:none}
.pv-plan.top button{background:linear-gradient(135deg,#a6f34a 0%,#2fd15a 45%,#16b34a 100%);color:#0b0d0c}
.pv-nota{background:#fff;border-radius:18px;padding:14px 16px;margin-top:14px;color:#5b665e;font-weight:600;font-size:.92rem;line-height:1.5}
.pv-pie{display:flex;gap:16px;flex-wrap:wrap;justify-content:center;margin-top:20px;font-weight:700;font-size:.9rem}
.pv-pie a{color:#11702f;text-decoration:none}
.pv-legal{text-align:center;color:#8a948d;font-size:.78rem;margin-top:14px;line-height:1.5}
</style>
</head>
<body>
<div class="pv">
    <div class="pv-top">
        <a class="pv-brand" href="{{ route('plan.vencido') }}"><span>L</span>LocalProo</a>
        <a class="pv-salir" href="{{ route('logout') }}"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
    </div>

    <div class="pv-hero">
        <small>{{ $tienda->nombre }}</small>
        @if ($esPrueba)
            <h1>Su prueba gratis terminó.<br>Active su plan para seguir vendiendo.</h1>
            <p>Su caja, inventario, ventas y tienda en línea están en pausa desde el {{ $tienda->plan_vence->copy()->addDay()->locale('es')->isoFormat('D [de] MMMM') }}. Pague su mensualidad o el plan anual y todo vuelve a funcionar al instante, tal como lo dejó.</p>
        @else
            <h1>Su plan venció.<br>Renuévelo para seguir vendiendo.</h1>
            <p>Su plan venció el {{ $tienda->plan_vence->locale('es')->isoFormat('D [de] MMMM') }}. Su información está guardada: renueve y siga trabajando de inmediato.</p>
        @endif
    </div>

    @if ($tienda->datos_depurados)
        <div class="pv-aviso"><p>Como la prueba terminó sin activar un plan en el plazo avisado, se eliminaron los productos, imágenes y ventas del negocio. Sus datos de contacto siguen guardados: active un plan y vuelva a empezar cuando quiera.</p></div>
    @elseif ($esPrueba && $fechaBorrado)
        <div class="pv-aviso">
            <div class="n"><div><b>{{ $diasBorrar }}</b><span>{{ $diasBorrar === 1 ? 'día' : 'días' }}</span></div></div>
            <p>Tiene hasta el <b>{{ $ultimoDia->locale('es')->isoFormat('dddd D [de] MMMM') }}</b> para pagar. Si no, el {{ $fechaBorrado->locale('es')->isoFormat('D [de] MMMM') }} se <b>eliminan de forma definitiva</b> sus productos, imágenes, videos, inventario, ventas y clientes, y no se pueden recuperar. También le avisamos a su correo.</p>
        </div>
    @else
        <div class="pv-ok"><i class="fa-solid fa-shield-halved"></i> Su información está guardada y segura mientras renueva.</div>
    @endif

    <div class="pv-planes">
        @foreach ($planes as $p)
            @php
                $anual = $p->dias >= 365;
                $ahorro = $anual && $mensual ? $mensual->precioEn($mc) * 12 - $p->precioEn($mc) : 0;
            @endphp
            <div class="pv-plan {{ $anual ? 'top' : '' }}">
                @if ($anual && $ahorro > 0)<div class="tag">Ahorra {{ $fmt($ahorro) }}</div>@endif
                <h3>{{ $p->nombre }}</h3>
                @include('plan._promo')
                <div class="p">{{ $fmt($p->precioEn($mc)) }} <small>{{ $anual ? '/ año' : ($p->dias >= 28 ? '/ mes' : '/ ' . $p->dias . ' días') }}</small></div>
                <div class="d">{{ $anual ? 'Equivale a ' . $fmt(\App\Support\Moneda::esExtranjera($mc) ? round($p->precioEn($mc) / 12, 2) : round((float) $p->precio / 12)) . ' al mes. ' : '' }}{{ $p->descripcion }}</div>
                @include('plan._promo_fin')
                @if ($puedePagar)
                    <form method="post" action="{{ route('plan.pagar', $p) }}">@csrf
                        <button type="submit">
                            @if ($wompi)<i class="fa-solid fa-credit-card"></i> Pagar {{ $fmt($p->precioEn($mc)) }} @else<i class="fa-brands fa-whatsapp"></i> Pagar con LocalProo @endif
                        </button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>

    @unless ($puedePagar)
        <div class="pv-nota"><i class="fa-solid fa-circle-info"></i> Solo el dueño o el administrador del negocio puede pagar el plan. Avísele para que ingrese y lo active.</div>
    @endunless
    @if ($wompi && $puedePagar)
        <p class="pv-legal" style="margin-top:12px"><i class="fa-solid fa-lock"></i> Pago seguro con Wompi (Bancolombia): tarjeta, PSE, Nequi o Bancolombia.</p>
    @endif

    <div class="pv-pie">
        <a href="{{ route('plan.index') }}"><i class="fa-solid fa-receipt"></i> Ver mi plan e historial de pagos</a>
        @if ($soporte)<a href="https://wa.me/{{ $soporte }}?text={{ rawurlencode('Hola LocalProo, quiero activar el plan de ' . $tienda->nombre . ' (' . ($tienda->codigo ?: '#' . $tienda->id) . ')') }}" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Hablar con soporte</a>@endif
    </div>
    @if ($esPrueba)
        <p class="pv-legal">Según las <a href="{{ route('legal.terminos') }}" target="_blank" style="color:inherit">condiciones de uso</a> aceptadas al crear la cuenta, al terminar la prueba sin activar un plan se elimina la información del negocio y se conservan los datos de contacto.</p>
    @endif
</div>
@include('layouts.partials.lp-ui')
@if ($errors->any() || session('success') || session('info'))
<script>document.addEventListener('DOMContentLoaded', function () {
    @if ($errors->any()) LP.toast(@json($errors->first()), 'error'); @endif
    @if (session('success')) LP.toast(@json(session('success')), 'ok'); @endif
    @if (session('info')) LP.toast(@json(session('info')), 'info'); @endif
});</script>
@endif
</body>
</html>
