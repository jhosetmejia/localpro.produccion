@extends('layouts.app')

@section('title', 'Egresos')

@push('css')
<style>
.eg-aviso{display:flex;gap:.8rem;align-items:center;border-radius:18px;padding:.8rem 1rem;margin-bottom:1rem;font-weight:700}
.eg-aviso.v{background:#eef7ff;border:1.5px solid #cfe3fb;color:#0f4c81}
.eg-aviso.a{background:#f3faf5;border:1.5px solid #cfeedd;color:#11702f}
.eg-aviso i{font-size:1.3rem}
.eg-cats{display:flex;gap:.4rem;flex-wrap:wrap}
.eg-cats label{cursor:pointer}
.eg-cats input{display:none}
.eg-cats span{display:inline-flex;align-items:center;gap:.35rem;border:1.5px solid #e3ebe6;background:#fff;border-radius:999px;padding:.4rem .8rem;font-weight:700;font-size:.82rem}
.eg-cats input:checked+span{background:#0b0d0c;color:#a6f34a;border-color:#0b0d0c}
.eg-fil{display:flex;gap:.4rem;flex-wrap:wrap;align-items:end}
.eg-item{display:flex;gap:.8rem;align-items:center;padding:.7rem .2rem;border-bottom:1px solid #eef2ef}
.eg-item:last-child{border-bottom:0}
.eg-item .av{width:42px;height:42px;border-radius:14px;background:#fdeef4;color:#b0206a;display:grid;place-items:center;flex:none}
.eg-item .t{flex:1;min-width:0}
.eg-item .t b{display:block;line-height:1.2}
.eg-item .t small{color:#6b7570;font-weight:600}
.eg-item .m{font-weight:900;color:#b0206a;white-space:nowrap}
.eg-tag{display:inline-block;border-radius:99px;padding:.05rem .45rem;font-size:.68rem;font-weight:800;margin-left:.25rem}
.eg-tag.adm{background:#0b0d0c;color:#a6f34a}.eg-tag.caja{background:#fff5d6;color:#8a6400}
.eg-bar{height:7px;border-radius:99px;background:#eef2ef;overflow:hidden;margin-top:.25rem}
.eg-bar i{display:block;height:100%;background:linear-gradient(90deg,#ff8db5,#b0206a)}
</style>
@endpush

@section('content')
@php $f = fn ($v) => '$' . number_format((float) $v, 0, ',', '.'); @endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-arrow-trend-down me-1"></i>Dinero que sale</small><h1><span>Egresos</span></h1>
            <span class="text-muted fw-semibold">Anote cada pago o gasto del negocio para saber en qué se va la plata.</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    @if ($admin)
    <div class="eg-aviso a"><i class="fa-solid fa-user-shield"></i><span>Aquí puede ver y anotar <b>todos los egresos</b> del negocio: los suyos y los de sus vendedores. Los vendedores no ven los que usted anota.</span></div>
    @else
    <div class="eg-aviso v"><i class="fa-solid fa-circle-info"></i><span>Aquí puede ver <b>sus egresos y los de sus compañeros</b>. Lo que pague en efectivo con su caja abierta se descuenta del efectivo que debe entregar.</span></div>
    @endif

    <div class="lp-kpis">
        <div class="lp-kpi hero"><div class="ic"><i class="fa-solid fa-arrow-trend-down"></i></div><div class="lbl">{{ $admin ? 'Total de egresos' : 'Egresos del equipo' }}</div><div class="val">{{ $f($total) }}</div><div class="sub">Del {{ $desde->format('d/m') }} al {{ $hasta->format('d/m/Y') }}</div></div>
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-user"></i></div><div class="lbl">Anotados por usted</div><div class="val">{{ $f($mios) }}</div><div class="sub">En el mismo periodo</div></div>
        @if ($caja)
        <div class="lp-kpi"><div class="ic"><i class="fa-solid fa-vault"></i></div><div class="lbl">Pagado con su caja abierta</div><div class="val">{{ $f($caja->totalEgresos()) }}</div><div class="sub"><a href="{{ route('cajas.actual') }}">Ver mi caja</a></div></div>
        @endif
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <form method="post" action="{{ route('egresos.store') }}" enctype="multipart/form-data" class="lp-box">
                @csrf
                <h3><span><i class="fa-solid fa-plus me-1"></i> Anotar egreso</span></h3>
                <div class="mb-3">
                    <label class="form-label">¿En qué se gastó?</label>
                    <input name="concepto" class="form-control form-control-lg" value="{{ old('concepto') }}" maxlength="160" placeholder="Ej: Pago del internet, bolsas, domicilio…" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-7"><label class="form-label">Valor</label><input data-dinero type="number" min="1" step="any" name="valor" class="form-control form-control-lg" value="{{ old('valor') }}" required></div>
                    <div class="col-5"><label class="form-label">Fecha</label><input type="date" name="fecha" class="form-control form-control-lg" value="{{ old('fecha', today()->format('Y-m-d')) }}" max="{{ today()->format('Y-m-d') }}"></div>
                </div>
                <label class="form-label">Tipo de gasto</label>
                <div class="eg-cats mb-3">
                    @foreach (\App\Models\Egreso::CATEGORIAS as $k => $c)
                    <label><input type="radio" name="categoria" value="{{ $k }}" @checked(old('categoria', 'otros') === $k)><span><i class="fa-solid {{ $c[1] }}"></i>{{ $c[0] }}</span></label>
                    @endforeach
                </div>
                <div class="mb-2">
                    <label class="form-label">¿Cómo se pagó?</label>
                    <select name="metodo_pago_id" class="form-select" id="egMetodo">
                        @foreach ($metodos as $m)<option value="{{ $m->id }}" data-efectivo="{{ $m->es_efectivo ? 1 : 0 }}" @selected((int) old('metodo_pago_id') === $m->id)>{{ $m->nombre }}</option>@endforeach
                    </select>
                </div>
                @if ($caja)
                <label class="d-flex gap-2 align-items-center small fw-bold mb-3" id="egCajaBox"><input type="hidden" name="de_caja" value="0"><input type="checkbox" name="de_caja" value="1" checked> Lo saqué de mi caja (se descuenta del efectivo esperado)</label>
                @else
                <small class="text-muted d-block mb-3" id="egCajaBox">No tiene caja abierta: el egreso queda anotado sin descontarse de ninguna caja.</small>
                @endif
                <div class="mb-3">
                    <label class="form-label">Foto del recibo <span class="text-muted fw-normal">(opcional)</span></label>
                    <input type="file" name="soporte" accept="image/*" class="form-control">
                </div>
                <div class="mb-3"><input name="nota" class="form-control" value="{{ old('nota') }}" maxlength="255" placeholder="Nota (opcional): a quién se le pagó, número de factura…"></div>
                <button class="btn btn-primary btn-lg w-100"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar egreso</button>
            </form>

            @if ($porCategoria->count())
            <div class="lp-box mt-3">
                <h3>En qué se va la plata</h3>
                @php $maxCat = max(1, (float) $porCategoria->max('total')); @endphp
                @foreach ($porCategoria as $pc)
                <div class="mb-2">
                    <div class="d-flex justify-content-between small fw-bold"><span><i class="fa-solid {{ \App\Models\Egreso::CATEGORIAS[$pc->categoria][1] ?? 'fa-receipt' }} me-1"></i>{{ \App\Models\Egreso::CATEGORIAS[$pc->categoria][0] ?? 'Otros' }} <span class="text-muted">({{ $pc->n }})</span></span><span>{{ $f($pc->total) }}</span></div>
                    <div class="eg-bar"><i style="width:{{ round($pc->total / $maxCat * 100) }}%"></i></div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <div class="col-lg-7">
            <div class="lp-box">
                <form method="get" class="eg-fil mb-3">
                    <div><label class="form-label small mb-1">Desde</label><input type="date" name="desde" class="form-control form-control-sm" value="{{ $desde->format('Y-m-d') }}"></div>
                    <div><label class="form-label small mb-1">Hasta</label><input type="date" name="hasta" class="form-control form-control-sm" value="{{ $hasta->format('Y-m-d') }}"></div>
                    <div><label class="form-label small mb-1">Tipo</label><select name="categoria" class="form-select form-select-sm"><option value="">Todos</option>@foreach (\App\Models\Egreso::CATEGORIAS as $k => $c)<option value="{{ $k }}" @selected($cat === $k)>{{ $c[0] }}</option>@endforeach</select></div>
                    <div><label class="form-label small mb-1">Quién</label><select name="quien" class="form-select form-select-sm">
                        <option value="">{{ $admin ? 'Todos' : 'Todo el equipo' }}</option>
                        <option value="yo" @selected($quien === 'yo')>Solo los míos</option>
                        @if ($admin)<option value="vendedores" @selected($quien === 'vendedores')>Solo vendedores</option><option value="admin" @selected($quien === 'admin')>Solo administración</option>@endif
                    </select></div>
                    <button class="btn btn-dark btn-sm">Filtrar</button>
                </form>

                @forelse ($lista as $e)
                <div class="eg-item">
                    <div class="av"><i class="fa-solid {{ $e->categoria_icono }}"></i></div>
                    <div class="t">
                        <b>{{ $e->concepto }}@if ($e->de_admin)<span class="eg-tag adm">Administración</span>@endif @if ($e->caja_id)<span class="eg-tag caja">De la caja</span>@endif</b>
                        <small>{{ $e->fecha->format('d/m/Y') }} · {{ $e->categoria_nombre }} · {{ $e->metodo->nombre ?? 'Sin forma de pago' }} · {{ (int) $e->user_id === (int) auth()->id() ? 'Usted' : ($e->user->name ?? '—') }}</small>
                        @if ($e->nota)<small class="d-block">{{ $e->nota }}</small>@endif
                    </div>
                    @if ($e->soporte_url)<a href="{{ $e->soporte_url }}" target="_blank" rel="noopener" class="btn btn-sm btn-light" title="Ver recibo"><i class="fa-solid fa-image"></i></a>@endif
                    <span class="m">-{{ $f($e->valor) }}</span>
                    @if ($e->puedeBorrar(auth()->user()))
                    <form method="post" action="{{ route('egresos.destroy', $e) }}" data-confirm="¿Borrar este egreso?" data-confirm-texto="{{ $e->caja_id ? 'Vuelve a sumarse al efectivo de la caja.' : 'Se quita de la lista.' }}" data-confirm-ok="Sí, borrar">@csrf @method('DELETE')<button class="btn btn-sm btn-light" title="Borrar"><i class="fa-solid fa-trash" style="color:#b0173f"></i></button></form>
                    @endif
                </div>
                @empty
                <div class="text-center text-muted py-4"><i class="fa-solid fa-receipt" style="font-size:2rem;color:#16b34a"></i><div class="fw-bold mt-2">No hay egresos en estas fechas.</div></div>
                @endforelse
                <div class="mt-3">{{ $lista->links() }}</div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var sel = document.getElementById('egMetodo'), box = document.getElementById('egCajaBox');
    if (!sel || !box) return;
    function ver() { var o = sel.selectedOptions[0]; box.style.display = o && o.dataset.efectivo === '1' ? '' : 'none'; }
    sel.addEventListener('change', ver); ver();
})();
</script>
@endsection
