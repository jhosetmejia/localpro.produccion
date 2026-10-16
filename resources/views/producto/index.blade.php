@extends('layouts.app')

@section('title','Productos')

@push('css')
<style>
.pl-top{display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;margin:1.2rem 0 1rem}
.pl-top h1{font-weight:900;font-size:1.6rem;margin:0;flex:1 1 auto}
.pl-nuevo{background:linear-gradient(90deg,#a3f04a,#16b84e);color:#0b0d0c;font-weight:800;border:0;border-radius:999px;padding:.7rem 1.2rem;text-decoration:none;white-space:nowrap}
.pl-nuevo:hover{color:#0b0d0c;filter:brightness(1.05)}
.pl-imp{background:#fff;color:#0b0d0c;font-weight:800;border:1.5px solid #dfe6e2;border-radius:999px;padding:.65rem 1.1rem;text-decoration:none;white-space:nowrap}
.pl-imp i{color:#16b34a}.pl-imp:hover{border-color:#16b34a;color:#0b0d0c}
@media (max-width:420px){.pl-top .pl-imp,.pl-top .pl-nuevo{flex:1 1 auto;text-align:center}}
.pl-buscar{position:relative;margin-bottom:.7rem}
.pl-buscar input{border-radius:16px;padding:.8rem 1rem .8rem 2.6rem;font-size:1rem}
.pl-buscar > i,.pl-buscar > svg{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#7a8580;pointer-events:none}
.pl-filtros{display:flex;gap:.4rem;overflow-x:auto;padding-bottom:.4rem;margin-bottom:.6rem}
.pl-filtros button{border:1.5px solid #dfe6e2;background:#fff;border-radius:999px;padding:.4rem .9rem;font-weight:700;font-size:.85rem;white-space:nowrap;color:#33403a}
.pl-filtros button.on{background:#0b0d0c;border-color:#0b0d0c;color:#fff}
.pl-lista{display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:.8rem}
.pl-item{background:#fff;border-radius:20px;padding:.9rem;box-shadow:0 6px 22px -14px rgba(0,0,0,.35);border:1px solid #edf1ee;display:flex;flex-direction:column;gap:.7rem;min-width:0}
.pl-item.off{opacity:.6}
.pl-cab{display:flex;gap:.8rem;align-items:center;min-width:0}
.pl-foto{width:64px;height:64px;border-radius:14px;background:#eef3ef;flex:none;overflow:hidden;display:grid;place-items:center;font-weight:900;font-size:1.4rem;color:#16b84e}
.pl-foto img{width:100%;height:100%;object-fit:contain;background:#fff}
.pl-txt{min-width:0;flex:1}
.pl-web{color:inherit;text-decoration:none}.pl-web:hover b{color:#16b34a}.pl-web i{font-size:.65em;opacity:.45;vertical-align:middle}
.pl-txt b{display:block;font-size:1rem;line-height:1.2;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical}
.pl-txt small{color:#6b7570;display:block;margin-top:.15rem}
.pl-precio{font-weight:900;font-size:1.05rem;white-space:nowrap}
.pl-stk{display:inline-block;border-radius:999px;padding:.15rem .6rem;font-weight:800;font-size:.78rem;margin-top:.3rem}
.pl-stk.ok{background:#dcfce7;color:#14753a}.pl-stk.low{background:#fef3c7;color:#92600a}.pl-stk.zero{background:#fde2e8;color:#b0173f}.pl-stk.na{background:#eef1ef;color:#55605a}
.pl-tallas{display:flex;gap:.3rem;flex-wrap:wrap}
.pl-tallas span{background:#f3f6f4;border-radius:8px;padding:.15rem .45rem;font-size:.75rem;font-weight:700;color:#33403a}
.pl-tallas span.cero{color:#b3bab6;text-decoration:line-through}
.pl-acc{display:grid;grid-template-columns:repeat(4,1fr);gap:.4rem}
.pl-acc a,.pl-acc button{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.2rem;border:0;border-radius:14px;padding:.55rem .2rem;font-size:.75rem;font-weight:800;text-decoration:none;background:#f1f5f2;color:#0b0d0c;min-height:58px}
.pl-acc a:hover,.pl-acc button:hover{background:#e3ebe6;color:#0b0d0c}
.pl-acc .pri{background:#0b0d0c;color:#fff}
.pl-acc .pri:hover{background:#222;color:#fff}
.pl-acc .del{color:#b0173f}
.pl-acc i,.pl-acc svg{font-size:1.05rem}
.pl-vacio{text-align:center;padding:2.5rem 1rem;color:#6b7570}
.pl-vacio i,.pl-vacio svg{font-size:2.4rem;margin-bottom:.6rem;color:#16b84e}
@media (max-width:420px){.pl-lista{grid-template-columns:minmax(0,1fr)}.pl-top h1{font-size:1.35rem}}
</style>
@endpush

@section('content')

@include('layouts.partials.alert')

@php
    $tallas = (bool) \App\Support\TiendaActual::tienda()?->usa_tallas;
    // Tocar el nombre abre el producto en la tienda en línea (en el navegador, con www, no dentro de la app)
    $tiendaCat = \App\Support\TiendaActual::tienda();
    $verWeb = $tiendaCat && $tiendaCat->catalogo_activo && $tiendaCat->slug;
    $u = $verWeb ? parse_url($tiendaCat->url_catalogo_navegador) : null;
    $baseWeb = $u ? ($u['scheme'] ?? 'https') . '://' . $u['host'] . (isset($u['port']) ? ':' . $u['port'] : '') : '';
    $activos = $productos->where('estado', 1)->count();
    $ocultos = $productos->count() - $activos;
@endphp

<div class="container-fluid px-3 px-md-4 pb-5">
    <div class="pt-3">@include('layouts.partials.limite-plan', ['que' => 'productos'])</div>
    <div class="pl-top">
        <h1>Productos <small class="text-muted fw-bold" style="font-size:.9rem">({{ $activos }})</small></h1>
        @can('crear-producto')
        <a href="{{ route('productos.importar') }}" class="pl-imp"><i class="fa-solid fa-file-excel"></i> Importar Excel</a>
        <a href="{{ route('productos.create') }}" class="pl-nuevo"><i class="fa-solid fa-plus"></i> Nuevo producto</a>
        @endcan
    </div>

    @if ($productos->isEmpty())
        <div class="pl-item pl-vacio">
            <div><i class="fa-solid {{ \App\Models\Tienda::iconoProducto() }}"></i></div>
            <b>Aún no tiene productos.</b>
            <span>Cree el primero y quedará listo para vender en la caja.</span>
            @can('crear-producto')
            <div class="d-flex gap-2 justify-content-center flex-wrap mt-2"><a href="{{ route('productos.create') }}" class="pl-nuevo d-inline-block">+ Crear mi primer producto</a><a href="{{ route('productos.importar') }}" class="pl-imp d-inline-block"><i class="fa-solid fa-file-excel"></i> Ya los tengo en Excel</a></div>
            <small class="d-block mt-2">¿Tiene cientos de productos? Impórtelos de una vez o envíenos el archivo y se los cargamos.</small>
            @endcan
        </div>
    @else
        <div class="pl-buscar">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" id="plQ" class="form-control" placeholder="Buscar por nombre, código o categoría" autocomplete="off">
        </div>
        <div class="pl-filtros" id="plF">
            <button type="button" class="on" data-f="activos">Activos ({{ $activos }})</button>
            <button type="button" data-f="bajo">Poco stock</button>
            <button type="button" data-f="agotados">Agotados</button>
            @if ($ocultos)
            <button type="button" data-f="ocultos">Ocultos ({{ $ocultos }})</button>
            @endif
        </div>

        <div class="pl-lista" id="plL">
            @foreach ($productos as $item)
            @php
                $controla = (bool) $item->controla_inventario;
                $st = round((float) $item->variantes->sum('stock'), 3);
                $clase = !$controla ? 'na' : ($st <= 0 ? 'zero' : ($st <= 5 ? 'low' : 'ok'));
                $cats = $item->categorias->map(fn ($c) => $c->caracteristica?->nombre)->filter()->implode(', ');
                $conVar = $item->variantes->filter(fn ($v) => $v->talla || $v->color);
            @endphp
            <div class="pl-item {{ $item->estado == 1 ? '' : 'off' }}"
                 data-q="{{ mb_strtolower($item->nombre . ' ' . $item->codigo . ' ' . $cats) }}"
                 data-estado="{{ $item->estado == 1 ? 1 : 0 }}" data-st="{{ $controla ? $st : -1 }}">
                <div class="pl-cab">
                    <div class="pl-foto">
                        @if ($item->img_path)
                        <img src="{{ asset('storage/productos/' . rawurlencode($item->img_path)) }}" alt="" loading="lazy">
                        @else
                        {{ mb_strtoupper(mb_substr($item->nombre, 0, 1)) }}
                        @endif
                    </div>
                    <div class="pl-txt">
                        @if ($verWeb && $item->estado == 1)
                        <a class="pl-web" href="{{ $baseWeb ? $baseWeb . parse_url(\App\Http\Controllers\catalogoController::urlProducto($tiendaCat, $item), PHP_URL_PATH) : \App\Http\Controllers\catalogoController::urlProducto($tiendaCat, $item) }}" target="_blank" rel="noopener" title="Ver en la tienda en línea"><b>{{ $item->nombre }} <i class="fa-solid fa-arrow-up-right-from-square"></i></b></a>
                        @else
                        <b>{{ $item->nombre }}</b>
                        @endif
                        <small>{{ $item->codigo }}{{ $cats ? ' · ' . $cats : '' }}</small>
                        <span class="pl-stk {{ $clase }}">
                            @if ($item->estado != 1) Oculto
                            @elseif (!$controla) Sin control de stock
                            @elseif ($st <= 0) Agotado
                            @elseif ($item->es_fraccion) {{ cantidad($st, $item->unidad) }} disponibles
                            @else {{ cantidad($st) }} {{ $st == 1 ? 'unidad' : 'unidades' }}
                            @endif
                        </span>
                    </div>
                    <div class="pl-precio">@if ($item->descuento)<span style="display:block;font-size:.72rem;font-weight:700;color:#8a948e;text-decoration:line-through">{{ dinero($item->precio_antes) }}</span><span style="display:inline-block;background:#e11d48;color:#fff;border-radius:999px;padding:0 .45rem;font-size:.7rem;font-weight:900;margin-right:4px">-{{ $item->descuento }}%</span>@endif{{ dinero($item->precio_venta) }}@if ($item->es_fraccion)<small style="font-weight:700;color:#6b7570">/{{ $item->unidad_corta }}</small>@endif</div>
                </div>

                @if ($conVar->isNotEmpty())
                <div class="pl-tallas">
                    @foreach ($conVar->take(12) as $v)
                    <span class="{{ $controla && $v->stock <= 0 ? 'cero' : '' }}">{{ trim($v->talla . ' ' . $v->color) }}@if ($controla) · {{ cantidad($v->stock) }}@endif</span>
                    @endforeach
                    @if ($conVar->count() > 12)<span>+{{ $conVar->count() - 12 }}</span>@endif
                </div>
                @endif

                <div class="pl-acc">
                    @can('editar-producto')
                    <a href="{{ route('productos.variantes', ['producto' => $item]) }}" class="pri"><i class="fa-solid fa-boxes-stacked"></i>{{ $tallas ? 'Tallas y stock' : 'Stock' }}</a>
                    <a href="{{ route('productos.edit', ['producto' => $item]) }}"><i class="fa-solid fa-pen"></i>Editar</a>
                    @endcan
                    <a href="{{ route('etiquetas.producto', ['producto' => $item]) }}"><i class="fa-solid fa-barcode"></i>Etiquetas</a>
                    @can('eliminar-producto')
                    <form action="{{ route('productos.destroy', ['producto' => $item->id]) }}" method="post" class="d-contents" style="display:contents"
                          data-confirm="{{ $item->estado == 1 ? '¿Ocultar este producto?' : '¿Restaurar este producto?' }}" data-confirm-texto="{{ $item->estado == 1 ? 'Ya no saldrá en la caja ni en el catálogo. Puede restaurarlo después.' : 'Volverá a salir en la caja y en el catálogo.' }}" data-confirm-ok="{{ $item->estado == 1 ? 'Sí, ocultar' : 'Sí, restaurar' }}" @if($item->estado == 1) data-peligro @endif>
                        @method('DELETE')
                        @csrf
                        @if ($item->estado == 1)
                        <button type="submit" class="del"><i class="fa-solid fa-eye-slash"></i>Ocultar</button>
                        @else
                        <button type="submit"><i class="fa-solid fa-rotate-left"></i>Restaurar</button>
                        @endif
                    </form>
                    @endcan
                </div>
            </div>
            @endforeach
        </div>
        <div class="pl-item pl-vacio mt-2" id="plNada" style="display:none"><b>No hay productos con ese filtro.</b></div>
    @endif
</div>
@endsection

@push('js')
<script>
(function () {
    var q = document.getElementById('plQ'); if (!q) return;
    var filtro = 'activos';
    var items = [].slice.call(document.querySelectorAll('#plL .pl-item'));
    function aplicar() {
        var t = q.value.trim().toLowerCase(), n = 0;
        items.forEach(function (el) {
            var e = el.dataset.estado === '1', st = +el.dataset.st, ok;
            if (filtro === 'ocultos') ok = !e;
            else if (filtro === 'bajo') ok = e && st > 0 && st <= 5;
            else if (filtro === 'agotados') ok = e && st === 0;
            else ok = e;
            if (ok && t) ok = el.dataset.q.indexOf(t) !== -1;
            el.style.display = ok ? '' : 'none'; if (ok) n++;
        });
        document.getElementById('plNada').style.display = n ? 'none' : '';
    }
    q.addEventListener('input', aplicar);
    document.getElementById('plF').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        [].forEach.call(this.children, function (x) { x.classList.remove('on'); });
        b.classList.add('on'); filtro = b.dataset.f; aplicar();
    });
    aplicar();
})();
</script>
@endpush
