@extends('layouts.app')

@section('title', 'Panel')

@push('css')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')
@if (session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({ title: @json(session('success')), icon: 'success', timer: 2200, showConfirmButton: false });
    });
</script>
@endif

@php
    $hora = (int) now()->format('H');
    $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
    $max = max(1, $semana->max('total'));
@endphp

<div class="lp-dash">
    @php
        $nv = \App\Support\Niveles::calcular();
        $tPlan = $nv['tienda']; $tid = $nv['tid']; $nivel = $nv['nivel'];
        $hechos = $nv['hechos']; $total = $nv['total'];
        // Los pasos ya hechos se quitan de la lista
        $pasos = collect($nv['pasos'])->map(fn ($p, $i) => array_merge($p, [$i + 1]))->reject(fn ($p) => $p[2])->values();
        $nombreUsr = \Illuminate\Support\Str::words(auth()->user()->name, 1, '');
    @endphp
    @include('layouts.partials.cuenta-regresiva')
    @include('layouts.partials.limite-plan', ['que' => 'productos'])
    @if (auth()->user()->can('crear-producto'))
    @if ($nivel < 3)
    <div class="vm-box">
        @if ($nivel === 2)
        <div class="vm-felicita">
            <span class="vm-stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
            <div><b>¡Muy bien! Completó el nivel 1</b><small>Ya domina lo básico de LocalPro: productos, clientes, tienda en línea, ventas en caja y redes. Ahora arme su negocio como un profesional.</small></div>
            <a href="{{ route('panel.certificado', 1) }}" class="vm-cert"><i class="fa-solid fa-award me-1"></i>Mi certificado</a>
        </div>
        @endif
        <div class="vm-h"><div><small>Nivel {{ $nivel }} · {{ $nivel === 1 ? 'Competencias básicas' : 'Para crecer' }}</small><b>{{ $nivel === 1 ? $total . ' pasos para dominar lo básico de LocalPro' : $total . ' pasos para manejar su negocio como un profesional' }}</b></div><span class="vm-n">{{ $hechos }}/{{ $total }}</span></div>
        <div class="vm-prog"><i style="width:{{ round($hechos * 100 / $total) }}%"></i></div>
        <div class="vm-pasos">
            @foreach ($pasos as [$tit, $sub, $ok, $url, $btn, $ic, $pct, $url2, $btn2, $nueva, $num])
            <div class="vm-p">
                <span class="vm-i">{{ $num }}</span>
                <div class="vm-t"><b>{{ $tit }}</b><small>{{ $sub }}</small>@if ($pct > 0)<span class="vm-bar"><i style="width:{{ $pct }}%"></i></span>@endif</div>
                <div class="vm-a"><a href="{{ $url }}" class="vm-btn" @if ($nueva) target="_blank" rel="noopener" onclick="setTimeout(function(){location.reload()},1500)" @endif><i class="fa-solid {{ $ic }} me-1"></i>{{ $btn }}</a>@if ($url2)<a href="{{ $url2 }}" class="vm-link">{{ $btn2 }}</a>@endif</div>
            </div>
            @endforeach
            @if ($hechos > 0)
            <div class="vm-hechos"><i class="fa-solid fa-star"></i> {{ $hechos }} {{ $hechos === 1 ? 'paso listo' : 'pasos listos' }} · ¡vamos bien!</div>
            @endif
        </div>
    </div>
    @else
    <div class="vm-fin" id="vm-fin" data-k="lp_fin_{{ $tid }}">
        <button type="button" class="vm-x" aria-label="Cerrar" onclick="try{localStorage.setItem(this.parentNode.dataset.k,'1')}catch(e){};this.parentNode.remove()">&times;</button>
        <span class="vm-stars grande"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
        <b>¡Felicitaciones, {{ $nombreUsr }}! 🏆</b>
        <p>Completó todos los pasos. Usted es una persona que <b>se preocupa por su negocio</b>: tiene productos, clientes, su tienda en línea, sus redes, su equipo y ya está vendiendo con LocalPro. ¡Siga así!</p>
        <div class="mt-2 d-flex gap-2 justify-content-center flex-wrap"><a href="{{ route('panel.certificado', 1) }}" class="vm-cert"><i class="fa-solid fa-award me-1"></i>Certificado nivel 1</a><a href="{{ route('panel.certificado', 2) }}" class="vm-cert"><i class="fa-solid fa-award me-1"></i>Certificado nivel 2</a></div>
    </div>
    <script>try{if(localStorage.getItem(document.getElementById('vm-fin').dataset.k))document.getElementById('vm-fin').remove()}catch(e){}</script>
    @endif
    <style>
    .vm-box{background:#fff;border:1.5px solid #bfeccb;border-radius:24px;padding:1.1rem 1.2rem;margin-bottom:1.2rem;background:linear-gradient(180deg,#f4fcf6,#fff 60%)}
    .vm-h{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:.8rem}
    .vm-h small{display:block;font-size:.72rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#16b34a}
    .vm-h b{font-size:1.1rem;font-weight:900}
    .vm-n{background:#0b0d0c;color:#a6f34a;font-weight:900;border-radius:999px;padding:.3rem .8rem;flex:none}
    .vm-pasos{display:flex;flex-direction:column;gap:.6rem}
    .vm-p{display:flex;gap:.8rem;align-items:center;background:#fff;border:1.5px solid #e3ebe6;border-radius:18px;padding:.75rem .9rem;flex-wrap:wrap}
    .vm-p.ok{opacity:.65}
    .vm-i{width:34px;height:34px;border-radius:50%;background:#0b0d0c;color:#fff;font-weight:900;display:grid;place-items:center;flex:none}
    .vm-p.ok .vm-i{background:#16b34a}
    .vm-t{flex:1 1 200px;min-width:0}.vm-t b{display:block;font-size:.95rem}.vm-t small{color:#6b7570;font-weight:600;font-size:.8rem}
    .vm-bar{display:block;height:6px;border-radius:9px;background:#e8eee9;margin-top:.35rem;overflow:hidden;max-width:220px}.vm-bar i{display:block;height:100%;background:linear-gradient(90deg,#a6f34a,#16b34a)}
    .vm-a{display:flex;gap:.6rem;align-items:center;flex-wrap:wrap}
    .vm-btn{background:linear-gradient(90deg,#a3f04a,#16b84e);color:#0b0d0c;font-weight:800;border-radius:999px;padding:.5rem 1rem;text-decoration:none;white-space:nowrap;font-size:.88rem}
    .vm-btn:hover{color:#0b0d0c;filter:brightness(1.05)}
    .vm-link{font-weight:700;font-size:.82rem;color:#11702f}
    .vm-ok{background:#e8f8ec;color:#11702f;font-weight:800;border-radius:999px;padding:.4rem .9rem;font-size:.85rem;white-space:nowrap}
    .vm-ok i{color:#f5b301}
    .vm-felicita{display:flex;gap:.9rem;align-items:center;background:#0b0d0c;color:#fff;border-radius:18px;padding:.8rem 1rem;margin-bottom:1rem}
    .vm-felicita b{display:block;font-size:1rem;color:#a6f34a}.vm-felicita small{color:#c9d3cc;font-weight:600;font-size:.82rem}
    .vm-stars{display:inline-flex;gap:.15rem;color:#f5b301;font-size:1.15rem;flex:none}
    .vm-stars i{animation:vmStar .6s cubic-bezier(.2,.9,.3,1.4) both}
    .vm-stars i:nth-child(2){animation-delay:.12s}.vm-stars i:nth-child(3){animation-delay:.24s}.vm-stars i:nth-child(4){animation-delay:.36s}.vm-stars i:nth-child(5){animation-delay:.48s}
    .vm-stars.grande{font-size:2rem;gap:.35rem;margin-bottom:.4rem}
    @keyframes vmStar{0%{transform:scale(0) rotate(-40deg);opacity:0}100%{transform:none;opacity:1}}
    .vm-fin{position:relative;text-align:center;background:linear-gradient(135deg,#0b0d0c,#14261a);color:#fff;border-radius:24px;padding:1.4rem 1.2rem;margin-bottom:1.2rem;box-shadow:0 0 0 2px #2fd15a inset}
    .vm-fin>b{display:block;font-size:1.35rem;font-weight:900;color:#a6f34a}
    .vm-fin p{margin:.3rem auto 0;max-width:36rem;color:#c9d3cc;font-weight:600}.vm-fin p b{color:#fff}
    .vm-x{position:absolute;top:.6rem;right:.8rem;background:none;border:0;color:#9aa69e;font-size:1.6rem;line-height:1;cursor:pointer}
    .vm-prog{height:8px;border-radius:9px;background:#e8eee9;overflow:hidden;margin:-.2rem 0 .8rem}.vm-prog i{display:block;height:100%;background:linear-gradient(90deg,#a6f34a,#16b34a);transition:width .6s}
    .vm-hechos{text-align:center;font-weight:800;color:#11702f;font-size:.85rem;padding:.2rem}.vm-hechos i{color:#f5b301}
    .vm-cert{background:#a6f34a;color:#0b0d0c;font-weight:900;border-radius:999px;padding:.45rem 1rem;text-decoration:none;white-space:nowrap;font-size:.85rem;margin-left:auto}
    .vm-cert:hover{color:#0b0d0c;filter:brightness(1.07)}.vm-fin .vm-cert{margin-left:0}
    /* Celebración al subir de nivel */
    .cel{position:fixed;inset:0;z-index:3000;display:grid;place-items:center;background:rgba(5,10,7,.78);backdrop-filter:blur(4px);animation:celIn .35s ease both;padding:16px}
    .cel canvas{position:fixed;inset:0;width:100%;height:100%;pointer-events:none}
    .cel-card{position:relative;max-width:440px;width:100%;text-align:center;background:linear-gradient(160deg,#0b0d0c,#163020);color:#fff;border-radius:28px;padding:2rem 1.4rem 1.5rem;box-shadow:0 0 0 2px #2fd15a inset,0 30px 80px rgba(0,0,0,.5);animation:celPop .6s cubic-bezier(.2,.9,.3,1.3) both .15s}
    .cel-trofeo{font-size:4.2rem;line-height:1;display:inline-block;animation:celTrofeo 1.6s ease-in-out infinite}
    .cel-card small{display:block;letter-spacing:.18em;font-weight:800;color:#a6f34a;font-size:.75rem;margin-top:.6rem}
    .cel-card h2{font-weight:900;font-size:1.7rem;margin:.3rem 0 .4rem}.cel-card h2 span{color:#a6f34a}
    .cel-card p{color:#c9d3cc;font-weight:600;margin:0 0 1.1rem}
    .cel-btns{display:flex;flex-direction:column;gap:.55rem}
    .cel-b1{background:linear-gradient(90deg,#a3f04a,#16b84e);color:#0b0d0c;font-weight:900;border-radius:999px;padding:.8rem 1rem;text-decoration:none;font-size:1rem}
    .cel-b1:hover{color:#0b0d0c}
    .cel-b2{background:none;border:1.5px solid #3c5a46;color:#dfe8e2;font-weight:800;border-radius:999px;padding:.7rem 1rem;cursor:pointer}
    @keyframes celIn{from{opacity:0}to{opacity:1}}
    @keyframes celPop{0%{transform:scale(.6) translateY(30px);opacity:0}100%{transform:none;opacity:1}}
    @keyframes celTrofeo{0%,100%{transform:rotate(-8deg) scale(1)}50%{transform:rotate(8deg) scale(1.12)}}
    </style>
    @if ($nv['logrados'] >= 1)
    @php $nCel = $nv['logrados']; @endphp
    <div class="cel" id="cel" data-k="lp_cel_{{ $tid }}_{{ $nCel }}" style="display:none">
        <canvas id="cel-cv"></canvas>
        <div class="cel-card">
            <span class="cel-trofeo">🏆</span>
            <small>NIVEL {{ $nCel }} COMPLETADO</small>
            <h2>¡Felicitaciones, <span>{{ $nombreUsr }}</span>!</h2>
            <p>Alcanzó el <b>Nivel {{ $nCel }} en LocalPro</b>. {{ $nCel === 1 ? 'Ya tiene las competencias básicas para manejar LocalPro: productos, tienda en línea, clientes, ventas en caja y redes sociales.' : 'Ya maneja su negocio como un profesional: equipo, marca y cierre de caja.' }}</p>
            <div class="cel-btns">
                <a href="{{ route('panel.certificado', $nCel) }}" class="cel-b1"><i class="fa-solid fa-award me-1"></i>Descargar mi certificado</a>
                <button type="button" class="cel-b2" onclick="celCerrar()">{{ $nCel === 1 ? 'Seguir al nivel 2 →' : 'Seguir vendiendo' }}</button>
            </div>
        </div>
    </div>
    <script>
    (function () {
        var el = document.getElementById('cel'), k = el.dataset.k;
        try { if (localStorage.getItem(k)) { el.remove(); return; } } catch (e) {}
        el.style.display = '';
        window.celCerrar = function () { try { localStorage.setItem(k, '1') } catch (e) {} el.remove(); };
        el.querySelector('.cel-b1').addEventListener('click', function () { try { localStorage.setItem(k, '1') } catch (e) {} });
        // Confeti
        var cv = document.getElementById('cel-cv'), cx = cv.getContext('2d'), W, H, ps = [], cols = ['#a6f34a', '#16b34a', '#f5b301', '#ffffff', '#2fd15a', '#ff6b6b'];
        function tam() { W = cv.width = innerWidth; H = cv.height = innerHeight; } tam(); addEventListener('resize', tam);
        for (var i = 0; i < 180; i++) ps.push({ x: Math.random() * W, y: -Math.random() * H, w: 6 + Math.random() * 8, h: 4 + Math.random() * 6, vy: 2 + Math.random() * 3.5, vx: -1.5 + Math.random() * 3, r: Math.random() * 6, vr: -.15 + Math.random() * .3, c: cols[i % cols.length] });
        var t0 = Date.now();
        (function loop() {
            if (!document.body.contains(cv)) return;
            cx.clearRect(0, 0, W, H);
            var vivo = Date.now() - t0 < 6000;
            ps.forEach(function (p) {
                p.y += p.vy; p.x += p.vx + Math.sin(p.y / 40); p.r += p.vr;
                if (p.y > H && vivo) { p.y = -10; p.x = Math.random() * W; }
                cx.save(); cx.translate(p.x, p.y); cx.rotate(p.r); cx.fillStyle = p.c; cx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h); cx.restore();
            });
            requestAnimationFrame(loop);
        })();
    })();
    </script>
    @endif
    @endif
    @if (auth()->user()?->hasAnyRole(['dueno', 'administrador']))
    @php $ppRes = \App\Support\PrimerosPasos::resumen(); @endphp
    @if ($ppRes['hechos'] < $ppRes['total'])
    <a href="{{ route('primeros.pasos') }}" class="d-flex align-items-center gap-3 text-decoration-none mb-3" style="background:#0b0d0c;color:#fff;border-radius:20px;padding:.9rem 1.1rem">
        <span style="width:44px;height:44px;border-radius:14px;background:linear-gradient(135deg,#a6f34a,#16b34a);color:#0b0d0c;display:grid;place-items:center;flex:none"><i class="fa-solid fa-graduation-cap"></i></span>
        <span class="flex-grow-1"><b class="d-block">Primeros pasos para vender más · {{ $ppRes['hechos'] }} de {{ $ppRes['total'] }}</b>
            <span style="display:block;height:8px;border-radius:99px;background:rgba(255,255,255,.15);overflow:hidden;margin-top:6px;max-width:360px"><i style="display:block;height:100%;width:{{ round($ppRes['hechos'] / $ppRes['total'] * 100) }}%;background:#a6f34a"></i></span></span>
        <span class="fw-bold" style="color:#a6f34a;white-space:nowrap">Continuar →</span>
    </a>
    @endif
    @endif
    <div class="lp-hello">
        <div>
            <small>{{ ucfirst(now()->locale('es')->isoFormat('dddd D [de] MMMM')) }}</small>
            <h1>{{ $saludo }}, <span>{{ \Illuminate\Support\Str::words(auth()->user()->name, 1, '') }}</span> 👋</h1>
        </div>
        <div class="lp-quick">
            @can('usar-pos')
                @if ($caja)
                    <a href="{{ route('pos.index') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-cash-register me-1"></i>Vender (POS)</a>
                    <a href="{{ route('cajas.cerrar') }}" class="btn btn-dark btn-lg"><i class="fa-solid fa-lock me-1"></i>Cerrar caja</a>
                @else
                    <a href="{{ route('cajas.abrir') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-lock-open me-1"></i>Abrir caja y vender</a>
                @endif
            @endcan
            @can('crear-producto')<a href="{{ route('productos.create') }}" class="btn btn-ghost btn-lg" style="border:2px solid #0b0d0c;background:#fff"><i class="fa-solid fa-plus me-1"></i>Producto</a>@endcan
            @php $tW = \App\Support\TiendaActual::tienda(); @endphp
            @if ($tW?->catalogo_activo)<a href="{{ $tW->url_catalogo_navegador }}" target="_blank" rel="noopener" class="btn btn-ghost btn-lg" style="border:2px solid #16b34a;background:#f2fbf3;color:#11702f"><i class="fa-solid fa-globe me-1"></i>Mi tienda en línea</a>@endif
        </div>
    </div>

    <div class="lp-kpis">
        <div class="lp-kpi hero">
            <div class="ic"><i class="fa-solid fa-sack-dollar"></i></div>
            <div class="lbl">Vendido hoy</div>
            <div class="val">@pesos($ventasHoy)</div>
            <div class="sub">{{ $numVentasHoy }} {{ $numVentasHoy === 1 ? 'venta' : 'ventas' }}@can('ver-reportes') · ganancia <b style="color:#a6f34a">@pesos($gananciaHoy)</b>@endcan</div>
        </div>
        <div class="lp-kpi">
            <div class="ic"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="lbl">Ventas del mes</div>
            <div class="val">@pesos($ventasMes)</div>
            <div class="sub">Desde el día 1</div>
        </div>
        <div class="lp-kpi">
            <div class="ic"><i class="fa-solid {{ \App\Models\Tienda::iconoProducto() }}"></i></div>
            <div class="lbl">{{ ucfirst(\App\Models\Tienda::palabraProducto(true)) }} en inventario</div>
            <div class="val">{{ dinero($unidades, false) }}</div>
            <div class="sub">
                @if ($caja) <span style="color:#16b34a">● Su caja está abierta</span>
                @else Unidades disponibles @endif
            </div>
        </div>
    </div>


    <div class="lp-box mb-3">
        <h3><span><i class="fa-solid fa-bullseye me-1" style="color:#16b34a"></i> Metas de su equipo de caja</span>@can('crear-user')<a href="{{ route('users.create') }}">+ Vendedor</a>@endcan</h3>
        @if ($equipo->isEmpty())
            <p class="lp-empty mb-0">Aún no tiene vendedores. Cree uno en <b>Usuarios</b> con el rol "Vendedor (caja)": venden en el POS, no ven ganancias y usted les pone metas aquí.</p>
        @else
        <div class="eq-lista">
            @foreach ($equipo as $u)
            @php
                $pv = ($u->meta_tipo ?? 'dinero') === 'ventas';
                $lD = $pv ? $u->num_dia : $u->vendido_dia; $lM = $pv ? $u->num_mes : $u->vendido_mes;
                $pd = $u->meta_dia > 0 ? min(100, round($lD / $u->meta_dia * 100)) : null;
                $pm = $u->meta_mes > 0 ? min(100, round($lM / $u->meta_mes * 100)) : null;
                // "1 de 30 ventas" o "$120.000 de $500.000": lo logrado contra la meta, sin porcentajes
                $fm = fn ($logrado, $meta) => $pv ? (int) $logrado . ' de ' . (int) $meta . ((int) $meta === 1 ? ' venta' : ' ventas') : dinero($logrado) . ' de ' . dinero($meta);
            @endphp
            <div class="eq-f">
                <div class="eq-n"><span class="eq-av">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span><div><b>{{ $u->name }}</b><small>Hoy {{ $u->num_dia }} {{ $u->num_dia == 1 ? 'venta' : 'ventas' }} · @pesos($u->vendido_dia)</small><small class="d-block"><b style="display:inline;color:#11702f">{{ $u->num_mes }} ventas en el mes</b> · @pesos($u->vendido_mes)</small></div></div>
                <div class="eq-m"><small>Meta de hoy {{ $pv ? '(ventas)' : '($)' }}</small>@if ($pd !== null)<span class="eq-b"><i style="width:{{ $pd }}%"></i></span><em>{{ $fm($lD, $u->meta_dia) }}</em>@else<em class="text-muted">Sin meta</em>@endif</div>
                <div class="eq-m"><small>Meta del mes {{ $pv ? '(ventas)' : '($)' }}</small>@if ($pm !== null)<span class="eq-b"><i style="width:{{ $pm }}%"></i></span><em>{{ $fm($lM, $u->meta_mes) }}</em>@else<em class="text-muted">Sin meta</em>@endif</div>
                <button type="button" class="btn btn-sm eq-btn" onclick="this.nextElementSibling.classList.toggle('d-none')"><i class="fa-solid fa-bullseye me-1"></i>Meta</button>
                <form method="post" action="{{ route('equipo.meta', $u) }}" class="eq-form d-none" data-sin-borrador>
                    @csrf
                    <div class="eq-tipo">
                        <small>¿Cómo quiere medir la meta de {{ strtok($u->name, ' ') }}?</small>
                        <label><input type="radio" name="meta_tipo" value="dinero" @checked(!$pv)><span>💰 Por dinero vendido</span></label>
                        <label><input type="radio" name="meta_tipo" value="ventas" @checked($pv)><span>🧾 Por cantidad de ventas</span></label>
                    </div>
                    <label><small>Meta del día</small><input type="number" min="0" step="any" name="meta_dia" class="form-control" value="{{ $u->meta_dia ? (float) $u->meta_dia : '' }}" placeholder="Ej: {{ $pv ? '10 ventas' : '500000' }}"></label>
                    <label><small>Meta del mes <span class="text-muted">(opcional)</span></small><input type="number" min="0" step="any" name="meta_mes" class="form-control" value="{{ $u->meta_mes ? (float) $u->meta_mes : '' }}" placeholder="Ej: {{ $pv ? '250 ventas' : '12000000' }}"></label>
                    <button class="btn btn-dark">Guardar meta</button>
                </form>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    <style>
    .eq-lista{display:flex;flex-direction:column}
    .eq-f{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,1fr) minmax(0,1fr) auto;gap:.9rem;align-items:center;padding:.8rem 0;border-bottom:1px dashed var(--lp-line,#e3ebe6)}
    .eq-f:last-child{border-bottom:0}
    .eq-n{display:flex;gap:.7rem;align-items:center;min-width:0}
    .eq-n b{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .eq-n small{color:#6b7570;font-weight:600;font-size:.8rem}
    .eq-av{width:40px;height:40px;border-radius:14px;background:#e8f8ec;color:#11702f;display:grid;place-items:center;font-weight:900;flex:none}
    .eq-m small{display:block;font-size:.7rem;font-weight:800;color:#7a8580;text-transform:uppercase;letter-spacing:.05em}
    .eq-m em{font-style:normal;font-weight:700;font-size:.8rem}
    .eq-b{display:block;height:7px;border-radius:9px;background:#e8eee9;margin:.25rem 0;overflow:hidden}
    .eq-b i{display:block;height:100%;background:linear-gradient(90deg,#a6f34a,#16b34a);border-radius:9px}
    .eq-btn{border:1.5px solid #cfe9d6;background:#f2fbf3;color:#11702f;font-weight:800;border-radius:999px}
    .eq-form{grid-column:1/-1;display:flex;gap:.6rem;align-items:flex-end;flex-wrap:wrap;background:#f7faf8;border-radius:16px;padding:.8rem}
    .eq-form label{flex:1 1 160px;margin:0}.eq-form small{font-weight:700;color:#5b665e}
    .eq-tipo{flex:1 1 100%;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center}
    .eq-tipo small{flex:1 1 100%}
    .eq-tipo label{flex:0 0 auto;cursor:pointer}
    .eq-tipo input{position:absolute;opacity:0;pointer-events:none}
    .eq-tipo span{display:inline-block;border:1.5px solid #dfe6e2;background:#fff;border-radius:999px;padding:.4rem .9rem;font-weight:700;font-size:.85rem}
    .eq-tipo input:checked + span{background:#0b0d0c;border-color:#0b0d0c;color:#fff}
    @media (max-width:767.98px){.eq-f{grid-template-columns:1fr 1fr}.eq-n{grid-column:1/-1}.eq-btn{grid-column:1/-1}}
    </style>

    <div class="lp-grid">
        <div class="lp-box">
            <h3>Ventas de la semana <a href="{{ route('pos.ventas') }}">Ver todas</a></h3>
            <div class="lp-bars">
                @foreach ($semana as $d)
                <div class="lp-bar {{ $loop->last ? 'today' : '' }}">
                    <i style="height:{{ max(3, round($d['total'] / $max * 100)) }}%" data-v="@pesos($d['total'])"></i>
                    <span>{{ $d['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="lp-box">
            <h3>Se está agotando <a href="{{ route('inventario.index', ['filtro' => 'bajo']) }}">Inventario</a></h3>
            <ul class="lp-list">
                @forelse ($stockBajo as $v)
                <li>
                    <div class="av"><i class="fa-solid {{ \App\Models\Tienda::iconoProducto() }}"></i></div>
                    <div class="t"><b>{{ $v->producto->nombre ?? '' }}</b><small>{{ $v->etiqueta }}</small></div>
                    <span class="lp-pill {{ $v->stock > 0 ? 'warn' : '' }}">{{ $v->stock > 0 ? cantidad($v->stock) . ' ' . ($v->producto?->unidad_corta ?? 'und') : 'Agotado' }}</span>
                </li>
                @empty
                <li class="lp-empty"><i class="fa-solid fa-circle-check me-1" style="color:#2fd15a"></i>Todo el inventario está bien surtido.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="lp-grid" style="grid-template-columns:1fr">
        <div class="lp-box">
            <h3>Últimas ventas <a href="{{ route('pos.ventas') }}">Historial</a></h3>
            <ul class="lp-list">
                @forelse ($ultimasVentas as $v)
                <li>
                    <div class="av"><i class="fa-solid fa-receipt"></i></div>
                    <div class="t"><b>{{ $v->cliente_nombre ?: 'Cliente de mostrador' }}</b><small>{{ $v->numero }} · {{ $v->created_at->diffForHumans() }} · {{ $v->user->name ?? '' }}</small></div>
                    @if ($v->estado === 'anulada')<span class="lp-pill">Anulada</span>@endif
                    <span class="m">@pesos($v->total)</span>
                </li>
                @empty
                <li class="lp-empty">Aún no hay ventas. Abra su caja y haga la primera desde el POS 🚀</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="lp-counts">
        @foreach ($conteos as [$nombre, $total, $icono, $ruta, $permiso])
            @can($permiso)
            <a class="lp-count" href="{{ route($ruta) }}">
                <div class="ci"><i class="fa-solid {{ $icono }}"></i></div>
                <b>{{ dinero($total, false) }}</b>
                <small>{{ $nombre }}</small>
            </a>
            @endcan
        @endforeach
    </div>
</div>
@endsection
