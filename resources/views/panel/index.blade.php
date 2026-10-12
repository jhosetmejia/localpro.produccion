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
        $tPlan = \App\Support\TiendaActual::tienda();
        $nProd = \App\Models\Producto::count();
        $nCli = \App\Models\Cliente::count();
        $compartio = (bool) \App\Models\Ajuste::valor('t' . $tPlan?->id . '_compartio');
        $tid = (int) $tPlan?->id;
        $nUsuarios = $tid ? \App\Models\User::where('tienda_id', $tid)->count() : 0;
        $nVentas = $tid ? \App\Models\PosVenta::where('tienda_id', $tid)->where('estado', 'pagada')->count() : 0;
        $tieneLogo = filled($tPlan?->logo_path);
        // Nivel 1: que le lleguen clientes
        $nivel1 = [
            ['Crea tus primeros 10 productos', min($nProd, 10) . ' de 10 listos', $nProd >= 10, route('productos.create'), 'Crear producto', 'fa-box-open', min(100, $nProd * 10), route('productos.importar'), '¿Los tiene en Excel?', false],
            ['Comparte tu tienda en línea con tus contactos', 'Envía tu página por WhatsApp a tus clientes y grupos', $compartio, route('panel.compartir'), 'Compartir por WhatsApp', 'fa-share-nodes', $compartio ? 100 : 0, null, null, true],
            ['Mete tus contactos al sistema', min($nCli, 10) . ' de 10 clientes · para enviarles mensajes por WhatsApp', $nCli >= 10, route('clientes.create'), 'Agregar cliente', 'fa-address-book', min(100, $nCli * 10), null, null, false],
        ];
        // Nivel 2: arme su negocio como un profesional
        $nivel2 = [
            ['Crea un usuario para tu vendedor o asesor', $nUsuarios > 1 ? 'Ya tiene ' . ($nUsuarios - 1) . ' en su equipo' : 'Cada uno entra con su clave y vende en su propia caja (no ve sus ganancias)', $nUsuarios > 1, route('users.create'), 'Crear usuario', 'fa-user-plus', 0, null, null, false],
            ['Pon el logo de tu negocio', 'Sale en sus tiquetes, facturas y en su tienda en línea', $tieneLogo, route('negocio.edit'), 'Subir logo', 'fa-image', 0, null, null, false],
            ['Haz tu primera venta en la caja', $nVentas > 0 ? $nVentas . ' ventas registradas' : 'Abra la caja y registre una venta: el inventario se descuenta solo', $nVentas > 0, route('cajas.abrir'), 'Abrir caja y vender', 'fa-cash-register', 0, null, null, false],
        ];
        $hechos1 = collect($nivel1)->where(2, true)->count();
        $hechos2 = collect($nivel2)->where(2, true)->count();
        $nivel = $hechos1 < 3 ? 1 : ($hechos2 < 3 ? 2 : 3);
        $pasos = $nivel === 1 ? $nivel1 : $nivel2;
        $hechos = $nivel === 1 ? $hechos1 : $hechos2;
    @endphp
    @include('layouts.partials.cuenta-regresiva')
    @include('layouts.partials.limite-plan', ['que' => 'productos'])
    @if (auth()->user()->can('crear-producto'))
    @if ($nivel < 3)
    <div class="vm-box">
        @if ($nivel === 2)
        <div class="vm-felicita">
            <span class="vm-stars"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
            <div><b>¡Muy bien! Completó el nivel 1</b><small>Ya tiene productos, compartió su tienda y tiene clientes. Ahora arme su negocio como un profesional.</small></div>
        </div>
        @endif
        <div class="vm-h"><div><small>Nivel {{ $nivel }} · {{ $nivel === 1 ? 'Para vender más' : 'Para crecer' }}</small><b>{{ $nivel === 1 ? '3 pasos para que le lleguen más clientes' : '3 pasos para manejar su negocio como un profesional' }}</b></div><span class="vm-n">{{ $hechos }}/3</span></div>
        <div class="vm-pasos">
            @foreach ($pasos as $i => [$tit, $sub, $ok, $url, $btn, $ic, $pct, $url2, $btn2, $nueva])
            <div class="vm-p {{ $ok ? 'ok' : '' }}">
                <span class="vm-i">@if ($ok)<i class="fa-solid fa-check"></i>@else{{ $i + 1 }}@endif</span>
                <div class="vm-t"><b>{{ $tit }}</b><small>{{ $sub }}</small>@if (!$ok && $pct > 0)<span class="vm-bar"><i style="width:{{ $pct }}%"></i></span>@endif</div>
                @if ($ok)
                <span class="vm-ok"><i class="fa-solid fa-star me-1"></i>¡Listo!</span>
                @else
                <div class="vm-a"><a href="{{ $url }}" class="vm-btn" @if ($nueva) target="_blank" rel="noopener" @endif><i class="fa-solid {{ $ic }} me-1"></i>{{ $btn }}</a>@if ($url2)<a href="{{ $url2 }}" class="vm-link">{{ $btn2 }}</a>@endif</div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="vm-fin" id="vm-fin" data-k="lp_fin_{{ $tid }}">
        <button type="button" class="vm-x" aria-label="Cerrar" onclick="try{localStorage.setItem(this.parentNode.dataset.k,'1')}catch(e){};this.parentNode.remove()">&times;</button>
        <span class="vm-stars grande"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></span>
        <b>¡Felicitaciones, {{ \Illuminate\Support\Str::words(auth()->user()->name, 1, '') }}! 🏆</b>
        <p>Completó todos los pasos. Usted es una persona que <b>se preocupa por su negocio</b>: tiene productos, clientes, su tienda en línea, su equipo y ya está vendiendo con LocalPro. ¡Siga así!</p>
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
    </style>
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
            @if ($tW?->catalogo_activo)<a href="{{ $tW->url_catalogo }}" target="_blank" class="btn btn-ghost btn-lg" style="border:2px solid #16b34a;background:#f2fbf3;color:#11702f"><i class="fa-solid fa-globe me-1"></i>Mi tienda en línea</a>@endif
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
            <div class="ic"><i class="fa-solid fa-shirt"></i></div>
            <div class="lbl">Prendas en inventario</div>
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
                $fm = fn ($n) => $pv ? (int) $n . ' ventas' : dinero($n);
            @endphp
            <div class="eq-f">
                <div class="eq-n"><span class="eq-av">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span><div><b>{{ $u->name }}</b><small>Hoy {{ $u->num_dia }} {{ $u->num_dia == 1 ? 'venta' : 'ventas' }} · @pesos($u->vendido_dia)</small><small class="d-block"><b style="display:inline;color:#11702f">{{ $u->num_mes }} ventas en el mes</b> · @pesos($u->vendido_mes)</small></div></div>
                <div class="eq-m"><small>Meta de hoy {{ $pv ? '(ventas)' : '($)' }}</small>@if ($pd !== null)<span class="eq-b"><i style="width:{{ $pd }}%"></i></span><em>{{ $pd }}% de {{ $fm($u->meta_dia) }}</em>@else<em class="text-muted">Sin meta</em>@endif</div>
                <div class="eq-m"><small>Meta del mes {{ $pv ? '(ventas)' : '($)' }}</small>@if ($pm !== null)<span class="eq-b"><i style="width:{{ $pm }}%"></i></span><em>{{ $pm }}% de {{ $fm($u->meta_mes) }}</em>@else<em class="text-muted">Sin meta</em>@endif</div>
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
                    <div class="av"><i class="fa-solid fa-shirt"></i></div>
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
