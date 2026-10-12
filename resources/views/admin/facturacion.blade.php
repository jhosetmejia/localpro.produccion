@extends('layouts.app')

@section('title', 'Facturación DIAN')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Facturación <span>DIAN</span></h1>
            <span class="text-muted fw-semibold">Proveedor actual: <b>{{ $nombreProveedor }}</b> · {{ $cola }} documentos en cola</span></div>
        <form method="post" action="{{ route('admin.facturacion.cola') }}">@csrf<button class="btn btn-dark btn-lg"><i class="fa-solid fa-paper-plane me-1"></i>Enviar cola ahora</button></form>
    </div>
    @include('layouts.partials.lp-flash')
    <div class="lp-grid">
        <form method="post" action="{{ route('admin.facturacion.guardar') }}" class="lp-box">
            @csrf
            <h3><i class="fa-solid fa-plug me-1"></i> Proveedor tecnológico</h3>
            <p class="text-muted small">LocalPro arma, valida y numera cada documento. El proveedor autorizado por la DIAN lo firma, lo envía y devuelve el CUFE. Mientras se contrata, use el <b>modo práctica</b> para probar todo el flujo sin enviar nada.</p>
            <label class="form-label">Modo</label>
            <select name="fe_proveedor" class="form-select mb-2">
                <option value="" @selected(!$proveedor)>Sin proveedor (los documentos quedan en cola)</option>
                <option value="simulado" @selected($proveedor === 'simulado')>Modo práctica (valida y calcula CUFE, no envía)</option>
                <option value="api" @selected($proveedor === 'api')>Proveedor conectado por API</option>
            </select>
            <label class="form-label">Nombre del proveedor</label>
            <input name="fe_nombre" class="form-control mb-2" maxlength="60" value="{{ old('fe_nombre', $nombre) }}" placeholder="Ej: el proveedor que se contrate">
            <label class="form-label">Dirección de la API</label>
            <input name="fe_url" class="form-control mb-2" maxlength="200" value="{{ old('fe_url', $url) }}" placeholder="https://api.proveedor.com/v1">
            <label class="form-label">Token</label>
            <input name="fe_token" type="password" class="form-control mb-3" autocomplete="new-password" placeholder="{{ $tieneToken ? 'Guardado · escriba solo para cambiarlo' : 'El que entrega el proveedor' }}">
            <button class="btn btn-primary w-100"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
        </form>
        <div class="lp-box">
            <h3><i class="fa-solid fa-store me-1"></i> Tiendas</h3>
            <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>Tienda</th><th>Listo</th><th>POS-e</th><th>FE</th><th>Ambiente</th><th>Documentos</th></tr></thead>
                <tbody>
                @foreach ($tiendas as $t)
                    @php $pr = \App\Support\TiendaActual::como($t->id, fn () => \App\Support\Dian\Facturador::preparacion($t)); @endphp
                    <tr>
                        <td><b>{{ $t->nombre }}</b><small class="d-block text-muted">{{ $t->codigo }} · {{ $pr['obligado'] ? 'Obligado' : 'No obligado' }}</small></td>
                        <td>{{ $pr['pct'] }}%</td>
                        <td>{!! $t->pos_electronico ? '<i class="fa-solid fa-check text-success"></i>' : '—' !!}</td>
                        <td>{!! $t->fe_activa ? '<i class="fa-solid fa-check text-success"></i>' : '—' !!}</td>
                        <td>{{ $t->fe_ambiente === 'produccion' ? 'Producción' : 'Pruebas' }}</td>
                        <td class="small">@foreach ($docs[$t->id] ?? [] as $d){{ \App\Models\DocumentoElectronico::ESTADOS[$d->estado][0] ?? $d->estado }}: {{ $d->n }}<br>@endforeach</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
@endsection
