<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.partials.landing-head')
    @include('layouts.partials.lp-ui')
    <title>Configure su tienda — LocalPro</title>
    <style>
        body{background:radial-gradient(circle at 85% 0%,rgba(166,243,74,.28),transparent 40%),radial-gradient(circle at 0% 100%,rgba(255,111,181,.12),transparent 40%),#fff}
        .wz{width:min(860px,100% - 32px);margin:0 auto;padding:28px 0 60px}
        .wz-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px}
        .wz-top a{color:var(--muted);font-weight:700;font-size:.9rem}
        .steps-bar{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:26px}
        .sb{display:flex;flex-direction:column;gap:8px}
        .sb i{height:8px;border-radius:99px;background:var(--line);display:block}
        .sb.done i{background:var(--green-2)}.sb.now i{background:var(--grad)}
        .sb span{font-size:.78rem;font-weight:800;color:var(--muted)}
        .sb.now span{color:var(--black)}
        .sb.done span a{color:var(--green-2)}
        .card{background:#fff;border:1.5px solid var(--line);border-radius:28px;padding:clamp(20px,4vw,36px);box-shadow:0 30px 60px -40px rgba(22,179,74,.6)}
        h1{font-size:clamp(1.8rem,4vw,2.5rem);font-weight:800;letter-spacing:-.035em;line-height:1.05;margin-bottom:.4rem}
        .lead{color:var(--muted);margin-bottom:1.6rem}
        .grid2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
        .grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}
        @media(max-width:640px){.grid2,.grid3{grid-template-columns:1fr}}
        select.in{appearance:auto}
        select.in:disabled{opacity:.6}
        .tipos{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px;margin-bottom:1.2rem}
        .tipo input{position:absolute;opacity:0;pointer-events:none}
        .tipo label{display:flex;flex-direction:column;gap:.35rem;border:2px solid var(--line);border-radius:18px;padding:14px;cursor:pointer;height:100%;transition:all .15s;background:#fff}
        .tipo label i{font-size:1.3rem;color:var(--green-2)}
        .tipo label b{font-size:.95rem}
        .tipo label small{color:var(--muted);font-size:.76rem;line-height:1.3}
        .tipo input:checked+label{border-color:var(--green);background:var(--soft);box-shadow:0 0 0 4px rgba(47,209,90,.15)}
        .tipo input:focus-visible+label{outline:3px solid var(--green)}
        .mods{display:flex;flex-direction:column;gap:8px;margin-bottom:1.2rem}
        .mod{display:block;cursor:pointer;margin:0}
        .mod input[type=checkbox]{position:absolute;opacity:0;pointer-events:none}
        .mod-c{display:flex;gap:12px;align-items:center;border:2px solid var(--line);border-radius:16px;padding:12px 14px;background:#fff;transition:all .15s}
        .mod-c > i{font-size:1.2rem;color:var(--green-2);width:26px;text-align:center;flex:none}
        .mod-c > span{flex:1;min-width:0}.mod-c b{display:block;font-size:.95rem}.mod-c small{color:var(--muted);font-size:.78rem;line-height:1.3}
        .mod-sw{width:44px;height:26px;border-radius:999px;background:#dfe6e2;position:relative;flex:none;transition:background .15s}
        .mod-sw::after{content:"";position:absolute;top:3px;left:3px;width:20px;height:20px;border-radius:50%;background:#fff;box-shadow:0 2px 6px rgba(0,0,0,.25);transition:left .15s}
        .mod input:checked + .mod-c{border-color:var(--green);background:var(--soft)}
        .mod input:checked + .mod-c .mod-sw{background:var(--green-2)}
        .mod input:checked + .mod-c .mod-sw::after{left:21px}
        .mod input:focus-visible + .mod-c{outline:3px solid var(--green)}
        .field label{display:block;font-size:.85rem;font-weight:800;margin-bottom:.4rem}
        .in{width:100%;padding:.85rem 1rem;border-radius:14px;border:2px solid var(--line);background:#fafdfb;font:inherit;font-weight:600;color:var(--ink)}
        .in:focus{outline:none;border-color:var(--green);background:#fff;box-shadow:0 0 0 5px rgba(47,209,90,.15)}
        .prods{display:grid;gap:12px}
        .prod{border:2px solid var(--line);border-radius:20px;padding:14px;display:grid;grid-template-columns:auto 1fr;gap:12px;align-items:start;transition:opacity .15s,border-color .15s}
        .prod.off{opacity:.45}
        .prod .chk{width:24px;height:24px;margin-top:10px;accent-color:#16b34a}
        .prod .row{display:grid;grid-template-columns:2fr 1fr 1fr;gap:8px}
        .prod .row2{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:8px}
        @media(max-width:640px){.prod .row,.prod .row2{grid-template-columns:1fr}}
        .prod small{display:block;font-size:.72rem;font-weight:800;color:var(--muted);margin:0 0 .25rem .2rem;text-transform:uppercase;letter-spacing:.06em}
        .mets{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;margin-bottom:1.2rem}
        .met input{position:absolute;opacity:0}
        .met label{display:flex;align-items:center;gap:.6rem;border:2px solid var(--line);border-radius:16px;padding:14px;font-weight:800;cursor:pointer;background:#fff}
        .met label i{color:var(--green-2)}
        .met input:checked+label{border-color:var(--green);background:var(--soft)}
        .met input:checked+label::after{content:"✓";margin-left:auto;color:var(--green-2)}
        .acts{display:flex;justify-content:space-between;gap:10px;margin-top:1.6rem;flex-wrap:wrap}
        .tip{background:var(--soft);border:1.5px solid #c9efd2;border-radius:16px;padding:12px 14px;font-size:.88rem;color:#11702f;font-weight:600;margin-bottom:1.2rem}
        .logo-prev{width:72px;height:72px;border-radius:18px;border:2px dashed var(--line);display:grid;place-items:center;overflow:hidden;flex:none;background:#fafdfb}
        .logo-prev img{max-width:100%;max-height:100%;object-fit:contain}
    </style>
</head>
<body>
<div class="wz">
    <div class="wz-top">
        <a href="{{ url('/') }}" class="brand"><span class="brand-mark">L</span>LocalPro</a>
        <a href="{{ route('logout') }}"><i class="fa-solid fa-arrow-right-from-bracket"></i> Salir</a>
    </div>

    @php $nombres = [1 => 'Sus datos', 2 => 'Su negocio', 3 => 'Productos', 4 => 'Cobro y caja']; @endphp
    <div class="steps-bar">
        @foreach ($nombres as $n => $txt)
        <div class="sb {{ $n < $paso ? 'done' : ($n === $paso ? 'now' : '') }}">
            <i></i>
            <span>
                @if ($n < $paso && $n >= 2)<a href="{{ route('bienvenida.paso', $n) }}">{{ $n }}. {{ $txt }} ✓</a>
                @else {{ $n }}. {{ $txt }} @if($n < $paso) ✓ @endif
                @endif
            </span>
        </div>
        @endforeach
    </div>

    <div class="card rise">
        @if ($errors->any())
            <div class="alert">{{ $errors->first() }}</div>
        @endif

        {{-- ===== PASO 2: NEGOCIO ===== --}}
        @if ($paso === 2)
        <h1>Cuéntenos de su <span class="grad-text">negocio</span></h1>
        <p class="lead">Con esto armamos su sistema a la medida: sus facturas, sus productos y su forma de vender.</p>
        <form method="post" action="{{ route('bienvenida.negocio') }}" enctype="multipart/form-data">
            @csrf
            <div class="field" style="margin-bottom:1.1rem">
                <label>Nombre del negocio</label>
                <input class="in" name="nombre" value="{{ old('nombre', str_starts_with($tienda->nombre, 'Tienda de ') ? '' : $tienda->nombre) }}" placeholder="Ej: Moda Urbana Pasto" maxlength="80" required autofocus>
            </div>

            <div class="field"><label>¿Qué tipo de negocio es?</label></div>
            <div class="tipos">
                @foreach ($tipos as $clave => [$nombre, $icono, $tallas, $desc])
                <div class="tipo">
                    <input type="radio" name="tipo_negocio" id="t_{{ $clave }}" value="{{ $clave }}" @checked(old('tipo_negocio', $tienda->tipo_negocio) === $clave) required>
                    <label for="t_{{ $clave }}"><i class="fa-solid {{ $icono }}"></i><b>{{ $nombre }}</b><small>{{ $desc }}</small></label>
                </div>
                @endforeach
            </div>

            <div class="mods" id="mods">
                <div class="field" style="margin-bottom:.4rem"><label>¿Qué necesita su negocio? <span style="color:var(--muted);font-weight:600">(lo marcamos según su tipo; puede cambiarlo cuando quiera)</span></label></div>
                @foreach ([
                    'mod_medida' => ['fa-ruler-combined', 'Vendo por metro, kilo o litro', 'Ej: vidrio, telas, cables, carnes, granos. En la caja se venden medidas como 1,5 m o 0,250 kg.'],
                    'mod_vencimiento' => ['fa-calendar-xmark', 'Mis productos tienen fecha de vencimiento', 'Ej: droguería, alimentos, cosméticos. Le avisamos lo que está por vencer.'],
                    'mod_recetas' => ['fa-bowl-food', 'Preparo productos con insumos (recetas)', 'Ej: restaurante, panadería. Al vender se descuentan los ingredientes.'],
                ] as $k => [$ic, $tit, $txt])
                <label class="mod">
                    <input type="hidden" name="{{ $k }}" value="0">
                    <input type="checkbox" name="{{ $k }}" value="1" data-mod="{{ $k }}" @checked(old($k, $tienda->$k))>
                    <span class="mod-c"><i class="fa-solid {{ $ic }}"></i><span><b>{{ $tit }}</b><small>{{ $txt }}</small></span><em class="mod-sw"></em></span>
                </label>
                @endforeach
            </div>

            <div class="field" style="margin-bottom:1.1rem">
                <label>¿Qué vende exactamente? <span style="color:var(--muted);font-weight:600">(opcional)</span></label>
                <input class="in" name="descripcion" value="{{ old('descripcion', $tienda->descripcion) }}" placeholder="Ej: Ropa urbana para hombre y mujer" maxlength="120">
            </div>
            <div class="grid3" style="margin-bottom:1.1rem">
                @include('layouts.partials.ubicacion', ['pais' => $tienda->pais, 'departamento' => $tienda->departamento, 'ciudad' => $tienda->ciudad, 'clase' => 'in', 'envoltura' => 'field', 'etiqueta' => ''])
            </div>
            <div class="grid2">
                <div class="field"><label>Teléfono / WhatsApp de la tienda</label><input class="in" name="telefono" value="{{ old('telefono', $tienda->telefono) }}" maxlength="30"></div>
                <div class="field"><label>NIT / RUT <span style="color:var(--muted);font-weight:600">(opcional)</span></label><input class="in" name="nit" value="{{ old('nit', $tienda->nit) }}" maxlength="30"></div>
                <div class="field">
                    <label>Logo <span style="color:var(--muted);font-weight:600">(opcional)</span></label>
                    <div style="display:flex;gap:10px;align-items:center">
                        <div class="logo-prev">@if($tienda->logo_url)<img id="lp" src="{{ $tienda->logo_url }}">@else<img id="lp" style="display:none"><i id="li" class="fa-regular fa-image" style="color:#bfeccb;font-size:1.4rem"></i>@endif</div>
                        <input class="in" type="file" name="logo" accept="image/png,image/jpeg,image/webp" style="padding:.6rem" onchange="var f=this.files[0];if(f){var i=document.getElementById('lp');i.src=URL.createObjectURL(f);i.style.display='';var c=document.getElementById('li');if(c)c.style.display='none';}">
                    </div>
                </div>
            </div>
            <div class="acts"><span></span><button class="btn btn-ink">Continuar · Productos <i class="fa-solid fa-arrow-right"></i></button></div>
        </form>
        <script>
        (function () {
            var MAP = @json(\App\Models\Tienda::MODULOS_POR_TIPO), tocados = {};
            var mods = document.getElementById('mods'); if (!mods) return;
            mods.addEventListener('change', function (e) { if (e.target.dataset.mod) tocados[e.target.dataset.mod] = true; });
            function aplicar(tipo) {
                mods.style.display = tipo === 'conjunto' ? 'none' : '';
                var on = MAP[tipo] || [];
                mods.querySelectorAll('[data-mod]').forEach(function (c) { if (!tocados[c.dataset.mod]) c.checked = on.indexOf(c.dataset.mod) !== -1; });
            }
            document.querySelectorAll('input[name=tipo_negocio]').forEach(function (r) { r.addEventListener('change', function () { aplicar(r.value); }); });
            var marcado = document.querySelector('input[name=tipo_negocio]:checked');
            if (marcado && {{ ($tienda->mod_medida || $tienda->mod_vencimiento || $tienda->mod_recetas || old('mod_medida') !== null) ? 'false' : 'true' }}) aplicar(marcado.value);
            else if (marcado) mods.style.display = marcado.value === 'conjunto' ? 'none' : '';
        })();
        </script>
        @endif

        {{-- ===== PASO 3: PRODUCTOS ===== --}}
        @if ($paso === 3)
        <h1>Sus primeros <span class="grad-text">productos</span></h1>
        <p class="lead">Le dejamos {{ count($ejemplos) }} ejemplos de {{ mb_strtolower($tienda->tipo_nombre) }}. Cámbielos por los suyos, desmarque los que no quiera o agregue más. Después podrá cargar todo su inventario.</p>
        @if ($tienda->usa_tallas)
            <div class="tip"><i class="fa-solid fa-lightbulb me-1"></i> Escriba las tallas y colores separados por coma. Se crea cada combinación con la cantidad indicada (ej. 4 tallas × 2 colores = 8 referencias).</div>
        @endif
        <div class="tip" style="background:#eef7ff;color:#164a7a"><i class="fa-solid fa-file-excel me-1"></i> <b>¿Ya tiene cientos de productos en Excel o en otro sistema?</b> Pulse "Omitir por ahora" y luego en <b>Productos → Importar Excel</b> súbalos todos de una vez, o envíenos el archivo y nuestro equipo se los carga.</div>
        @php $sinConteo = in_array($tienda->tipo_negocio, ['restaurante', 'cafeteria']); @endphp
        @if ($sinConteo)
            <div class="tip"><i class="fa-solid fa-utensils me-1"></i> En su tipo de negocio no hace falta contar unidades: se vende sin límite. Si algún producto sí lo necesita, actívelo luego en Productos.</div>
        @endif
        <form method="post" action="{{ route('bienvenida.productos') }}">
            @csrf
            <div class="prods" id="prods">
                @foreach (old('productos', $ejemplos) as $i => $p)
                <div class="prod">
                    <input type="hidden" name="productos[{{ $i }}][incluir]" value="0">
                    <input class="chk" type="checkbox" name="productos[{{ $i }}][incluir]" value="1" @checked(old("productos.$i.incluir", 1)) onchange="this.closest('.prod').classList.toggle('off', !this.checked)">
                    <div>
                        <div class="row">
                            <div><small>Producto</small><input class="in" name="productos[{{ $i }}][nombre]" value="{{ $p['nombre'] ?? '' }}" maxlength="80"></div>
                            <div><small>Precio ({{ moneda()['moneda'] }})</small><input data-dinero class="in" type="number" min="0" step="{{ paso_dinero() }}" name="productos[{{ $i }}][precio]" value="{{ $p['precio'] ?? '' }}" placeholder="0"></div>
                            <div @if($sinConteo) style="display:none" @endif><small>{{ $tienda->usa_tallas ? 'Cantidad c/u' : 'Cantidad' }}</small><input class="in" type="number" min="0" name="productos[{{ $i }}][cantidad]" value="{{ $p['cantidad'] ?? 0 }}"></div>
                        </div>
                        @if ($tienda->usa_tallas)
                        <div class="row2">
                            <div><small>Tallas</small><input class="in" name="productos[{{ $i }}][tallas]" value="{{ $p['tallas'] ?? '' }}" placeholder="S, M, L"></div>
                            <div><small>Colores</small><input class="in" name="productos[{{ $i }}][colores]" value="{{ $p['colores'] ?? '' }}" placeholder="Negro, Blanco"></div>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-ghost" id="mas" style="margin-top:12px"><i class="fa-solid fa-plus"></i> Agregar otro producto</button>
            <div class="acts">
                <button class="btn btn-ghost" name="omitir" value="1" formnovalidate>Omitir por ahora</button>
                <button class="btn btn-ink">Guardar y continuar <i class="fa-solid fa-arrow-right"></i></button>
            </div>
        </form>
        <script>
            (function () {
                var box = document.getElementById('prods'), btn = document.getElementById('mas');
                btn.addEventListener('click', function () {
                    var n = box.children.length;
                    if (n >= 8) { btn.disabled = true; return; }
                    var c = box.children[0].cloneNode(true);
                    c.classList.remove('off');
                    c.querySelectorAll('input').forEach(function (i) {
                        i.name = i.name.replace(/productos\[\d+\]/, 'productos[' + n + ']');
                        if (i.type === 'checkbox') i.checked = true;
                        else if (i.type !== 'hidden') i.value = i.type === 'number' ? '' : '';
                    });
                    box.appendChild(c);
                    c.querySelector('input[name$="[nombre]"]').focus();
                    if (n + 1 >= 8) btn.disabled = true;
                });
            })();
        </script>
        @endif

        {{-- ===== PASO 4: COBRO Y CAJA ===== --}}
        @if ($paso === 4)
        <h1>¿Cómo le <span class="grad-text">pagan</span>?</h1>
        <p class="lead">Marque las formas de pago que acepta. Las podrá cambiar cuando quiera en "Métodos de pago".</p>
        <form method="post" action="{{ route('bienvenida.cobro') }}">
            @csrf
            <div class="mets">
                @foreach ($metodos as $m)
                <div class="met">
                    <input type="checkbox" name="metodos[]" id="m{{ $m->id }}" value="{{ $m->id }}" @checked($m->activo)>
                    <label for="m{{ $m->id }}"><i class="fa-solid {{ $m->es_efectivo ? 'fa-money-bill-wave' : 'fa-mobile-screen-button' }}"></i>{{ $m->nombre }}</label>
                </div>
                @endforeach
            </div>
            <div class="grid2">
                <div class="field"><label>¿Otra forma de pago? <span style="color:var(--muted);font-weight:600">(opcional)</span></label><input class="in" name="nuevo_metodo" maxlength="40" placeholder="Ej: Addi, Sistecrédito, QR"></div>
                <div class="field"><label>Base de caja para empezar hoy</label><input data-dinero class="in" type="number" min="0" step="{{ paso_dinero() }}" name="monto_inicial" value="0"><small style="color:var(--muted)">El efectivo con el que abre su caja ({{ moneda()['moneda'] }}).</small></div>
            </div>
            <div class="acts">
                <span></span>
                <button class="btn btn-amber" style="font-size:1.05rem;padding:1rem 1.8rem"><i class="fa-solid fa-rocket"></i> ¡Empezar a vender!</button>
            </div>
        </form>
        @endif
    </div>
    <p style="text-align:center;color:var(--muted);font-size:.85rem;margin-top:18px">© {{ date('Y') }} LocalProo · Todos los derechos reservados</p>
</div>
</body>
</html>
