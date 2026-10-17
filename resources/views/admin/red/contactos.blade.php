@extends('layouts.app')

@section('title', 'Contactos para asesores')

@section('content')
@php $n = fn ($v) => number_format((int) $v, 0, ',', '.'); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-crown me-1"></i>Red comercial</small><h1>Contactos para <span>asesores</span></h1>
            <span class="text-muted fw-semibold">Suba aquí la base de datos para todos los asesores. Cada día a cada asesor le salen {{ $diarios }} contactos y <b>a cada persona se le escribe una sola vez</b>: los que ya se contactaron no se le muestran a ningún otro asesor.</span></div>
        <div class="lp-quick"><a href="{{ route('admin.red.index') }}" class="btn btn-light btn-lg"><i class="fa-solid fa-arrow-left me-1"></i>Red comercial</a></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-brands fa-whatsapp"></i></div><div class="lbl">Ya contactados</div><div class="val">{{ $n($kpi['contactados']) }}</div><div class="sub">{{ $n($kpi['hoy']) }} hoy</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-address-book"></i></div><div class="lbl">Por contactar</div><div class="val">{{ $n($kpi['por']) }}</div><div class="sub">de {{ $n($kpi['total']) }} en la base</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-user-tie"></i></div><div class="lbl">Asesores escribiendo</div><div class="val">{{ $porAsesor->where('hoy', '>', 0)->count() }}</div><div class="sub">hoy</div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="lp-box h-100">
                <h3><span><i class="fa-solid fa-upload me-1"></i> Subir contactos</span></h3>
                <form method="post" action="{{ route('admin.red.contactos.subir') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="archivo" class="form-control mb-2" accept=".xlsx,.csv,.vcf,.vcard" required>
                    <small class="text-muted d-block mb-2">Excel (.xlsx), .csv o contactos del celular (.vcf). LocalPro busca solo el nombre y el celular. Los números que ya están en la base no se repiten.</small>
                    <button class="btn btn-primary rounded-pill fw-bold"><i class="fa-solid fa-upload me-1"></i>Subir a la base</button>
                </form>
                <form method="post" action="{{ route('admin.red.contactos.ajustes') }}" class="d-flex gap-2 align-items-end mt-3 flex-wrap">
                    @csrf
                    <div><label class="form-label small fw-bold mb-1">Contactos al día por asesor</label>
                        <input type="number" name="diarios" min="1" max="200" value="{{ $diarios }}" class="form-control" style="width:120px"></div>
                    <button class="btn btn-dark rounded-pill fw-bold">Guardar</button>
                </form>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="lp-box h-100">
                <h3><span><i class="fa-solid fa-ranking-star me-1"></i> Quién ha escrito</span></h3>
                @forelse ($porAsesor as $p)
                <div class="d-flex justify-content-between py-1 border-bottom small">
                    <span class="fw-bold">{{ $p->contactadoPor->nombre ?? 'Asesor' }} <span class="text-muted">{{ $p->contactadoPor->codigo ?? '' }}</span></span>
                    <span><b>{{ (int) $p->hoy }}</b> hoy · {{ $n($p->n) }} en total</span>
                </div>
                @empty
                <p class="text-muted mb-0">Todavía ningún asesor ha escrito.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="lp-box">
        <form method="get" class="d-flex gap-2 flex-wrap align-items-center mb-3">
            @foreach (['contactados' => 'Ya contactados', 'por_contactar' => 'Por contactar', 'hoy' => 'Repartidos hoy', 'todos' => 'Todos'] as $k => $t)
            <a href="{{ route('admin.red.contactos', ['ver' => $k, 'q' => $q ?: null]) }}" class="btn btn-sm rounded-pill fw-bold {{ $ver === $k ? 'btn-dark' : 'btn-light' }}">{{ $t }}</a>
            @endforeach
            <input type="hidden" name="ver" value="{{ $ver }}">
            <input type="search" name="q" value="{{ $q }}" class="form-control form-control-sm ms-auto" style="max-width:240px" placeholder="Buscar nombre o celular">
        </form>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Contacto</th><th>Estado</th><th>Asesor</th><th></th></tr></thead>
                <tbody>
                @forelse ($lista as $c)
                <tr>
                    <td><b>{{ $c->nombre }}</b><div class="text-muted small">{{ $c->telefono }}</div></td>
                    <td>
                        @if ($c->contactado_at)
                        <span class="badge rounded-pill {{ $c->estado === 'enviado' ? 'text-bg-success' : 'text-bg-warning' }}">{{ \App\Models\RedContacto::ESTADOS[$c->estado] ?? 'Contactado ✓' }}</span>
                        <div class="text-muted small">{{ $c->contactado_at->format('d/m/Y g:i a') }}</div>
                        @elseif ($c->asignado_fecha && $c->asignado_fecha->isToday())
                        <span class="badge rounded-pill text-bg-info">Repartido hoy</span>
                        @else
                        <span class="badge rounded-pill text-bg-light border">Por contactar</span>
                        @endif
                    </td>
                    <td class="small">
                        @if ($c->contactadoPor){{ $c->contactadoPor->nombre }} <span class="text-muted">{{ $c->contactadoPor->codigo }}</span>
                        @elseif ($c->asignadoA && $c->asignado_fecha?->isToday()){{ $c->asignadoA->nombre }} <span class="text-muted">(le salió hoy)</span>
                        @else <span class="text-muted">—</span>@endif
                    </td>
                    <td class="text-end">
                        @if ($c->contactado_at)
                        <form method="post" action="{{ route('admin.red.contactos.liberar', $c) }}" data-confirm="¿Quitar la marca de {{ $c->nombre }}?" data-confirm-texto="Volverá a la base y le puede salir a otro asesor." data-confirm-ok="Sí, quitar marca">@csrf
                            <button class="btn btn-sm btn-light rounded-pill">↩ Quitar marca</button></form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No hay contactos en esta lista.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $lista->links() }}
    </div>
</div>
@endsection
