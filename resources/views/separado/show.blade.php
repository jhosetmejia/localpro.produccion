@extends('layouts.app')

@section('title', 'Separado ' . $s->numero)

@push('css')
<style>
.sd-item{cursor:pointer;border-radius:14px;transition:background .15s}.sd-item:hover{background:#f3faf5}
.sd-top{background:#0b0d0c;color:#fff;border-radius:24px;padding:1.2rem 1.3rem;margin-bottom:1rem}
.sd-top .row1{display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:flex-start}
.sd-top small{color:#9fe3b0;font-weight:700}
.sd-top h2{font-size:1.5rem;font-weight:900;margin:.1rem 0 0}
.sd-nums{display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1rem}
.sd-nums div{flex:1 1 130px;background:#1a211d;border-radius:16px;padding:.7rem .9rem}
.sd-nums b{display:block;font-size:1.35rem;font-weight:900}
.sd-nums span{font-size:.78rem;color:#9aa69e;font-weight:700}
.sd-nums .falta b{color:#ffcf5c}.sd-nums .ok b{color:#2fd15a}
.sd-bar{height:12px;border-radius:99px;background:#26302a;overflow:hidden;margin-top:1rem}
.sd-bar i{display:block;height:100%;background:linear-gradient(90deg,#a6f34a,#16b34a)}
.sd-est{font-weight:900;border-radius:999px;padding:.35rem .9rem;font-size:.85rem}
.sd-ab li .acc{display:flex;gap:.3rem}
.sd-abonar{background:linear-gradient(180deg,#f4fcf6,#fff);border:1.5px solid #bfeccb}
.sd-wa{background:#25d366;color:#fff;border:0}
.sd-wa:hover{background:#1fb457;color:#fff}
</style>
@endpush

@section('content')
@php
    $f = fn ($v) => dinero($v);
    $estTxt = $s->estado === 'entregado' ? ['Entregado', '#2fd15a', '#0b0d0c'] : ($s->estado === 'cancelado' ? ['Cancelado', '#6b7570', '#fff'] : ($s->pagado ? ['✅ Pagado · listo para entregar', '#2fd15a', '#0b0d0c'] : ($s->vencido ? ['Vencido', '#ff5c9a', '#fff'] : ['Abonando', '#ffcf5c', '#0b0d0c'])));
    $ultimo = session('abono_nuevo') ? $s->abonos->firstWhere('id', session('abono_nuevo')) : null;
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-box-archive me-1"></i>Separados</small><h1>Separado <span>{{ $s->numero }}</span></h1>
            <span class="text-muted fw-semibold">Creado el {{ $s->created_at->format('d/m/Y h:i a') }}{{ $s->user ? ' por ' . $s->user->name : '' }}</span></div>
        <div class="lp-quick"><a href="{{ route('separados.index') }}" class="btn btn-light btn-lg"><i class="fa-solid fa-arrow-left me-1"></i>Separados</a></div>
    </div>
    @include('layouts.partials.lp-flash')
    @if ($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    @if ($ultimo)
    <div class="lp-box mb-3 d-flex gap-2 flex-wrap align-items-center" style="border-color:#bfeccb">
        <div class="flex-grow-1"><b><i class="fa-solid fa-receipt me-1" style="color:#16b34a"></i>Abono {{ $ultimo->numero }} de {{ $f($ultimo->monto) }} registrado.</b> <span class="text-muted">Entréguele su recibo:</span></div>
        @if ($s->telefono_wa)<a class="btn sd-wa" target="_blank" href="https://wa.me/{{ $s->telefono_wa }}?text={{ rawurlencode(\App\Http\Controllers\separadoController::mensajeAbono($s, $ultimo)) }}"><i class="fa-brands fa-whatsapp me-1"></i>Enviar por WhatsApp</a>@endif
        <a class="btn btn-dark" target="_blank" href="{{ route('separados.recibo', $ultimo) }}"><i class="fa-solid fa-print me-1"></i>Imprimir recibo</a>
    </div>
    @endif

    <div class="sd-top">
        <div class="row1">
            <div><small>Cliente</small><h2>{{ $s->cliente_nombre }}</h2>
                <span style="color:#c9d3cc">{{ $s->cliente_telefono ?: 'Sin WhatsApp' }}{{ $s->vence_en && $s->estado === 'activo' ? ' · Plazo hasta el ' . $s->vence_en->format('d/m/Y') : '' }}</span></div>
            <span class="sd-est" style="background:{{ $estTxt[1] }};color:{{ $estTxt[2] }}">{{ $estTxt[0] }}</span>
        </div>
        <div class="sd-nums">
            <div><span>Total</span><b>{{ $f($s->total) }}</b></div>
            <div class="ok"><span>Abonado</span><b>{{ $f($s->abonado) }}</b></div>
            <div class="falta"><span>Le falta</span><b>{{ $f($s->saldo) }}</b></div>
        </div>
        @if ($s->estado === 'activo')<div class="sd-bar"><i style="width:{{ max(3, $s->porcentaje) }}%"></i></div>@endif
    </div>

    <div class="lp-grid">
        <div>
            @if ($s->estado === 'activo' && !$s->pagado)
            <form method="post" action="{{ route('separados.abonar', $s) }}" class="lp-box sd-abonar mb-3">
                @csrf
                <h3><span><i class="fa-solid fa-hand-holding-dollar me-1"></i> Registrar abono</span></h3>
                @if (!$caja)<p class="small fw-bold" style="color:#a86b00">Para recibir el abono primero abra su caja.</p>@endif
                <div class="row g-2">
                    <div class="col-sm-5"><label class="form-label">¿Cuánto abona?</label><input name="monto" data-dinero type="number" min="1" max="{{ (int) ceil($s->saldo) }}" class="form-control form-control-lg fw-bold" required placeholder="Máx. {{ $f($s->saldo) }}"></div>
                    <div class="col-sm-4"><label class="form-label">¿Cómo pagó?</label><select name="metodo_id" class="form-select form-select-lg" data-nativo>@foreach ($metodos as $m)<option value="{{ $m->id }}">{{ $m->nombre }}</option>@endforeach</select></div>
                    <div class="col-sm-3 d-grid align-items-end"><button class="btn btn-primary btn-lg" style="margin-top:1.9rem">Abonar</button></div>
                </div>
                <button type="button" class="btn btn-sm btn-light mt-2" onclick="var i=this.form.querySelector('[name=monto]');i.value={{ (int) round($s->saldo) }};i.dispatchEvent(new Event('change'))">Paga todo lo que falta ({{ $f($s->saldo) }})</button>
            </form>
            @endif

            @if ($s->estado === 'activo' && $s->pagado)
            <form method="post" action="{{ route('separados.entregar', $s) }}" class="lp-box mb-3" style="border-color:#2fd15a;background:linear-gradient(180deg,#ecfaef,#fff)" data-confirm="¿Entregar la prenda a {{ $s->cliente_nombre }}?" data-confirm-texto="Queda registrada como venta. El dinero ya entró a la caja con cada abono." data-confirm-ok="Sí, entregar">
                @csrf
                <h3><span>🎉 ¡Pagado completo!</span></h3>
                <p class="text-muted mb-2">Saque la prenda de la bodega y entréguesela al cliente.</p>
                <button class="btn btn-primary btn-lg w-100"><i class="fa-solid fa-gift me-1"></i>Entregar y registrar la venta</button>
            </form>
            @endif

            @if ($s->estado === 'entregado')
            <div class="lp-box mb-3"><b><i class="fa-solid fa-circle-check me-1" style="color:#16b34a"></i>Entregado el {{ $s->entregado_en?->format('d/m/Y h:i a') }}</b>@if ($s->venta)<div class="text-muted">Venta {{ $s->venta->numero }} · <a target="_blank" href="{{ route('pos.ticket', $s->venta) }}">ver tiquete</a></div>@endif</div>
            @endif
            @if ($s->estado === 'cancelado')
            <div class="lp-box mb-3"><b><i class="fa-solid fa-ban me-1"></i>Cancelado el {{ $s->cancelado_en?->format('d/m/Y') }}</b><div class="text-muted">{{ $s->motivo_cancelacion }}</div></div>
            @endif

            <div class="lp-box mb-3">
                <h3><span><i class="fa-solid {{ \App\Models\Tienda::iconoProducto() }} me-1"></i> {{ ucfirst(\App\Models\Tienda::palabraProducto(true)) }} separados</span></h3>
                <ul class="lp-list">
                    @foreach ($s->items as $i)
                    <li role="button" class="sd-item" data-bs-toggle="modal" data-bs-target="#sdVer{{ $i->id }}" title="Ver la prenda">
                        <div class="av" style="{{ $i->foto_url ? 'padding:0;overflow:hidden;background:#fff' : '' }}">@if ($i->foto_url)<img src="{{ $i->foto_url }}" alt="" style="width:100%;height:100%;object-fit:cover">@else<i class="fa-solid fa-box"></i>@endif</div>
                        <div class="t"><b>{{ $i->descripcion }}</b><small>{{ $i->etiqueta ?: 'Única' }} · {{ cantidad($i->cantidad) }} × {{ $f($i->precio) }} · <span style="color:#16b34a;font-weight:800">Ver <i class="fa-solid fa-eye"></i></span></small></div>
                        <b>{{ $f($i->subtotal) }}</b>
                    </li>
                    @endforeach
                </ul>
                @if ($s->nota)<p class="small text-muted mb-0 mt-2"><i class="fa-regular fa-note-sticky me-1"></i>{{ $s->nota }}</p>@endif
            </div>
        </div>

        <div>
            <div class="lp-box mb-3 sd-ab">
                <h3><span><i class="fa-solid fa-receipt me-1"></i> Abonos</span><span class="small text-muted">{{ $s->abonos->where('anulado', false)->count() }}</span></h3>
                <ul class="lp-list">
                    @forelse ($s->abonos as $a)
                    <li style="{{ $a->anulado ? 'opacity:.5' : '' }}">
                        <div class="av" style="{{ $a->monto < 0 ? 'background:#ffe9f3;color:#d61f69' : '' }}"><i class="fa-solid {{ $a->monto < 0 ? 'fa-rotate-left' : 'fa-coins' }}"></i></div>
                        <div class="t"><b>{{ $f($a->monto) }} · {{ $a->metodo->nombre ?? '' }}</b><small>{{ $a->numero }} · {{ $a->created_at->format('d/m/Y h:i a') }}{{ $a->user ? ' · ' . $a->user->name : '' }} · quedó debiendo {{ $f($a->saldo_despues) }}{{ $a->nota ? ' · ' . $a->nota : '' }}</small></div>
                        <div class="acc">
                            @if ($a->monto > 0 && $s->telefono_wa)<a class="btn btn-sm sd-wa" target="_blank" title="Enviar recibo por WhatsApp" href="https://wa.me/{{ $s->telefono_wa }}?text={{ rawurlencode(\App\Http\Controllers\separadoController::mensajeAbono($s, $a)) }}"><i class="fa-brands fa-whatsapp"></i></a>@endif
                            <a class="btn btn-sm btn-dark" target="_blank" title="Imprimir recibo" href="{{ route('separados.recibo', $a) }}"><i class="fa-solid fa-print"></i></a>
                        </div>
                    </li>
                    @empty
                    <li class="lp-empty">Aún no hay abonos.</li>
                    @endforelse
                </ul>
                <div class="d-flex gap-2 mt-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-light" onclick="navigator.clipboard.writeText(@js($publico));LP.toast('Enlace copiado','ok')"><i class="fa-regular fa-copy me-1"></i>Copiar enlace para el cliente</button>
                    @if ($s->telefono_wa)<a class="btn btn-sm sd-wa" target="_blank" href="https://wa.me/{{ $s->telefono_wa }}?text={{ rawurlencode('Hola ' . strtok($s->cliente_nombre, ' ') . ' 👋 Le recordamos su separado ' . $s->numero . ' en ' . \App\Models\Negocio::datos()->nombre . ': le falta ' . $f($s->saldo) . ($s->vence_en ? ' y tiene plazo hasta el ' . $s->vence_en->format('d/m/Y') : '') . '. Vea sus abonos aquí: ' . $publico) }}"><i class="fa-brands fa-whatsapp me-1"></i>Recordarle lo que falta</a>@endif
                </div>
            </div>

            @if ($s->estado === 'activo')
            <form method="post" action="{{ route('separados.cancelar', $s) }}" class="lp-box" data-confirm="¿Cancelar el separado {{ $s->numero }}?" data-confirm-texto="La prenda vuelve al inventario disponible." data-confirm-ok="Sí, cancelar" data-peligro>
                @csrf
                <h3><span><i class="fa-solid fa-ban me-1"></i> Cancelar separado</span></h3>
                <input name="motivo" class="form-control mb-2" maxlength="200" placeholder="Motivo (ej. el cliente desistió, se venció el plazo)">
                @if ($s->abonado > 0)
                <label class="d-flex gap-2 align-items-center mb-2 small fw-bold"><input type="checkbox" name="devolver" value="1"> Devolverle el dinero abonado ({{ $f($s->abonado) }}) desde mi caja</label>
                @endif
                <button class="btn btn-outline-danger btn-sm">Cancelar separado</button>
            </form>
            @endif
        </div>
    </div>
</div>
@foreach ($s->items as $i)
<div class="modal fade" id="sdVer{{ $i->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:24px;border:0;overflow:hidden">
            <div style="background:#f4f8f5;display:grid;place-items:center;min-height:220px;position:relative">
                @if ($i->foto_url)<img src="{{ $i->foto_url }}" alt="{{ $i->descripcion }}" style="width:100%;max-height:60vh;object-fit:contain;background:#fff">@else<div class="text-center text-muted p-4"><i class="fa-regular fa-image" style="font-size:3rem;color:#16b34a"></i><div class="fw-bold mt-2">Este producto no tiene foto</div></div>@endif
                <button type="button" class="btn-close position-absolute" style="top:12px;right:12px;background-color:#fff;border-radius:50%;padding:.6rem;opacity:1" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="p-3">
                <b style="font-size:1.15rem;display:block">{{ $i->descripcion }}</b>
                <div class="d-flex gap-2 flex-wrap my-2">
                    @if ($i->talla)<span class="badge rounded-pill text-bg-dark">Talla {{ $i->talla }}</span>@endif
                    @if ($i->color)<span class="badge rounded-pill" style="background:#dcfce7;color:#14753a">{{ $i->color }}</span>@endif
                    <span class="badge rounded-pill text-bg-light">{{ cantidad($i->cantidad) }} × {{ $f($i->precio) }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center"><span class="text-muted fw-semibold">Subtotal</span><b style="font-size:1.2rem">{{ $f($i->subtotal) }}</b></div>
                @if ($i->producto)
                @can('editar-producto')
                <a href="{{ route('productos.edit', ['producto' => $i->producto]) }}" class="btn btn-dark w-100 mt-3" style="border-radius:999px;font-weight:800"><i class="fa-solid fa-pen me-1"></i>Abrir el producto</a>
                @endcan
                @else
                <small class="text-muted d-block mt-2">El producto ya no existe en el inventario.</small>
                @endif
            </div>
        </div>
    </div>
</div>
@endforeach
@endsection
