@extends('layouts.app')

@section('title', 'Importaciones')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Administrador LocalPro</small><h1>Cargar <span>productos</span></h1>
            <span class="text-muted fw-semibold">Negocios que mandaron su archivo para que el equipo les cargue los productos</span></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if (session('importacion_errores'))
    <div class="alert border-0" style="background:#fff8ec;color:#7a4b00"><b>{{ count(session('importacion_errores')) }} fila(s) no se cargaron:</b>
        <ul class="mb-0 mt-1" style="max-height:200px;overflow:auto">@foreach (session('importacion_errores') as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="lp-box">
        <h3><span><i class="fa-solid fa-file-import me-1"></i> Solicitudes {{ $pendientes ? '(' . $pendientes . ' pendientes)' : '' }}</span>
            <form method="get" class="d-flex gap-2 flex-wrap">
                <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:150px">
                    @foreach (['pendiente' => 'Pendientes', 'lista' => 'Listas', 'rechazada' => 'Rechazadas', 'todas' => 'Todas'] as $k => $v)<option value="{{ $k }}" @selected($estado === $k)>{{ $v }}</option>@endforeach
                </select>
                <select name="tipo" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:190px">
                    @foreach (['solicitud' => 'Enviadas al equipo', 'propia' => 'Hechas por el negocio', 'todas' => 'Todas'] as $k => $v)<option value="{{ $k }}" @selected($tipo === $k)>{{ $v }}</option>@endforeach
                </select>
            </form>
        </h3>
        @if ($lista->isEmpty())
            <p class="lp-empty mb-0">No hay solicitudes {{ $estado === 'pendiente' ? 'pendientes' : '' }}. 🎉</p>
        @else
        <ul class="lp-list">
            @foreach ($lista as $i)
            @php $tel = preg_replace('/\D/', '', (string) ($i->telefono ?: $i->tienda?->telefono)); if ($tel && strlen($tel) === 10) $tel = '57' . $tel; @endphp
            <li style="flex-wrap:wrap;align-items:flex-start">
                <div class="av"><i class="fa-solid {{ $i->tipo === 'solicitud' ? 'fa-headset' : 'fa-file-excel' }}"></i></div>
                <div class="t" style="min-width:220px">
                    <b><a href="{{ route('admin.tiendas.show', $i->tienda_id) }}" class="text-decoration-none" style="color:inherit">{{ $i->tienda->nombre ?? 'Tienda' }}</a> · {{ $i->nombre_original }}</b>
                    <small>{{ $i->created_at->locale('es')->diffForHumans() }} · {{ $i->user->name ?? '' }}{{ $i->telefono ? ' · ' . $i->telefono : '' }}</small>
                    @if ($i->nota)<small class="d-block" style="color:#33403a">📝 {{ $i->nota }}</small>@endif
                    @if ($i->resultado)<small class="d-block" style="color:#11702f;white-space:pre-line">✅ {{ \Illuminate\Support\Str::limit($i->resultado, 300) }}</small>@endif
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('admin.importaciones.archivo', $i) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-download me-1"></i>Archivo</a>
                    @if ($i->estado !== 'lista')
                    <form method="post" action="{{ route('admin.importaciones.atender', $i) }}">@csrf<button class="btn btn-sm btn-primary"><i class="fa-solid fa-file-import me-1"></i>Importar en su tienda</button></form>
                    <form method="post" action="{{ route('admin.importaciones.estado', $i) }}">@csrf<input type="hidden" name="estado" value="lista"><button class="btn btn-sm btn-outline-success">Marcar lista</button></form>
                    @else
                    <form method="post" action="{{ route('admin.importaciones.estado', $i) }}">@csrf<input type="hidden" name="estado" value="pendiente"><button class="btn btn-sm btn-outline-secondary">Reabrir</button></form>
                    @endif
                    @if ($tel)
                    <a target="_blank" class="btn btn-sm btn-success" href="https://wa.me/{{ $tel }}?text={{ rawurlencode($i->estado === 'lista' ? '¡Hola! Ya cargamos sus productos en LocalPro (' . ($i->tienda->nombre ?? '') . '). Ya puede empezar a vender 🙌' : 'Hola, somos LocalPro. Recibimos su archivo de productos y estamos cargándolo.') }}"><i class="fa-brands fa-whatsapp me-1"></i>Avisar</a>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
        <div class="mt-3">{{ $lista->links() }}</div>
        @endif
    </div>
</div>
@endsection
