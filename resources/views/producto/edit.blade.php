@extends('layouts.app')

@section('title','Editar producto')

@push('css')
<style>
    .pe-sec{background:#fff;border:1.5px solid var(--lp-line);border-radius:20px;padding:1rem 1.1rem;margin-bottom:12px}
    .pe-sec h3{font-size:1rem;font-weight:800;margin:0 0 .7rem;display:flex;align-items:center;gap:.5rem;justify-content:space-between}
    .pe-sec h3 a{font-size:.8rem;font-weight:700}
    .pe-sec .form-label{margin-bottom:.25rem;font-size:.85rem}
    .pe-foto{display:flex;gap:12px;align-items:center}
    .pe-foto .ph{width:84px;height:84px;border-radius:14px;background:#fff;border:1.5px solid var(--lp-line);display:grid;place-items:center;overflow:hidden;flex:none;color:#bfeccb;font-size:1.6rem}
    .pe-foto .ph img{width:100%;height:100%;object-fit:contain}
    .pe-stock{border:1.5px solid var(--lp-line);border-radius:14px;overflow:auto;max-height:260px}
    .pe-fila{display:flex;align-items:center;gap:10px;padding:.4rem .7rem;border-bottom:1px solid var(--lp-line)}
    .pe-fila:last-child{border-bottom:0}
    .pe-fila b{flex:1;font-size:.9rem}
    .pe-fila input{width:90px;text-align:center;font-weight:800}
    .pe-fila input.cambio{border-color:#16b34a;background:#ecfbf0}
    .pf-tipo{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:.8rem}
    .pf-tipo button{border:2px solid var(--lp-line,#dfe6e2);background:#fff;border-radius:16px;padding:.6rem .8rem;text-align:left;display:flex;flex-direction:column;gap:2px}
    .pf-tipo button i{font-size:1.1rem;color:#16b84e}
    .pf-tipo button small{color:#6b7a72;font-size:.72rem}
    .pf-tipo button.on{border-color:#16b84e;background:#ecfbf0}
    .pe-tot{display:flex;justify-content:space-between;font-weight:800;font-size:.85rem;margin-top:.5rem;color:var(--lp-muted)}
    .pe-cgrid{display:flex;flex-wrap:wrap;gap:8px}
    .pe-c{width:84px;cursor:pointer;text-align:center}
    .pe-c .ph{width:84px;height:100px;border-radius:12px;border:2px dashed var(--lp-line);background:#fafdfb;display:grid;place-items:center;overflow:hidden;color:#9ab3a3}
    .pe-c .ph img{width:100%;height:100%;object-fit:contain;background:#fff}
    .pe-c:hover .ph{border-color:#16b34a}
    .pe-c .nm{display:flex;align-items:center;justify-content:center;gap:4px;font-size:.72rem;font-weight:800;margin-top:3px}
    .pe-c .nm i{width:10px;height:10px;border-radius:50%;border:1px solid rgba(0,0,0,.2);flex:none}
    .pe-guardar{position:sticky;bottom:10px;z-index:5;display:flex;gap:8px}
</style>
@endpush

@section('content')
@php
    $variantes = $producto->variantes()->where('estado', 1)->orderBy('talla')->orderBy('color')->get();
    $soloUna = $variantes->count() === 1 && !$variantes[0]->talla && !$variantes[0]->color;
    $puedeStock = auth()->user()?->can('ajustar-inventario');
@endphp
<div class="lp-dash" style="max-width:1200px;margin:0 auto">
    <div class="lp-hello">
        <div><small><a href="{{ route('productos.index') }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>Productos</a></small>@php $conServ = (bool) \App\Support\TiendaActual::tienda()?->mod_servicios; $esServ = $conServ && old('es_servicio', $producto->es_servicio ? '1' : '0') == '1'; @endphp
            <h1>Editar <span id="tTit">{{ $esServ ? 'servicio' : 'producto' }}</span></h1></div>
    </div>
    @include('layouts.partials.lp-flash')

    <form action="{{ route('productos.update', ['producto' => $producto]) }}" method="post" enctype="multipart/form-data" id="pe">
        @method('PATCH')
        @csrf
        <div class="row g-3">
            {{-- Izquierda: datos y precios --}}
            <div class="col-lg-7">
                <div class="pe-sec">
                    <h3>Datos y precio</h3>
                    @if ($conServ)
                    <input type="hidden" name="es_servicio" id="esServ" value="{{ $esServ ? '1' : '0' }}">
                    <div class="pf-tipo" id="pfTipo">
                        <button type="button" data-t="0" class="{{ $esServ ? '' : 'on' }}"><i class="fa-solid fa-box"></i><b>Producto</b><small>Lleva cantidad e inventario</small></button>
                        <button type="button" data-t="1" class="{{ $esServ ? 'on' : '' }}"><i class="fa-solid fa-hand-holding-heart"></i><b>Servicio</b><small>Siempre disponible, sin stock</small></button>
                    </div>
                    @endif
                    <div class="row g-2">
                        <div class="col-md-8">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" name="nombre" id="nombre" class="form-control fw-bold" value="{{ old('nombre', $producto->nombre) }}" maxlength="80" required>
                            @error('nombre')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-12 mt-0">@include('producto._seo')</div>
                        <div class="col-md-4">
                            <label for="codigo" class="form-label">Código</label>
                            <input type="text" name="codigo" id="codigo" class="form-control" value="{{ old('codigo', $producto->codigo) }}" maxlength="50">
                            @error('codigo')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="precio_venta" class="form-label">Precio de venta ({{ moneda()['moneda'] }})</label>
                            <input data-dinero type="number" min="0" step="{{ paso_dinero() }}" name="precio_venta" id="precio_venta" class="form-control fw-bold" value="{{ old('precio_venta', (float) $producto->precio_venta) }}" required>
                            @error('precio_venta')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="precio_costo" class="form-label">Precio de costo <span class="text-muted fw-normal">(lo que le costó)</span></label>
                            <input data-dinero type="number" min="0" step="{{ paso_dinero() }}" name="precio_costo" id="precio_costo" class="form-control" value="{{ old('precio_costo', (float) $producto->precio_costo ?: '') }}" placeholder="Para calcular su ganancia">
                            @error('precio_costo')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        @include('producto._promo', ['col' => 'col-12'])
                        @include('producto._impuesto', ['col' => 'col-md-6'])
                        @if (\App\Support\TiendaActual::tienda()?->mod_medida)
                        <div class="col-md-6">
                            <label for="unidad" class="form-label">Se vende por</label>
                            <select name="unidad" id="unidad" class="form-select">
                                @foreach (\App\Models\Producto::UNIDADES as $k => $u)<option value="{{ $k }}" @selected(old('unidad', $producto->unidad ?: 'und') === $k)>{{ $u[0] }}{{ $k !== 'und' ? ' (' . $u[1] . ')' : '' }}</option>@endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-12">
                            <label for="descripcion" class="form-label">Descripción <span class="text-muted fw-normal">(sale en el catálogo)</span></label>
                            <textarea name="descripcion" id="descripcion" rows="2" class="form-control" maxlength="255" style="resize:none" placeholder="Material, detalles…">{{ old('descripcion', $producto->descripcion) }}</textarea>
                            @error('descripcion')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>
                </div>

                <div class="pe-sec">
                    <h3>Categoría</h3>
                    <div class="row">
                        @include('producto._categorias', ['seleccionadas' => $producto->categorias->pluck('id')->all(), 'para' => $producto->para])
                    </div>
                </div>
            </div>

            {{-- Derecha: foto y cantidades --}}
            <div class="col-lg-5">
                <div class="pe-sec">
                    <h3>Foto</h3>
                    <div class="pe-foto">
                        <span class="ph" id="pePrev">@if ($producto->img_path)<img src="{{ asset('storage/productos/' . rawurlencode($producto->img_path)) }}" alt="">@else<i class="fa-regular fa-image"></i>@endif</span>
                        <div class="flex-grow-1" style="min-width:0">
                            <input type="file" name="img_path" id="img_path" class="form-control" accept="image/*" data-foto-lista>
                            <script src="{{ asset('js/foto-lista.js') }}?v=2"></script>
                            <small class="text-muted">{{ $producto->img_path ? 'Suba otra solo si la quiere cambiar.' : 'Así la ven sus clientes en el catálogo.' }}</small>
                            @error('img_path')<small class="text-danger d-block">{{ $message }}</small>@enderror
                        </div>
                    </div>
                    @unless ($producto->tiene_foto)
                    <div class="form-check mt-2 p-2 ps-4" style="background:#fff8e6;border-radius:12px" id="sinFotoBox">
                        <input class="form-check-input" type="checkbox" name="mostrar_sin_foto" value="1" id="mostrarSinFoto" @checked(old('mostrar_sin_foto', $producto->mostrar_sin_foto))>
                        <label class="form-check-label small fw-semibold" for="mostrarSinFoto"><i class="fa-solid fa-triangle-exclamation me-1" style="color:#c78a00"></i>Si no tiene foto, no se mostrará en el catálogo. <b>Mostrar aunque no tenga foto</b></label>
                    </div>
                    @endunless
                    @php
                        $coloresProd = $variantes->pluck('color')->filter()->unique()->values();
                        $fotosColor = $producto->fotos->keyBy('color');
                    @endphp
                    @if ($coloresProd->isNotEmpty())
                    <div class="pe-colores">
                        <div class="small fw-bold mt-3 mb-1">Foto de cada color <span class="text-muted fw-normal">(en la tienda, al tocar el color cambia la foto)</span></div>
                        <div class="pe-cgrid">
                            @foreach ($coloresProd as $i => $col)
                            @php $fc = $fotosColor->get($col); $hex = \App\Support\Colores::PALETA[$col] ?? '#e3ebe6'; @endphp
                            <label class="pe-c" for="fc{{ $i }}" title="Subir foto {{ ucfirst(mb_strtolower($col)) }}">
                                <span class="ph" id="fcPrev{{ $i }}">@if ($fc)<img src="{{ $fc->url }}" alt="">@else<i class="fa-solid fa-camera"></i>@endif</span>
                                <span class="nm"><i style="background:{{ $hex }}"></i>{{ ucfirst(mb_strtolower($col)) }}</span>
                                <input type="hidden" name="foto_color_nombre[{{ $i }}]" value="{{ $col }}">
                                <input type="file" name="foto_color[{{ $i }}]" id="fc{{ $i }}" accept="image/*" class="d-none" data-foto-lista data-prev="fcPrev{{ $i }}">
                            </label>
                            @endforeach
                        </div>
                        @if ($fotosColor->isNotEmpty())
                        <div class="small mt-1">
                            @foreach ($coloresProd as $i => $col)@if ($fotosColor->get($col))<label class="me-2 text-muted"><input type="checkbox" name="quitar_foto_color[{{ $i }}]" value="1"> Quitar {{ mb_strtolower($col) }}</label>@endif @endforeach
                        </div>
                        @endif
                        @error('foto_color.*')<small class="text-danger d-block">{{ $message }}</small>@enderror
                    </div>
                    @endif
                </div>

                @if ($conServ)
                <div class="pe-sec" id="secServ" @unless($esServ) style="display:none" @endunless>
                    <h3>Servicio</h3>
                    <p class="text-muted small mb-0"><i class="fa-solid fa-hand-holding-heart me-1" style="color:#16b84e"></i>Es un servicio: no se acaba ni lleva inventario. Siempre está disponible para vender en la caja, en pedidos y en su catálogo.</p>
                </div>
                @endif
                <div class="pe-sec" id="secCant" @if($esServ) style="display:none" @endif>
                    <h3>{{ $soloUna ? 'Cantidad disponible' : 'Cantidad por talla y color' }}
                        @can('editar-producto')<a href="{{ route('productos.variantes', $producto) }}"><i class="fa-solid fa-plus me-1"></i>{{ \App\Support\TiendaActual::tienda()?->usa_tallas ? 'Agregar tallas / colores' : 'Más opciones' }}</a>@endcan
                    </h3>
                    @if ($variantes->isEmpty())
                        <p class="text-muted small mb-0">Este producto aún no tiene cantidades. <a href="{{ route('productos.variantes', $producto) }}">Agréguelas aquí</a>.</p>
                    @elseif (!$producto->controla_inventario)
                        <p class="text-muted small mb-0">Este producto se vende sin límite de cantidad (no controla inventario).</p>
                    @else
                        <div class="pe-stock" id="peStock">
                            @foreach ($variantes as $v)
                            <div class="pe-fila">
                                <b>{{ $soloUna ? 'Unidades' : $v->etiqueta }}</b>
                                <input type="hidden" name="stock_antes[{{ $v->id }}]" value="{{ (float) $v->stock }}">
                                <input type="number" min="0" step="any" class="form-control form-control-sm" name="stock[{{ $v->id }}]" value="{{ old('stock.' . $v->id, (float) $v->stock) }}" data-antes="{{ (float) $v->stock }}" inputmode="numeric" @disabled(!$puedeStock)>
                            </div>
                            @endforeach
                        </div>
                        <div class="pe-tot"><span>{{ $puedeStock ? 'Escriba la cantidad que hay ahora.' : 'No tiene permiso para cambiar cantidades.' }}</span><span>Total: <span id="peTotal">{{ cantidad($variantes->sum('stock')) }}</span></span></div>
                    @endif
                    <input type="hidden" name="controla_inventario" value="0">
                    <div class="form-check form-switch mt-3 mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" name="controla_inventario" value="1" id="controla_inventario" @checked(old('controla_inventario', $producto->controla_inventario))>
                        <label class="form-check-label small" for="controla_inventario"><b>Controlar inventario</b> <span class="text-muted d-block">Apáguelo en comidas preparadas o servicios.</span></label>
                    </div>
                </div>
            </div>
        </div>

        <div class="pe-guardar">
            <button type="submit" class="btn btn-primary btn-lg flex-grow-1" style="padding:.85rem"><i class="fa-solid fa-check me-1"></i>Guardar cambios</button>
            <a href="{{ route('productos.index') }}" class="btn btn-light btn-lg" style="padding:.85rem 1.3rem">Cancelar</a>
        </div>
    </form>
</div>
@endsection

@push('js')
<script>
(function () {
    var f = document.getElementById('pe');
    // Vista previa de la foto nueva
    var foto = document.getElementById('img_path');
    foto.addEventListener('change', function () {
        var a = this.files[0]; if (!a) return;
        document.getElementById('pePrev').innerHTML = '<img src="' + URL.createObjectURL(a) + '" alt="">';
    });
    // Vista previa de las fotos por color
    document.querySelectorAll('input[data-prev]').forEach(function (i) {
        i.addEventListener('change', function () {
            var a = i.files[0]; if (!a) return;
            document.getElementById(i.dataset.prev).innerHTML = '<img src="' + URL.createObjectURL(a) + '" alt="">';
        });
    });
    // Cantidades: marca las que cambió y suma el total
    var st = document.getElementById('peStock');
    if (st) st.addEventListener('input', function () {
        var t = 0;
        st.querySelectorAll('input[type=number]').forEach(function (i) {
            t += parseFloat(i.value) || 0;
            i.classList.toggle('cambio', String(parseFloat(i.value) || 0) !== String(parseFloat(i.dataset.antes) || 0));
        });
        document.getElementById('peTotal').textContent = Math.round(t * 1000) / 1000;
    });
    // Avisa antes de salir si hay cambios sin guardar
    var cambios = false;
    f.addEventListener('input', function () { cambios = true; });
    f.addEventListener('change', function () { cambios = true; });
    f.addEventListener('submit', function () { cambios = false; });
    window.addEventListener('beforeunload', function (e) { if (cambios) { e.preventDefault(); e.returnValue = ''; } });
})();
</script>
@endpush

@push('js')
<script>
// Producto o servicio: el servicio no muestra cantidades
(function () {
    var box = document.getElementById('pfTipo'); if (!box) return;
    box.addEventListener('click', function (e) {
        var b = e.target.closest('[data-t]'); if (!b) return;
        var s = b.dataset.t === '1';
        document.getElementById('esServ').value = s ? '1' : '0';
        box.querySelectorAll('[data-t]').forEach(function (x) { x.classList.toggle('on', x === b); });
        document.getElementById('secCant').style.display = s ? 'none' : '';
        document.getElementById('secServ').style.display = s ? '' : 'none';
        document.getElementById('tTit').textContent = s ? 'servicio' : 'producto';
        if (!s) { var c = document.getElementById('controla_inventario'); if (c) c.checked = true; }
    });
})();
</script>
@endpush
