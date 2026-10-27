{{-- Hoja de la cotización: la misma para el negocio (imprimir / guardar PDF) y para el cliente (enlace de WhatsApp) --}}
@php
    $negocioLinea = trim(implode(' · ', array_filter([$tienda->direccion, trim(($tienda->ciudad ?? '') . ($tienda->departamento ? ', ' . $tienda->departamento : ''), ', '), $tienda->telefono ? 'Tel. ' . $tienda->telefono : null, $tienda->email])));
    $bs = $tienda->usa_bolivares ? precio_bs($c->total) : null;
    $logo = $c->logo_url ?? $tienda->logo_url; // logo propio de la cotización o el del negocio
    $conEvento = $c->evento_fecha || $c->horario_texto;
@endphp
<style>
.ct-hoja{--ac:#16b34a;--ink:#0b0d0c;background:#fff;max-width:820px;margin:0 auto;padding:40px 44px;color:#151a17;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:1.45;box-shadow:0 20px 50px -30px rgba(0,0,0,.45);border-radius:6px;position:relative;overflow:hidden}
.ct-hoja::before{content:"";position:absolute;left:0;top:0;right:0;height:8px;background:linear-gradient(90deg,#2fd15a,#a6f34a)}
.ct-hoja .cab{display:flex;gap:18px;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;margin-bottom:22px}
.ct-hoja .neg{display:flex;gap:14px;align-items:center;min-width:0}
.ct-hoja .neg img{max-height:74px;max-width:150px;object-fit:contain}
.ct-hoja .neg b{display:block;font-size:19px;letter-spacing:-.01em}
.ct-hoja .neg span{display:block;color:#5d6862;font-size:11.5px}
.ct-hoja .doc{text-align:right}
.ct-hoja .doc .tit{font-size:26px;font-weight:900;letter-spacing:.04em;text-transform:uppercase;color:var(--ink);line-height:1}
.ct-hoja .doc .nro{display:inline-block;margin-top:6px;background:var(--ink);color:#a6f34a;font-weight:900;border-radius:999px;padding:3px 12px;font-size:12px}
.ct-hoja .doc small{display:block;color:#5d6862;margin-top:6px;font-size:11.5px}
.ct-hoja .cajas{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:18px}
.ct-hoja .caja{border:1px solid #e3ebe6;border-radius:12px;padding:10px 14px;background:#fafdfb}
.ct-hoja .caja h4{font-size:10.5px;text-transform:uppercase;letter-spacing:.08em;color:var(--ac);margin:0 0 4px;font-weight:900}
.ct-hoja .caja b{display:block;font-size:14px}
.ct-hoja .caja span{display:block;color:#45504a;font-size:12px}
.ct-hoja .asunto{font-size:15px;font-weight:800;margin:0 0 10px}
.ct-hoja table{width:100%;border-collapse:collapse}
.ct-hoja thead th{background:var(--ink);color:#fff;font-size:10.5px;text-transform:uppercase;letter-spacing:.06em;padding:8px 10px;text-align:left}
.ct-hoja thead th:first-child{border-radius:8px 0 0 8px}.ct-hoja thead th:last-child{border-radius:0 8px 8px 0}
.ct-hoja tbody td{padding:9px 10px;border-bottom:1px solid #eef1ef;vertical-align:top}
.ct-hoja tbody tr:nth-child(even) td{background:#fafdfb}
.ct-hoja .r{text-align:right;white-space:nowrap}
.ct-hoja .c{text-align:center}
.ct-hoja .tot{display:flex;justify-content:flex-end;margin-top:12px}
.ct-hoja .tot div{min-width:270px}
.ct-hoja .tot p{display:flex;justify-content:space-between;margin:0;padding:4px 10px}
.ct-hoja .tot .g{background:linear-gradient(90deg,#2fd15a,#a6f34a);color:var(--ink);font-size:18px;font-weight:900;border-radius:10px;padding:9px 12px;margin-top:6px}
.ct-hoja .cond{margin-top:22px;border-left:4px solid #a6f34a;background:#f7fbf8;border-radius:0 10px 10px 0;padding:10px 14px;white-space:pre-line;font-size:12px;color:#33403a}
.ct-hoja .cond b{display:block;font-size:10.5px;text-transform:uppercase;letter-spacing:.08em;color:var(--ac);margin-bottom:3px}
.ct-hoja .firma{display:flex;justify-content:space-between;gap:20px;margin-top:46px;flex-wrap:wrap}
.ct-hoja .firma div{flex:1;min-width:200px;border-top:1px solid #151a17;padding-top:5px;text-align:center;font-size:11.5px;color:#45504a}
.ct-hoja .pie{margin-top:26px;text-align:center;font-size:10.5px;color:#8a948f}
.ct-hoja .sello{position:absolute;right:30px;top:120px;transform:rotate(-12deg);border:3px solid;border-radius:10px;padding:4px 12px;font-weight:900;font-size:16px;text-transform:uppercase;opacity:.75}
@media (max-width:640px){.ct-hoja{padding:26px 16px}.ct-hoja .cajas{grid-template-columns:1fr}.ct-hoja .doc{text-align:left}.ct-hoja .sello{display:none}.ct-hoja table{font-size:12px}.ct-hoja .hide-sm{display:none}}
@media print{
  .ct-hoja{box-shadow:none;border-radius:0;max-width:none;padding:0 0 0 0;-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .ct-hoja::before{display:none}
  .ct-hoja .no-pdf{display:none!important}
  .ct-hoja .cajas{grid-template-columns:1fr 1fr}.ct-hoja .hide-sm{display:table-cell}
  @page{margin:14mm 14mm}
}
</style>
<div class="ct-hoja" id="ctHoja">
    @if ($c->estado === 'aceptada')<div class="sello" style="color:#16b34a">Aceptada</div>@elseif ($c->vencida)<div class="sello" style="color:#9aa5a0">Vencida</div>@endif
    <div class="cab">
        <div class="neg">
            @if ($logo)<img src="{{ $logo }}" alt="{{ $tienda->nombre }}">@endif
            <div><b>{{ $tienda->nombre }}</b>
                @if ($tienda->nit)<span>NIT {{ $tienda->nit }}</span>@endif
                @if ($negocioLinea)<span>{{ $negocioLinea }}</span>@endif</div>
        </div>
        <div class="doc">
            <div class="tit">Cotización</div>
            <span class="nro">{{ $c->numero }}</span>
            <small>Fecha: {{ $c->fecha->format('d/m/Y') }}@if ($c->vence) · Válida hasta: {{ $c->vence->format('d/m/Y') }}@endif</small>
        </div>
    </div>

    <div class="cajas">
        <div class="caja"><h4>Cliente</h4><b>{{ $c->cliente_nombre }}</b>
            @if ($c->cliente_documento)<span>CC / NIT: {{ $c->cliente_documento }}</span>@endif
            @if ($c->cliente_telefono)<span>Tel.: {{ $c->cliente_telefono }}</span>@endif
            @if ($c->cliente_email)<span>{{ $c->cliente_email }}</span>@endif
            @if ($c->cliente_direccion)<span>{{ $c->cliente_direccion }}</span>@endif</div>
        <div class="caja"><h4>{{ $conEvento ? 'Alquiler / evento' : 'Atendido por' }}</h4>
            @if ($conEvento)
                @if ($c->evento_fecha)<b>{{ $c->evento_texto }}</b>@endif
                @if ($c->horario_texto)<span>Hora: {{ $c->horario_texto }}</span>@endif
                @if ($c->evento_lugar)<span>Lugar: {{ $c->evento_lugar }}</span>@endif
            @endif
            <span>{{ $conEvento ? 'Atendido por: ' : '' }}{{ $c->user?->name ?? $tienda->nombre }}</span>
            @if ($tienda->telefono)<span>WhatsApp: {{ $tienda->telefono }}</span>@endif
        </div>
    </div>

    @if ($c->asunto)<p class="asunto">{{ $c->asunto }}</p>@endif
    <table>
        <thead><tr><th class="c hide-sm" style="width:36px">#</th><th>Descripción</th><th class="c" style="width:70px">Cant.</th><th class="r" style="width:120px">Valor unidad</th><th class="r" style="width:130px">Subtotal</th></tr></thead>
        <tbody>
            @foreach ($c->items as $n => $it)
            <tr>
                <td class="c hide-sm">{{ $n + 1 }}</td>
                <td>{{ $it->descripcion }}
                    @if (!empty($disp) && $it->producto_id && isset($disp[$it->producto_id]) && $disp[$it->producto_id]['libre'] !== null)
                        @php $dd = $disp[$it->producto_id]; $falta = $it->cantidad > $dd['libre']; @endphp
                        <span class="no-pdf" style="display:block;font-size:11px;font-weight:700;color:{{ $falta ? '#9b1c1c' : '#11702f' }}">{{ $falta ? '⚠ Solo quedan' : '✓ Disponibles' }} {{ rtrim(rtrim(number_format($dd['libre'], 3, ',', '.'), '0'), ',') }} de {{ rtrim(rtrim(number_format($dd['tiene'], 3, ',', '.'), '0'), ',') }} para esas fechas</span>
                    @endif
                </td>
                <td class="c">{{ $it->cantidad_texto }}</td>
                <td class="r">{{ dinero($it->precio) }}</td>
                <td class="r"><b>{{ dinero($it->subtotal) }}</b></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="tot"><div>
        <p><span>Subtotal</span><span>{{ dinero($c->subtotal) }}</span></p>
        @if ($c->descuento > 0)<p><span>Descuento</span><span>− {{ dinero($c->descuento) }}</span></p>@endif
        <p class="g"><span>TOTAL</span><span>{{ dinero($c->total) }}</span></p>
        @if ($bs)<p style="color:#5d6862;font-size:11.5px"><span>Equivale a</span><span>{{ $bs }}</span></p>@endif
    </div></div>

    @if ($c->condiciones)<div class="cond"><b>Condiciones</b>{{ $c->condiciones }}</div>@endif

    <div class="firma"><div>{{ $tienda->nombre }}</div><div>Acepto · {{ $c->cliente_nombre }}</div></div>
    <div class="pie">Cotización hecha con LocalProo · localproo.com</div>
</div>
