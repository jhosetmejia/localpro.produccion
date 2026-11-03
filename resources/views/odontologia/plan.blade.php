@extends('layouts.app')

@section('title', 'Plan ' . $plan->numero . ' · ' . $p->nombre_completo)

@push('css')
<style>
.pt-grid{display:grid;grid-template-columns:1.6fr 1fr;gap:14px;align-items:start}
.pt-tabla{width:100%;border-collapse:separate;border-spacing:0;font-size:.9rem}
.pt-tabla th{font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:var(--lp-muted);font-weight:900;padding:.4rem .5rem;border-bottom:1.5px solid #d6eef3;white-space:nowrap}
.pt-tabla td{padding:.6rem .5rem;border-bottom:1px solid #eef5f7;vertical-align:middle}
.pt-tabla tr.hecho td{background:#f0fdf4}
.pt-tabla tr.hecho .desc{text-decoration:line-through;text-decoration-color:#16a34a66}
.pt-pieza{display:inline-grid;place-items:center;min-width:34px;height:26px;border-radius:9px;background:#ecfeff;color:#0e7490;font-weight:900;font-size:.82rem;padding:0 .35rem}
.pt-chk{width:30px;height:30px;border-radius:10px;border:2px solid #cbd5e1;background:#fff;display:grid;place-items:center;color:#fff;cursor:pointer}
.pt-chk.on{background:#16a34a;border-color:#16a34a}
.pt-tot{display:grid;gap:.35rem;margin-top:1rem;max-width:340px;margin-left:auto}
.pt-tot div{display:flex;justify-content:space-between;gap:1rem;font-weight:700}
.pt-tot .gran{font-size:1.2rem;font-weight:900;border-top:1.5px solid #d6eef3;padding-top:.4rem}
.pt-tot .saldo{color:#e11d48;font-size:1.25rem;font-weight:900}
.pt-grid>*{min-width:0}
.pt-nuevo{display:grid;grid-template-columns:2.4fr .8fr .7fr .55fr 1fr auto;gap:8px;align-items:end;background:#f5feff;border:1.5px dashed #a5f3fc;border-radius:18px;padding:.8rem;margin-top:1rem}
.pt-nuevo label{font-size:.72rem;font-weight:800;color:var(--lp-muted);display:block;margin-bottom:.15rem}
.pt-nuevo .form-control,.pt-nuevo .form-select{border-radius:12px}
.pt-ab{display:flex;justify-content:space-between;gap:.5rem;padding:.55rem 0;border-bottom:1px dashed #e2eef1;font-size:.88rem}
.pt-ab:last-child{border-bottom:0}
.pt-ok{background:#ecfdf5;border:1.5px solid #a7f3d0;border-radius:18px;padding:.8rem 1rem;margin-bottom:1rem;display:flex;justify-content:space-between;gap:.8rem;flex-wrap:wrap;align-items:center}
.pt-est{display:flex;gap:6px;flex-wrap:wrap}
@media (max-width:991px){.pt-grid{grid-template-columns:1fr}.pt-nuevo{grid-template-columns:1fr 1fr}}
</style>
@endpush

@section('content')
@php
    [$estNom, $estCol] = \App\Models\TratamientoPlan::ESTADOS[$plan->estado];
    $abierto = !in_array($plan->estado, ['terminado', 'cancelado'], true);
    $edita = $clinico && $abierto;
@endphp
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><a href="{{ route('consultorio.paciente', $p) }}" class="text-decoration-none" style="color:inherit"><i class="fa-solid fa-arrow-left me-1"></i>{{ $p->nombre_completo }}</a></small>
            <h1>{{ $plan->numero }} · <span>{{ $plan->nombre }}</span></h1>
            <span class="cs-est" style="background:{{ $estCol }};font-size:.8rem">{{ $estNom }}</span>
            <span class="text-muted fw-semibold ms-1">Creado el {{ $plan->created_at->format('d/m/Y') }}{{ $plan->profesional ? ' por ' . $plan->profesional->name : '' }}{{ $plan->aprobado_at ? ' · Aprobado el ' . $plan->aprobado_at->format('d/m/Y') : '' }}</span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('odonto.plan.presupuesto', $plan) }}" target="_blank" class="cs-btn"><i class="fa-solid fa-print"></i>Presupuesto</a>
            @if ($clinico)<a href="{{ route('odonto.odontograma', $p) }}" class="cs-btn"><i class="fa-solid fa-tooth"></i>Odontograma</a>@endif
        </div>
    </div>
    @include('consultorio._nav')

    @if ($abonoNuevo)
    <div class="pt-ok">
        <div><b><i class="fa-solid fa-circle-check me-1" style="color:#16a34a"></i>Abono {{ $abonoNuevo->numero }} por {{ dinero($abonoNuevo->monto) }}</b><div class="small text-muted">Saldo después del abono: {{ dinero($abonoNuevo->saldo_despues) }}</div></div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('odonto.abono.recibo', $abonoNuevo) }}" target="_blank" class="cs-btn sm dark"><i class="fa-solid fa-receipt"></i>Imprimir recibo</a>
            @if ($p->whatsapp)<a href="https://wa.me/{{ $p->whatsapp }}?text={{ rawurlencode(\App\Http\Controllers\odontologiaController::mensajeAbono($plan, $abonoNuevo)) }}" target="_blank" rel="noopener" class="cs-btn sm"><i class="fa-brands fa-whatsapp" style="color:#16a34a"></i>Enviar por WhatsApp</a>@endif
        </div>
    </div>
    @endif

    <div class="pt-grid">
        <div class="cs-card">
            <h3>Procedimientos <small>{{ $plan->items->where('estado', 'hecho')->count() }} de {{ $plan->items->count() }} hechos · {{ $plan->avance }}%</small></h3>
            <div class="table-responsive">
            <table class="pt-tabla">
                <thead><tr><th></th><th>Pieza</th><th>Procedimiento</th><th class="text-end">Cant.</th><th class="text-end">Valor</th><th class="text-end">Total</th><th></th></tr></thead>
                <tbody>
                @forelse ($plan->items as $it)
                <tr class="{{ $it->estado === 'hecho' ? 'hecho' : '' }}">
                    <td>
                        @if ($edita)
                        <form method="post" action="{{ route('odonto.item.hecho', [$plan, $it]) }}">@csrf<button class="pt-chk {{ $it->estado === 'hecho' ? 'on' : '' }}" title="{{ $it->estado === 'hecho' ? 'Marcar como pendiente' : 'Marcar como hecho' }}"><i class="fa-solid fa-check"></i></button></form>
                        @else<span class="pt-chk {{ $it->estado === 'hecho' ? 'on' : '' }}" style="cursor:default"><i class="fa-solid fa-check"></i></span>@endif
                    </td>
                    <td>@if ($it->pieza)<span class="pt-pieza">{{ $it->pieza }}{{ $it->caras ? ' ' . $it->caras : '' }}</span>@else<span class="text-muted">—</span>@endif</td>
                    <td class="desc"><b>{{ $it->descripcion }}</b>@if ($it->estado === 'hecho')<small class="d-block text-success fw-bold">Hecho {{ $it->hecho_at?->format('d/m/Y') }}{{ $it->hecho_por ? ' · ' . $it->hecho_por : '' }}</small>@endif</td>
                    <td class="text-end">{{ $it->cantidad }}</td>
                    <td class="text-end text-nowrap">{{ dinero($it->valor) }}</td>
                    <td class="text-end text-nowrap fw-bold">{{ dinero($it->total) }}</td>
                    <td class="text-end">
                        @if ($edita && $it->estado !== 'hecho')
                        <form method="post" action="{{ route('odonto.item.destroy', [$plan, $it]) }}" data-confirm="¿Quitar este procedimiento?" data-confirm-ok="Sí, quitar" data-peligro>@csrf @method('DELETE')<button class="btn btn-link text-danger p-0" title="Quitar"><i class="fa-solid fa-trash-can"></i></button></form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="7"><div class="lp-empty">Todavía no hay procedimientos. {{ $edita ? 'Agréguelos abajo o desde el odontograma.' : '' }}</div></td></tr>
                @endforelse
                </tbody>
            </table>
            </div>

            @if ($edita)
            <form method="post" action="{{ route('odonto.item.store', $plan) }}" class="pt-nuevo">
                @csrf
                <div><label>Servicio o procedimiento</label>
                    <select name="producto_id" class="form-select" id="pt-serv">
                        <option value="">— Escribir otro —</option>
                        @foreach ($servicios as $s)<option value="{{ $s->id }}" data-precio="{{ (float) $s->precio_venta }}">{{ $s->nombre }} · {{ dinero($s->precio_venta) }}</option>@endforeach
                    </select>
                    <input name="descripcion" class="form-control mt-1" id="pt-desc" maxlength="160" placeholder="O escriba el procedimiento">
                </div>
                <div><label>Pieza</label>
                    <select name="pieza" class="form-select"><option value="">—</option>
                        @foreach ($piezas as $pz)<option value="{{ $pz }}">{{ $pz }}</option>@endforeach</select></div>
                <div><label>Caras</label><input name="caras" class="form-control" maxlength="5" placeholder="Ej. OM" style="text-transform:uppercase"></div>
                <div><label>Cant.</label><input name="cantidad" type="number" min="1" max="99" value="1" class="form-control"></div>
                <div><label>Valor c/u</label><input name="valor" class="form-control" id="pt-valor" data-dinero inputmode="numeric" placeholder="0"></div>
                <div><button class="cs-btn prim"><i class="fa-solid fa-plus"></i>Agregar</button></div>
            </form>
            @endif

            <div class="pt-tot">
                <div><span>Subtotal</span><span>{{ dinero($plan->subtotal) }}</span></div>
                @if ($plan->descuento > 0)<div><span>Descuento</span><span>− {{ dinero($plan->descuento) }}</span></div>@endif
                <div class="gran"><span>Total</span><span>{{ dinero($plan->total) }}</span></div>
                <div><span>Abonado</span><span style="color:#16a34a">{{ dinero($plan->abonado) }}</span></div>
                <div><span>Saldo</span><span class="saldo">{{ dinero($plan->saldo) }}</span></div>
            </div>
        </div>

        <div class="d-flex flex-column gap-3">
            <div class="cs-card">
                <h3>Abonos del paciente <small>{{ $plan->abonos->where('anulado', false)->count() }}</small></h3>
                @if ($plan->estado !== 'cancelado' && $plan->saldo > 0)
                    @if ($caja)
                    <form method="post" action="{{ route('odonto.abono', $plan) }}" class="d-grid gap-2 mb-3">
                        @csrf
                        <label class="small fw-bold" style="color:var(--lp-muted)">¿Cuánto abona? Saldo: {{ dinero($plan->saldo) }}</label>
                        <input name="monto" class="form-control" data-dinero inputmode="numeric" placeholder="Valor del abono" required style="border-radius:14px;font-weight:800;font-size:1.05rem">
                        <select name="metodo_id" class="form-select" required>
                            @foreach ($metodos as $m)<option value="{{ $m->id }}">{{ $m->nombre }}</option>@endforeach
                        </select>
                        <input name="nota" class="form-control" maxlength="255" placeholder="Nota (opcional), ej. cuota 2 de ortodoncia" style="border-radius:14px">
                        <button class="cs-btn prim justify-content-center"><i class="fa-solid fa-hand-holding-dollar"></i>Recibir abono</button>
                        <small class="text-muted">Entra a su caja abierta y sale en el cierre de caja.</small>
                    </form>
                    @else
                    <div class="lp-empty mb-3">Para recibir abonos abra su caja.<br><a href="{{ route('cajas.abrir') }}" class="cs-btn sm dark mt-2"><i class="fa-solid fa-vault"></i>Abrir mi caja</a></div>
                    @endif
                @elseif ($plan->saldo <= 0 && $plan->items->count())
                <div class="pt-ok mb-3" style="margin:0"><b><i class="fa-solid fa-circle-check me-1" style="color:#16a34a"></i>Tratamiento pagado completo</b></div>
                @endif
                @forelse ($plan->abonos as $ab)
                <div class="pt-ab" style="{{ $ab->anulado ? 'opacity:.5;text-decoration:line-through' : '' }}">
                    <span><b>{{ dinero($ab->monto) }}</b> · {{ $ab->metodo?->nombre }}<br><small class="text-muted">{{ $ab->numero }} · {{ $ab->created_at->format('d/m/Y h:i a') }} · {{ $ab->user?->name }}{{ $ab->nota ? ' · ' . $ab->nota : '' }}</small></span>
                    <a href="{{ route('odonto.abono.recibo', $ab) }}" target="_blank" class="cs-btn sm align-self-center"><i class="fa-solid fa-receipt"></i></a>
                </div>
                @empty<div class="text-muted small">Sin abonos todavía.</div>@endforelse
            </div>

            @if ($plan->es_ortodoncia)
            <div class="cs-card" style="border-color:#a5f3fc">
                <h3><span><i class="fa-solid fa-teeth-open me-1" style="color:#0891b2"></i>Ortodoncia</span>
                    @if ($plan->atraso > 0)<span class="cs-est" style="background:#e11d48">{{ $plan->cuotas_atrasadas }} cuota(s) atrasada(s)</span>@elseif ($plan->cuota_mensual > 0)<span class="cs-est" style="background:#16a34a">Al día</span>@endif</h3>
                @if ($plan->cuota_mensual > 0)
                <div class="small mb-2">Inicio {{ $plan->inicio?->format('d/m/Y') }} · Cuota inicial {{ dinero($plan->cuota_inicial) }} · {{ $plan->cuotas }} cuotas de {{ dinero($plan->cuota_mensual) }}</div>
                <div class="d-flex justify-content-between small fw-bold"><span>Debería llevar pagado hoy</span><span>{{ dinero($plan->deberia_llevar) }}</span></div>
                <div class="d-flex justify-content-between small fw-bold"><span>Lleva pagado</span><span style="color:#16a34a">{{ dinero($plan->abonado) }}</span></div>
                @if ($plan->atraso > 0)<div class="d-flex justify-content-between small fw-bold"><span>Atrasado</span><span style="color:#e11d48">{{ dinero($plan->atraso) }}</span></div>
                @if ($p->whatsapp)<a class="cs-btn sm mt-2" target="_blank" rel="noopener" href="https://wa.me/{{ $p->whatsapp }}?text={{ rawurlencode('Hola ' . $p->nombres . ', le recordamos que tiene ' . $plan->cuotas_atrasadas . ' cuota(s) pendiente(s) de su ortodoncia por ' . dinero($plan->atraso) . '. Puede pagarla en su próximo control. ¡Gracias!') }}"><i class="fa-brands fa-whatsapp" style="color:#16a34a"></i>Recordarle el pago</a>@endif
                @endif
                @else
                <div class="small text-muted">Ponga abajo, en "Datos del plan", la cuota inicial, la cuota mensual y el número de meses.</div>
                @endif
                <h3 class="mt-3" style="font-size:.92rem">Controles <small>{{ $plan->controles->count() }}</small></h3>
                @if ($clinico && $abierto)
                <form method="post" action="{{ route('odonto.control.guardar', $plan) }}" class="d-grid gap-2 mb-2">
                    @csrf
                    <div class="d-flex gap-2"><input type="date" name="fecha" class="form-control form-control-sm" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" required>
                        <input type="date" name="proximo" class="form-control form-control-sm" title="Próximo control" value="{{ today()->addMonth()->toDateString() }}"></div>
                    <div class="d-flex gap-2"><input name="arco_sup" class="form-control form-control-sm" maxlength="60" placeholder="Arco superior (ej. 0.016 NiTi)"><input name="arco_inf" class="form-control form-control-sm" maxlength="60" placeholder="Arco inferior"></div>
                    <div class="d-flex gap-2"><input name="ligaduras" class="form-control form-control-sm" maxlength="60" placeholder="Ligaduras (color)"><input name="elasticos" class="form-control form-control-sm" maxlength="80" placeholder="Elásticos (ej. clase II 3/16)"></div>
                    <textarea name="notas" class="form-control form-control-sm" rows="2" maxlength="3000" placeholder="Qué se hizo, indicaciones…"></textarea>
                    <button class="cs-btn sm justify-content-center"><i class="fa-solid fa-plus"></i>Guardar control</button>
                </form>
                @endif
                @foreach ($plan->controles->take(12) as $ct)
                <div class="pt-ab"><span><b>{{ $ct->fecha->format('d/m/Y') }}</b> · {{ implode(' · ', array_filter([$ct->arco_sup ? 'Sup: ' . $ct->arco_sup : null, $ct->arco_inf ? 'Inf: ' . $ct->arco_inf : null, $ct->ligaduras, $ct->elasticos])) ?: 'Control' }}
                    @if ($clinico && $ct->notas)<br><small class="text-muted">{{ $ct->notas }}</small>@endif
                    <br><small class="text-muted">{{ $ct->profesional_nombre }}{{ $ct->proximo ? ' · Próximo: ' . $ct->proximo->format('d/m/Y') : '' }}</small></span></div>
                @endforeach
            </div>
            @endif

            @if ($clinico)
            <div class="cs-card">
                <h3>Datos del plan</h3>
                @if ($abierto)
                <form method="post" action="{{ route('odonto.plan.guardar', $plan) }}" class="d-grid gap-2">
                    @csrf @method('PUT')
                    <input name="nombre" class="form-control" value="{{ $plan->nombre }}" maxlength="120" style="border-radius:14px">
                    <div class="d-flex gap-2 align-items-center"><label class="small fw-bold text-nowrap" style="color:var(--lp-muted)">Descuento</label>
                        <input name="descuento" class="form-control" data-dinero inputmode="numeric" value="{{ (float) $plan->descuento ?: '' }}" placeholder="0" style="border-radius:14px"></div>
                    @if ($plan->es_ortodoncia)
                    <div class="row g-2">
                        <div class="col-6"><label class="small fw-bold" style="color:var(--lp-muted)">Inicio</label><input type="date" name="inicio" class="form-control" value="{{ $plan->inicio?->toDateString() }}" style="border-radius:14px"></div>
                        <div class="col-6"><label class="small fw-bold" style="color:var(--lp-muted)">Meses (cuotas)</label><input type="number" name="cuotas" min="0" max="120" class="form-control" value="{{ $plan->cuotas }}" style="border-radius:14px"></div>
                        <div class="col-6"><label class="small fw-bold" style="color:var(--lp-muted)">Cuota inicial</label><input name="cuota_inicial" class="form-control" data-dinero inputmode="numeric" value="{{ (float) $plan->cuota_inicial ?: '' }}" placeholder="0" style="border-radius:14px"></div>
                        <div class="col-6"><label class="small fw-bold" style="color:var(--lp-muted)">Cuota mensual</label><input name="cuota_mensual" class="form-control" data-dinero inputmode="numeric" value="{{ (float) $plan->cuota_mensual ?: '' }}" placeholder="0" style="border-radius:14px"></div>
                    </div>
                    <small class="text-muted">El valor del tratamiento queda como un renglón: cuota inicial + meses × cuota. Los abonos se descuentan del saldo y el sistema le avisa si va atrasado.</small>
                    @endif
                    <textarea name="notas" class="form-control" rows="2" maxlength="3000" placeholder="Notas: forma de pago acordada, duración, indicaciones…" style="border-radius:14px">{{ $plan->notas }}</textarea>
                    <button class="cs-btn justify-content-center"><i class="fa-solid fa-floppy-disk"></i>Guardar</button>
                </form>
                @elseif ($plan->notas)<p class="small" style="white-space:pre-line">{{ $plan->notas }}</p>@endif
                <div class="pt-est mt-3">
                    @if ($plan->estado === 'propuesto')
                    <form method="post" action="{{ route('odonto.plan.guardar', $plan) }}">@csrf @method('PUT')<input type="hidden" name="accion" value="aprobar"><button class="cs-btn sm dark"><i class="fa-solid fa-handshake"></i>El paciente aprobó</button></form>
                    @endif
                    @if ($abierto && $plan->estado === 'aprobado')
                    <form method="post" action="{{ route('odonto.plan.guardar', $plan) }}" data-confirm="¿Terminar el plan?" data-confirm-texto="{{ $plan->saldo > 0 ? 'Queda un saldo de ' . dinero($plan->saldo) . ' por cobrar.' : 'Queda como terminado.' }}" data-confirm-ok="Sí, terminar">@csrf @method('PUT')<input type="hidden" name="accion" value="terminar"><button class="cs-btn sm"><i class="fa-solid fa-flag-checkered"></i>Terminar</button></form>
                    @endif
                    @if ($abierto)
                    <form method="post" action="{{ route('odonto.plan.guardar', $plan) }}" data-confirm="¿Cancelar el plan?" data-confirm-ok="Sí, cancelar" data-peligro>@csrf @method('PUT')<input type="hidden" name="accion" value="cancelar"><button class="cs-btn sm"><i class="fa-solid fa-ban"></i>Cancelar</button></form>
                    @else
                    <form method="post" action="{{ route('odonto.plan.guardar', $plan) }}">@csrf @method('PUT')<input type="hidden" name="accion" value="reabrir"><button class="cs-btn sm"><i class="fa-solid fa-rotate-left"></i>Volver a abrir</button></form>
                    @endif
                </div>
                @if ($plan->aprobado_nota)<div class="small text-muted mt-2"><i class="fa-solid fa-handshake me-1"></i>{{ $plan->aprobado_nota }}</div>@endif
            </div>
            @endif
            @if ($p->whatsapp)
            <a href="{{ route('odonto.plan.presupuesto', $plan) }}#whatsapp" target="_blank" class="cs-btn justify-content-center"><i class="fa-brands fa-whatsapp" style="color:#16a34a"></i>Enviar presupuesto por WhatsApp</a>
            @endif
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
(function () {
    var s = document.getElementById('pt-serv'), v = document.getElementById('pt-valor'), d = document.getElementById('pt-desc');
    if (!s) return;
    s.addEventListener('change', function () {
        var o = s.options[s.selectedIndex];
        if (o && o.dataset.precio !== undefined) { v.value = Math.round(+o.dataset.precio); v.dispatchEvent(new Event('input', { bubbles: true })); v.dispatchEvent(new Event('blur')); }
        d.style.display = s.value ? 'none' : '';
    });
})();
</script>
@endpush
