@extends('layouts.app')

@section('title', 'Inventario')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small>Inventario</small><h1>Su <span>mercancía</span> al día</h1></div>
        <div class="lp-quick">
            <a href="{{ route('inventario.movimientos') }}" class="btn btn-dark"><i class="fa-solid fa-clock-rotate-left me-1"></i>Movimientos</a>
            @can('crear-producto')<a href="{{ route('productos.create') }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i>Nuevo producto</a>@endcan
        </div>
    </div>

    <div class="lp-counts mb-3" style="grid-template-columns:repeat(4,1fr)">
        <a class="lp-count" href="{{ route('inventario.index') }}"><div class="ci"><i class="fa-solid {{ \App\Models\Tienda::iconoProducto() }}"></i></div><b>{{ dinero($resumen['unidades'], false) }}</b><small>Unidades</small></a>
        <a class="lp-count" href="{{ route('inventario.index') }}"><div class="ci"><i class="fa-solid fa-layer-group"></i></div><b>{{ $resumen['referencias'] }}</b><small>Tallas/colores</small></a>
        <a class="lp-count" href="{{ route('inventario.index', ['filtro' => 'bajo']) }}"><div class="ci"><i class="fa-solid fa-arrow-trend-down"></i></div><b>{{ $resumen['bajo'] }}</b><small>Stock bajo (1–5)</small></a>
        <a class="lp-count" href="{{ route('inventario.index', ['filtro' => 'agotado']) }}"><div class="ci" style="background:#ff6fb5"><i class="fa-solid fa-ban"></i></div><b>{{ $resumen['agotado'] }}</b><small>Agotados</small></a>
    </div>

    <div class="lp-box">
        <form class="row g-2 mb-3" method="get">
            <div class="col-md-6"><div class="input-group"><input name="q" id="invQ" value="{{ $q }}" class="form-control" placeholder="Buscar {{ \App\Models\Tienda::palabraProducto() }}, código{{ \App\Support\TiendaActual::tienda()?->usa_tallas ? ', talla o color' : '' }}..."><button type="button" class="btn btn-dark" data-escanear="invQ" data-enviar data-titulo="Escanee la etiqueta de la prenda" title="Escanear con la cámara"><i class="fa-solid fa-camera me-1"></i>Escanear</button></div>
                <small class="text-muted">📷 Puede escanear el código de la prenda con la cámara del celular.</small>
                <script src="{{ asset('js/escaner.js') }}?v=1"></script></div>
            <div class="col-md-3">
                <select name="filtro" class="form-select" onchange="this.form.submit()">
                    <option value="todos" @selected($filtro === 'todos')>Todos</option>
                    <option value="bajo" @selected($filtro === 'bajo')>Stock bajo</option>
                    <option value="agotado" @selected($filtro === 'agotado')>Agotados</option>
                </select>
            </div>
            <div class="col-md-3 d-grid"><button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass me-1"></i>Buscar</button></div>
        </form>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Prenda</th><th>Talla</th><th>Color</th><th>Precio</th><th>Stock</th><th></th></tr></thead>
                <tbody>
                @forelse ($variantes as $v)
                    <tr>
                        <td><b>{{ $v->producto->nombre }}</b><br><small class="text-muted">{{ $v->producto->codigo }}</small></td>
                        <td class="fw-bold">{{ $v->talla ?? '—' }}</td>
                        <td>{{ $v->color ?? '—' }}</td>
                        <td>@pesos($v->precio ?? $v->producto->precio_venta)</td>
                        <td><span class="badge {{ $v->stock <= 0 ? 'bg-danger' : ($v->stock <= 5 ? 'bg-warning text-dark' : 'bg-success') }} fs-6">{{ cantidad($v->stock) }}</span></td>
                        <td class="text-end"><a href="{{ route('productos.variantes', $v->producto_id) }}" class="btn btn-sm btn-dark">Gestionar</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="lp-empty">No hay prendas con ese filtro. Cree productos y agrégueles tallas y colores.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $variantes->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
