@extends('layouts.app')

@section('title', 'Nuevo producto')

@push('css')
<style>
    .pf-sec{background:#fff;border:1.5px solid var(--lp-line);border-radius:22px;padding:1.2rem 1.2rem 1.3rem;margin-bottom:14px}
    .pf-sec h3{font-size:1.05rem;font-weight:800;margin:0 0 .9rem;display:flex;align-items:center;gap:.55rem}
    .pf-num{width:28px;height:28px;border-radius:9px;background:var(--lp-black);color:var(--lp-lime);display:inline-grid;place-items:center;font-size:.85rem;flex:none}
    .pf-foto{display:flex;gap:12px;align-items:center;border:2px dashed var(--lp-line);border-radius:18px;padding:10px;cursor:pointer;background:#fafdfb}
    .pf-foto .ph{width:76px;height:76px;border-radius:14px;background:var(--lp-bg);display:grid;place-items:center;overflow:hidden;flex:none;color:#bfeccb;font-size:1.6rem}
    .pf-foto .ph img{width:100%;height:100%;object-fit:contain;background:#fff}
    .pf-foto b{display:block}
    .pf-foto small{color:var(--lp-muted)}
    .pf-pres{display:flex;flex-wrap:wrap;gap:6px;margin-top:.5rem}
    .pf-pres button{border:1.5px solid var(--lp-line);background:#fff;border-radius:999px;padding:.3rem .75rem;font-weight:700;font-size:.82rem;font-family:inherit}
    .pf-pres button:hover{border-color:#2fd15a}
    .pf-tabla{border:1.5px solid var(--lp-line);border-radius:16px;overflow:hidden;margin-top:.9rem}
    .pf-fila{display:flex;align-items:center;gap:10px;padding:.55rem .8rem;border-bottom:1px solid var(--lp-line)}
    .pf-fila:last-child{border-bottom:0}
    .pf-fila b{flex:1;font-size:.95rem}
    .pf-fila input{width:96px;text-align:center;font-weight:800}
    .pf-fila{flex-wrap:wrap}
    .pf-bar{flex-basis:100%;display:flex;gap:6px;align-items:center}
    .pf-bar input.form-control,.pf-barra input.form-control{width:auto;flex:1;text-align:left;font-weight:700;font-size:.85rem;letter-spacing:.02em}
    .pf-bar button,.pf-barra button{flex:none;border:0;border-radius:10px;background:var(--lp-black);color:var(--lp-lime);font-weight:800;font-size:.8rem;padding:.42rem .7rem;white-space:nowrap}
    .pf-barra{display:flex;gap:6px;align-items:center;max-width:460px}
    .pf-cab{background:#fbfdfb;font-size:.75rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--lp-muted)}
    .pf-total{display:flex;justify-content:space-between;align-items:center;background:var(--lp-black);color:#fff;border-radius:14px;padding:.7rem 1rem;margin-top:.8rem;font-weight:800}
    .pf-total span:last-child{color:var(--lp-lime);font-size:1.2rem}
    .pf-guardar{position:sticky;bottom:10px;z-index:5}
    details.pf-mas summary{cursor:pointer;font-weight:800;list-style:none;display:flex;align-items:center;gap:.5rem}
    details.pf-mas summary::-webkit-details-marker{display:none}
    details.pf-mas summary::after{content:"▾";margin-left:auto;color:var(--lp-muted)}
    details.pf-mas[open] summary::after{content:"▴"}
</style>
@endpush

@section('content')
@php
    $tienda = \App\Support\TiendaActual::tienda();
    $moda = (bool) $tienda?->usa_tallas;
    $sinConteo = in_array($tienda?->tipo_negocio, ['restaurante', 'cafeteria']);
@endphp
<div class="lp-dash" style="max-width:760px;margin:0 auto">
    <div class="lp-hello">
        <div><small><a href="{{ route('productos.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Productos</a></small><h1>Nuevo <span>producto</span></h1></div>
    </div>
    @include('layouts.partials.lp-flash')

    <form action="{{ route('productos.store') }}" method="post" enctype="multipart/form-data" id="pf">
        @csrf

        {{-- 1. Lo básico --}}
        <div class="pf-sec">
            <h3><span class="pf-num">1</span>¿Qué va a vender?</h3>
            <div class="row g-3">
                <div class="col-12">
                    <label for="nombre" class="form-label">Nombre del producto</label>
                    <input type="text" name="nombre" id="nombre" class="form-control form-control-lg" value="{{ old('nombre') }}" placeholder="{{ $moda ? 'Ej: Chaqueta de cuero con ovejero' : 'Ej: Hamburguesa sencilla' }}" maxlength="80" required autofocus>
                    @error('nombre')<small class="text-danger">{{ $message }}</small>@enderror
                    @include('producto._seo')
                </div>
                <div class="col-12">
                    <label for="precio_venta" class="form-label">Precio de venta ({{ moneda()['moneda'] }})</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text fw-bold" style="border-radius:12px 0 0 12px">{{ moneda()['simbolo'] }}</span>
                        <input data-dinero type="number" min="0" step="{{ paso_dinero() }}" name="precio_venta" id="precio_venta" class="form-control fw-bold" value="{{ old('precio_venta') }}" placeholder="{{ moneda()['decimales'] ? '29.90' : '130000' }}" inputmode="decimal" required>
                    </div>
                    @error('precio_venta')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                @include('producto._promo')
                @include('producto._impuesto')
                @if (\App\Support\TiendaActual::tienda()?->mod_medida)
                <div class="col-12">
                    <label for="unidad" class="form-label">Se vende por</label>
                    <select name="unidad" id="unidad" class="form-select form-select-lg">
                        @foreach (\App\Models\Producto::UNIDADES as $k => $u)<option value="{{ $k }}" @selected(old('unidad', 'und') === $k)>{{ $u[0] }}{{ $k !== 'und' ? ' (' . $u[1] . ')' : '' }}</option>@endforeach
                    </select>
                    <small class="text-muted">El precio es por cada <b id="unTxt">unidad</b>. En la caja podrá vender medidas con decimales, ej. 1,5 m o 0,250 kg.</small>
                </div>
                @endif
                <div class="col-12">
                    <label class="form-label">Foto <span class="text-muted fw-normal">(opcional)</span></label>
                    <label class="pf-foto" for="img_path">
                        <span class="ph" id="fotoPrev"><i class="fa-regular fa-image"></i></span>
                        <span><b id="fotoTxt">Tomar o elegir una foto</b><small>Así la ven sus clientes en el catálogo</small></span>
                    </label>
                    <input type="file" name="img_path" id="img_path" accept="image/*" class="d-none" data-foto-lista>
                    <script src="{{ asset('js/foto-lista.js') }}?v=2"></script>
                    @error('img_path')<small class="text-danger">{{ $message }}</small>@enderror
                    <div class="form-check mt-2 p-2 ps-4" style="background:#fff8e6;border-radius:12px" id="sinFotoBox">
                        <input class="form-check-input" type="checkbox" name="mostrar_sin_foto" value="1" id="mostrarSinFoto" @checked(old('mostrar_sin_foto', false))>
                        <label class="form-check-label small fw-semibold" for="mostrarSinFoto"><i class="fa-solid fa-triangle-exclamation me-1" style="color:#c78a00"></i>Si no tiene foto, no se mostrará en el catálogo. <b>Mostrar aunque no tenga foto</b></label>
                    </div>
                </div>
            </div>
        </div>

        {{-- 2. Cantidades --}}
        <div class="pf-sec">
            <h3><span class="pf-num">2</span>¿Cuántas unidades tiene?</h3>

            @if ($moda)
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="conTallas" @checked(old('con_tallas', '1') === '1')>
                <input type="hidden" name="con_tallas" id="conTallasVal" value="{{ old('con_tallas', '1') }}">
                <label class="form-check-label fw-bold" for="conTallas">Este producto tiene tallas o colores</label>
            </div>

            <div id="bloqueTallas">
                <label class="form-label">1) Escriba las tallas <span class="text-muted fw-normal">(separadas por coma)</span></label>
                <input type="text" id="tallas" class="form-control" placeholder="Toque las tallas de abajo o escríbalas" value="{{ old('tallas_txt') }}" name="tallas_txt" autocomplete="off">
                @include('producto._tallas', ['para' => 'tallas'])

                <label class="form-label mt-3">2) Colores <span class="text-muted fw-normal">(opcional, separados por coma)</span></label>
                <input type="text" id="colores" class="form-control" placeholder="Toque los colores de abajo o escríbalos" value="{{ old('colores_txt') }}" name="colores_txt" autocomplete="off">
                @include('producto._paleta', ['para' => 'colores'])

                <div id="tablaWrap" style="display:none">
                    <label class="form-label mt-3 mb-0">3) ¿Cuántas hay de cada una?</label>
                    <div class="d-flex gap-2 align-items-center mt-2 flex-wrap">
                        <small class="text-muted">Poner la misma cantidad a todas:</small>
                        <input type="number" min="0" id="todas" class="form-control" style="width:90px" placeholder="0" inputmode="numeric">
                        <button type="button" class="btn btn-sm btn-dark" id="aplicarTodas">Aplicar</button>
                    </div>
                    <div class="pf-tabla">
                        <div class="pf-fila pf-cab"><b>Talla / color</b><span style="width:96px;text-align:center">Cantidad</span></div>
                        <div id="filas"></div>
                    </div>
                </div>
            </div>
            @endif

            <div id="bloqueUnica" @if($moda) style="display:none" @endif>
                @unless ($sinConteo)
                <label for="cantidad_inicial" class="form-label">Cantidad disponible <span id="cantUn" class="text-muted fw-normal"></span></label>
                <input type="number" min="0" step="any" name="cantidad_inicial" id="cantidad_inicial" class="form-control form-control-lg fw-bold" style="max-width:220px" value="{{ old('cantidad_inicial', 0) }}" inputmode="decimal">
                @else
                <p class="text-muted mb-0"><i class="fa-solid fa-utensils me-1"></i> En su negocio no hace falta contar unidades: se vende sin límite. Puede cambiarlo en "Más opciones".</p>
                @endunless
                <label for="codigo_barras" class="form-label mt-3">Código de barras <span class="text-muted fw-normal">(opcional · si ya le pegó una etiqueta, escanéela)</span></label>
                <div class="pf-barra">
                    <input type="text" name="codigo_barras" id="codigo_barras" class="form-control" data-barras value="{{ old('codigo_barras', request('barras')) }}" placeholder="Vacío = el sistema le crea uno" autocomplete="off">
                    <button type="button" data-barras-esc="codigo_barras"><i class="fa-solid fa-camera me-1"></i>Escanear</button>
                </div>
            </div>

            <div class="pf-total" id="totalBox" style="display:none"><span>Total de unidades</span><span id="totalUnd">0</span></div>
            @error('variantes')<small class="text-danger d-block mt-2">{{ $message }}</small>@enderror
            @error('codigo_barras')<small class="text-danger d-block mt-2">{{ $message }}</small>@enderror
            <div class="small text-muted mt-2"><i class="fa-solid fa-barcode me-1"></i>¿La mercancía ya está sellada con <a href="{{ route('etiquetas.nuevas') }}">etiquetas en blanco</a>? Escanee la etiqueta en cada talla/color y el producto queda con ese código (puede escanear varias si prendas iguales tienen códigos distintos).</div>
        </div>

        {{-- 3. Categorías --}}
        <div class="pf-sec">
            <h3><span class="pf-num">3</span>Categoría</h3>
            <div class="row">
                @include('producto._categorias', ['seleccionadas' => []])
            </div>
        </div>

        {{-- Más opciones --}}
        <div class="pf-sec">
            <details class="pf-mas" @if($errors->hasAny(['codigo', 'precio_costo', 'descripcion'])) open @endif>
                <summary><i class="fa-solid fa-sliders"></i> Más opciones <small class="text-muted fw-normal">(costo, código, descripción)</small></summary>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label for="precio_costo" class="form-label">Precio de costo <span class="text-muted fw-normal">(lo que le costó)</span></label>
                        <input data-dinero type="number" min="0" step="{{ paso_dinero() }}" name="precio_costo" id="precio_costo" class="form-control" value="{{ old('precio_costo') }}" placeholder="Para calcular su ganancia" inputmode="decimal">
                        @error('precio_costo')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="codigo" class="form-label">Código <span class="text-muted fw-normal">(vacío = automático)</span></label>
                        <input type="text" name="codigo" id="codigo" class="form-control" value="{{ old('codigo') }}" maxlength="50">
                        @error('codigo')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-12">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea name="descripcion" id="descripcion" rows="2" class="form-control" maxlength="255" style="resize:none" placeholder="Material, detalles… (sale en el catálogo)">{{ old('descripcion') }}</textarea>
                    </div>
                    <div class="col-12">
                        <input type="hidden" name="controla_inventario" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" name="controla_inventario" value="1" id="controla_inventario" @checked(old('controla_inventario', $sinConteo ? '0' : '1') === '1')>
                            <label class="form-check-label" for="controla_inventario"><b>Controlar inventario</b> <small class="text-muted d-block">Apáguelo en comidas preparadas o servicios: se vende sin límite de cantidad.</small></label>
                        </div>
                    </div>
                </div>
            </details>
        </div>

        <div class="pf-guardar">
            <button type="submit" class="btn btn-primary btn-lg w-100" style="padding:1rem;font-size:1.1rem"><i class="fa-solid fa-check me-1"></i>Guardar producto</button>
        </div>
    </form>
</div>
@endsection

@push('js')
<script src="{{ asset('js/escaner.js') }}?v=1"></script>
<script>
(function () {
    var f = document.getElementById('pf');

    // Foto con vista previa
    document.getElementById('img_path').addEventListener('change', function () {
        var a = this.files[0]; if (!a) return;
        document.getElementById('fotoPrev').innerHTML = '<img src="' + URL.createObjectURL(a) + '" alt="">';
        document.getElementById('fotoTxt').textContent = a.name;
    });

    var tallas = document.getElementById('tallas');
    if (tallas) {
        var colores = document.getElementById('colores'), filas = document.getElementById('filas');
        @php
            $guardadasJs = collect(old('variantes', []))->mapWithKeys(fn ($v) => [mb_strtoupper(trim(($v['talla'] ?? '') . '|' . ($v['color'] ?? ''))) => $v['cantidad'] ?? 0]);
            $barrasJs = collect(old('variantes', []))->mapWithKeys(fn ($v) => [mb_strtoupper(trim(($v['talla'] ?? '') . '|' . ($v['color'] ?? ''))) => $v['barras'] ?? '']);
        @endphp
        var guardadas = @json($guardadasJs), barrasG = @json($barrasJs), barrasIni = @json(old('variantes') ? '' : (string) request('barras'));
        var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); };
        var lista = function (t) {
            var vistos = {};
            return t.split(/\s*(?:[,;\/\n]|\s+y\s+)\s*/i).map(function (x) { return x.trim().toUpperCase(); })
                .filter(function (x) { if (!x || vistos[x]) return false; vistos[x] = 1; return true; });
        };

        function generar() {
            // Conserva lo que ya escribió al cambiar tallas o colores
            filas.querySelectorAll('.pf-fila').forEach(function (r) { guardadas[r.dataset.k] = r.querySelector('input[type=number]').value; barrasG[r.dataset.k] = r.querySelector('[data-barras]').value; });
            var ts = window.LPTallas ? LPTallas.lista(tallas.value) : lista(tallas.value), cs = window.LPColores ? LPColores.lista(colores.value) : lista(colores.value);
            if (!ts.length && !cs.length) { filas.innerHTML = ''; document.getElementById('tablaWrap').style.display = 'none'; total(); return; }
            ts = ts.length ? ts : [''];
            cs = cs.length ? cs : [''];
            var html = '', i = 0;
            ts.forEach(function (t) {
                cs.forEach(function (c) {
                    var k = t + '|' + c, et = [t, c].filter(Boolean).join(' · ');
                    html += '<div class="pf-fila" data-k="' + esc(k) + '"><b>' + esc(et) + '</b>' +
                        '<input type="hidden" name="variantes[' + i + '][talla]" value="' + esc(t) + '">' +
                        '<input type="hidden" name="variantes[' + i + '][color]" value="' + esc(c) + '">' +
                        '<input type="number" min="0" class="form-control" name="variantes[' + i + '][cantidad]" value="' + esc(guardadas[k] || '') + '" placeholder="0" inputmode="numeric">' +
                        '<div class="pf-bar"><input type="text" class="form-control form-control-sm" data-barras id="barras' + i + '" name="variantes[' + i + '][barras]" value="' + esc(barrasG[k] || (i === 0 ? barrasIni : '') || '') + '" placeholder="Código de barras (opcional, escanee la etiqueta)" autocomplete="off">' +
                        '<button type="button" data-barras-esc="barras' + i + '"><i class="fa-solid fa-camera"></i></button></div></div>';
                    i++;
                });
            });
            filas.innerHTML = html;
            if (html) barrasIni = '';
            document.getElementById('tablaWrap').style.display = '';
            total();
        }
        function total() {
            var conT = document.getElementById('conTallas').checked, n = 0;
            if (conT) filas.querySelectorAll('input[type=number]').forEach(function (x) { n += +x.value || 0; });
            else { var u = document.getElementById('cantidad_inicial'); n = u ? +u.value || 0 : 0; }
            document.getElementById('totalUnd').textContent = n;
            document.getElementById('totalBox').style.display = (conT && filas.children.length) ? '' : 'none';
        }
        tallas.addEventListener('input', generar);
        colores.addEventListener('input', generar);
        filas.addEventListener('input', total);
        document.querySelectorAll('.pf-pres button').forEach(function (b) {
            b.addEventListener('click', function () { tallas.value = b.dataset.t; generar(); });
        });
        document.getElementById('aplicarTodas').addEventListener('click', function () {
            var v = document.getElementById('todas').value;
            filas.querySelectorAll('input[type=number]').forEach(function (x) { x.value = v; });
            total();
        });
        var sw = document.getElementById('conTallas');
        function modo() {
            document.getElementById('bloqueTallas').style.display = sw.checked ? '' : 'none';
            document.getElementById('bloqueUnica').style.display = sw.checked ? 'none' : '';
            document.getElementById('conTallasVal').value = sw.checked ? '1' : '0';
            // Sin tallas no se envían las filas
            filas.querySelectorAll('input').forEach(function (x) { x.disabled = !sw.checked; });
            total();
        }
        sw.addEventListener('change', modo);
        generar(); modo();
    }

    // Códigos de barras: el lector de códigos escribe el código y "Enter"; aquí el Enter no guarda el formulario,
    // deja un espacio para poder escanear otra etiqueta en la misma talla/color
    f.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' || !e.target.matches('[data-barras]')) return;
        e.preventDefault();
        var x = e.target; if (x.value.trim() && !/\s$/.test(x.value)) x.value += ' ';
    });
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-barras-esc]'); if (!b || !window.LPEscaner) return;
        var inp = document.getElementById(b.dataset.barrasEsc); if (!inp) return;
        LPEscaner.abrir({ titulo: 'Escanee la etiqueta de esta prenda', continuo: true, alLeer: function (c) {
            c = String(c || '').trim().toUpperCase(); if (!c) return;
            var l = inp.value.trim() ? inp.value.trim().split(/[\s,;]+/) : [];
            if (l.indexOf(c) === -1) l.push(c);
            inp.value = l.join(' '); cambios = true;
            var est = document.getElementById('lpEscEstado'); if (est) { est.textContent = '✓ ' + c + ' (puede escanear otra o cerrar)'; est.className = 'estado ok'; }
        } });
    });

    // Avisa antes de salir si hay cambios sin guardar
    var cambios = false;
    f.addEventListener('input', function () { cambios = true; });
    f.addEventListener('submit', function () { cambios = false; });
    window.addEventListener('beforeunload', function (e) { if (cambios) { e.preventDefault(); e.returnValue = ''; } });
})();
</script>
@endpush
@push('js')
<script>
(function () {
    var u = document.getElementById('unidad'); if (!u) return;
    var U = @json(\App\Models\Producto::UNIDADES);
    function pintar() {
        var x = U[u.value] || U.und;
        document.getElementById('unTxt').textContent = x[0].toLowerCase();
        var c = document.getElementById('cantUn'); if (c) c.textContent = u.value === 'und' ? '' : '(en ' + x[2] + ')';
    }
    u.addEventListener('change', pintar); pintar();
})();
</script>
@endpush
