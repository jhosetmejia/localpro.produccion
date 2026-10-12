@extends('layouts.app')

@section('title', 'Facturación DIAN')

@section('content')
@php
    $resp = \App\Support\Dian\Facturador::responsabilidades($negocio);
    $grupos = ['datos' => 'Datos del RUT', 'impuestos' => 'Impuestos', 'numeracion' => 'Numeración autorizada', 'envio' => 'Envío a la DIAN'];
    $esAdmin = auth()->user()->es_superadmin;
@endphp
<div class="lp-dash">
    <div class="lp-hello"><div><small>Configuración</small><h1>Facturación <span>DIAN</span></h1><span class="text-muted fw-semibold">Factura electrónica y documento equivalente POS electrónico, como lo pide la DIAN.</span></div></div>
    @include('layouts.partials.lp-flash')

    {{-- Qué tan listo está --}}
    <div class="fd-top">
        <div class="fd-anillo" style="--p:{{ $prep['pct'] }}"><b>{{ $prep['pct'] }}%</b><small>listo</small></div>
        <div class="fd-top-t">
            <b>{{ $prep['ok'] }} de {{ $prep['total'] }} requisitos cumplidos</b>
            <p>
                @if ($prep['obligado'])
                    Su negocio <b>está obligado a facturar electrónicamente</b> (por sus responsabilidades en el RUT). Cada venta debe salir como POS electrónico o factura electrónica.
                @else
                    Como persona natural <b>no responsable de IVA</b> no está obligado a facturar: puede entregar el tiquete de LocalPro. Si un cliente le pide factura, active la factura electrónica.
                @endif
            </p>
            <span class="fd-prov"><i class="fa-solid fa-plug me-1"></i>{{ $proveedor }}</span>
        </div>
    </div>

    <div class="fd-grid">
        <div>
            <div class="lp-box mb-3">
                <h3><i class="fa-solid fa-list-check me-1"></i> Lo que necesita para estar bien ante la DIAN</h3>
                @foreach ($grupos as $g => $nombre)
                <div class="fd-gr">{{ $nombre }}</div>
                @foreach (collect($prep['lista'])->where(0, $g) as [, $tit, $ok, $txt])
                <div class="fd-req {{ $ok ? 'ok' : '' }}">
                    <span class="fd-ic">@if ($ok)<i class="fa-solid fa-check"></i>@else<i class="fa-solid fa-exclamation"></i>@endif</span>
                    <div><b>{{ $tit }}</b><small>{{ $txt }}</small></div>
                </div>
                @endforeach
                @endforeach
                @if ($sinImpuesto && in_array('O-48', $resp, true))
                <form method="post" action="{{ route('facturacion.impuesto') }}" class="fd-masivo" data-confirm="¿Poner este impuesto a los {{ $sinImpuesto }} productos que no tienen impuesto?">
                    @csrf
                    <span>{{ $sinImpuesto }} productos sin impuesto. Ponerles:</span>
                    <select name="impuesto" class="form-select form-select-sm" data-nativo>@foreach (\App\Support\Dian\Catalogos::IMPUESTOS as $k => $i)@if ($k !== 'ninguno')<option value="{{ $k }}">{{ $i[0] }}</option>@endif @endforeach</select>
                    <button class="btn btn-dark btn-sm">Aplicar</button>
                </form>
                @endif
                <form method="post" action="{{ route('facturacion.revisar') }}" class="mt-3">
                    @csrf
                    <button class="btn btn-primary w-100"><i class="fa-solid fa-magnifying-glass-chart me-1"></i>Revisar mi última venta como la vería la DIAN</button>
                </form>
                @if ($revision)
                <div class="fd-rev {{ $revision['errores'] ? 'mal' : 'bien' }}">
                    <b>{{ $revision['venta'] }} · {{ $revision['tipo'] === 'fe' ? 'Factura electrónica' : 'POS electrónico' }}</b>
                    <small>Base {{ dinero($revision['base']) }} + impuestos {{ dinero($revision['impuestos']) }} = {{ dinero($revision['total']) }}</small>
                    @if ($revision['errores'])
                        <ul>@foreach ($revision['errores'] as $e)<li>{{ $e }}</li>@endforeach</ul>
                    @else
                        <span><i class="fa-solid fa-circle-check me-1"></i>Cumple la estructura que pide la DIAN.</span>
                    @endif
                </div>
                @endif
            </div>

            <div class="lp-box">
                <h3><i class="fa-solid fa-file-invoice me-1"></i> Documentos electrónicos</h3>
                <div class="fd-filtros">
                    <a href="{{ route('facturacion.index') }}" class="{{ !request('estado') ? 'on' : '' }}">Todos</a>
                    @foreach (\App\Models\DocumentoElectronico::ESTADOS as $k => $e)
                        @if ($conteo[$k] ?? 0)<a href="{{ route('facturacion.index', ['estado' => $k]) }}" class="{{ request('estado') === $k ? 'on' : '' }}">{{ $e[0] }} ({{ $conteo[$k] }})</a>@endif
                    @endforeach
                </div>
                @forelse ($docs as $d)
                <div class="fd-doc">
                    <div>
                        <b>{{ $d->numero }}</b> <small class="text-muted">{{ $d->nombre_tipo }}</small>
                        <small class="d-block text-muted">{{ $d->created_at->format('d/m/Y h:i a') }} · {{ dinero($d->total) }}@if ($d->venta) · venta {{ $d->venta->numero }}@endif</small>
                        @if ($d->ultimo_error && in_array($d->estado, ['error', 'rechazado', 'pendiente'], true))<small class="d-block" style="color:#b42318">{{ \Illuminate\Support\Str::limit($d->ultimo_error, 160) }}</small>@endif
                    </div>
                    <div class="fd-doc-a">
                        <span class="fd-est" style="color:{{ $d->estado_info[1] }};background:{{ $d->estado_info[2] }}">{{ $d->estado_info[0] }}</span>
                        <a href="{{ route('facturacion.documento', $d) }}" class="btn btn-sm btn-light" title="Ver"><i class="fa-regular fa-eye"></i></a>
                        @if (in_array($d->estado, ['error', 'rechazado', 'pendiente'], true))
                        <form method="post" action="{{ route('facturacion.reintentar', $d) }}">@csrf<button class="btn btn-sm btn-dark" title="Enviar de nuevo"><i class="fa-solid fa-rotate"></i></button></form>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-muted mb-0">Aún no hay documentos electrónicos. Aparecen aquí cuando active el POS electrónico o la factura electrónica y venda.</p>
                @endforelse
                <div class="mt-2">{{ $docs->links() }}</div>
            </div>
        </div>

        <form method="post" action="{{ route('facturacion.update') }}">
            @csrf @method('PUT')
            <div class="lp-box mb-3">
                <h3><i class="fa-solid fa-id-card me-1"></i> Datos del RUT</h3>
                <div class="row g-2">
                    <div class="col-12"><label class="form-label">Razón social o nombre del titular</label><input name="razon_social" class="form-control" maxlength="120" value="{{ old('razon_social', $negocio->razon_social) }}" placeholder="Como aparece en el RUT"></div>
                    <div class="col-8"><label class="form-label">NIT (sin DV)</label><input name="nit" class="form-control" maxlength="30" value="{{ old('nit', $negocio->nit) }}" inputmode="numeric" id="fdNit"></div>
                    <div class="col-4"><label class="form-label">DV</label><input name="dv" class="form-control" maxlength="1" value="{{ old('dv', $negocio->dv) }}" inputmode="numeric" id="fdDv" placeholder="Auto"></div>
                    <div class="col-6"><label class="form-label">Tipo de persona</label>
                        <select name="tipo_persona" class="form-select"><option value="">—</option><option value="natural" @selected(old('tipo_persona', $negocio->tipo_persona) === 'natural')>Natural</option><option value="juridica" @selected(old('tipo_persona', $negocio->tipo_persona) === 'juridica')>Jurídica (empresa)</option></select></div>
                    <div class="col-6"><label class="form-label">Actividad (CIIU)</label><input name="ciiu" class="form-control" maxlength="10" value="{{ old('ciiu', $negocio->ciiu) }}" placeholder="Ej: 4771"></div>
                    <div class="col-12"><label class="form-label">Responsabilidades (casilla 53 del RUT)</label>
                        @foreach (\App\Support\Dian\Catalogos::RESPONSABILIDADES as $k => $n)
                        <div class="form-check"><input class="form-check-input" type="checkbox" name="responsabilidades[]" value="{{ $k }}" id="r{{ $k }}" @checked(in_array($k, old('responsabilidades', $resp), true))><label class="form-check-label small" for="r{{ $k }}"><b>{{ $k }}</b> · {{ $n }}</label></div>
                        @endforeach
                    </div>
                    <div class="col-12"><label class="form-label">Dirección</label><input name="direccion" class="form-control" maxlength="120" value="{{ old('direccion', $negocio->direccion) }}"></div>
                    <div class="col-6"><label class="form-label">Municipio (código DANE)</label><input name="municipio_dane" class="form-control" maxlength="5" inputmode="numeric" value="{{ old('municipio_dane', $negocio->municipio_dane) }}" placeholder="Pasto: 52001"></div>
                    <div class="col-6"><label class="form-label">Correo de facturación</label><input name="email_facturacion" type="email" class="form-control" maxlength="120" value="{{ old('email_facturacion', $negocio->email_facturacion) }}"></div>
                </div>
            </div>

            <div class="lp-box mb-3">
                <h3><i class="fa-solid fa-receipt me-1"></i> POS electrónico <small class="text-muted fw-normal">(ventas de mostrador)</small></h3>
                <input type="hidden" name="pos_electronico" value="0">
                <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="pos_electronico" value="1" id="posE" @checked(old('pos_electronico', $negocio->pos_electronico))><label class="form-check-label fw-bold" for="posE">Enviar cada venta a la DIAN como documento equivalente POS electrónico</label></div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">N.º de resolución</label><input name="resolucion_numero" class="form-control" maxlength="40" value="{{ old('resolucion_numero', $negocio->resolucion_numero) }}"></div>
                    <div class="col-6"><label class="form-label">Prefijo</label><input name="resolucion_prefijo" class="form-control" maxlength="10" value="{{ old('resolucion_prefijo', $negocio->resolucion_prefijo) }}" placeholder="Ej: POS"></div>
                    <div class="col-6"><label class="form-label">Fecha</label><input type="date" name="resolucion_fecha" class="form-control" value="{{ old('resolucion_fecha', $negocio->resolucion_fecha?->format('Y-m-d')) }}"></div>
                    <div class="col-6"><label class="form-label">Vigente hasta</label><input type="date" name="resolucion_vigencia" class="form-control" value="{{ old('resolucion_vigencia', $negocio->resolucion_vigencia?->format('Y-m-d')) }}"></div>
                    <div class="col-6"><label class="form-label">Desde</label><input type="number" min="1" name="resolucion_desde" class="form-control" value="{{ old('resolucion_desde', $negocio->resolucion_desde) }}"></div>
                    <div class="col-6"><label class="form-label">Hasta</label><input type="number" min="1" name="resolucion_hasta" class="form-control" value="{{ old('resolucion_hasta', $negocio->resolucion_hasta) }}"></div>
                </div>
                @if ($negocio->resolucion_prefijo)<small class="text-muted d-block mt-2">Próxima venta: <b>{{ $negocio->resolucion_prefijo }}-{{ max($negocio->consecutivo_pos + 1, (int) $negocio->resolucion_desde) }}</b></small>@endif
            </div>

            <div class="lp-box mb-3">
                <h3><i class="fa-solid fa-file-invoice-dollar me-1"></i> Factura electrónica <small class="text-muted fw-normal">(cuando el cliente la pide)</small></h3>
                <input type="hidden" name="fe_activa" value="0">
                <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="fe_activa" value="1" id="feA" @checked(old('fe_activa', $negocio->fe_activa))><label class="form-check-label fw-bold" for="feA">Permitir factura electrónica en la caja</label></div>
                <div class="row g-2">
                    <div class="col-6"><label class="form-label">N.º de resolución</label><input name="fe_resolucion_numero" class="form-control" maxlength="40" value="{{ old('fe_resolucion_numero', $negocio->fe_resolucion_numero) }}"></div>
                    <div class="col-6"><label class="form-label">Prefijo</label><input name="fe_prefijo" class="form-control" maxlength="10" value="{{ old('fe_prefijo', $negocio->fe_prefijo) }}" placeholder="Ej: FE"></div>
                    <div class="col-6"><label class="form-label">Fecha</label><input type="date" name="fe_resolucion_fecha" class="form-control" value="{{ old('fe_resolucion_fecha', $negocio->fe_resolucion_fecha?->format('Y-m-d')) }}"></div>
                    <div class="col-6"><label class="form-label">Vigente hasta</label><input type="date" name="fe_resolucion_vigencia" class="form-control" value="{{ old('fe_resolucion_vigencia', $negocio->fe_resolucion_vigencia?->format('Y-m-d')) }}"></div>
                    <div class="col-6"><label class="form-label">Desde</label><input type="number" min="1" name="fe_desde" class="form-control" value="{{ old('fe_desde', $negocio->fe_desde) }}"></div>
                    <div class="col-6"><label class="form-label">Hasta</label><input type="number" min="1" name="fe_hasta" class="form-control" value="{{ old('fe_hasta', $negocio->fe_hasta) }}"></div>
                    <div class="col-12"><label class="form-label">Clave técnica de la resolución</label><input type="password" name="fe_clave_tecnica" class="form-control" autocomplete="new-password" placeholder="{{ $negocio->fe_clave_tecnica ? 'Guardada · escriba solo para cambiarla' : 'La da el portal de la DIAN o el proveedor' }}"></div>
                    <div class="col-12"><label class="form-label">Ambiente</label>
                        @if ($esAdmin)
                        <select name="fe_ambiente" class="form-select"><option value="pruebas" @selected($negocio->fe_ambiente !== 'produccion')>Pruebas (habilitación)</option><option value="produccion" @selected($negocio->fe_ambiente === 'produccion')>Producción</option></select>
                        @else
                        <div class="form-control bg-light">{{ $negocio->fe_ambiente === 'produccion' ? 'Producción' : 'Pruebas (habilitación) · LocalPro lo pasa a producción cuando la DIAN lo habilite' }}</div>
                        @endif
                    </div>
                </div>
            </div>
            <button class="btn btn-primary w-100 btn-lg"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
        </form>
    </div>
</div>
<style>
.fd-top{display:flex;gap:1.2rem;align-items:center;background:#0b0d0c;color:#fff;border-radius:26px;padding:1.2rem 1.4rem;margin-bottom:1rem;flex-wrap:wrap}
.fd-anillo{--p:0;width:96px;height:96px;border-radius:50%;background:conic-gradient(#a6f34a calc(var(--p)*1%),#26302a 0);display:grid;place-items:center;flex:none;position:relative}
.fd-anillo::before{content:"";position:absolute;inset:9px;border-radius:50%;background:#0b0d0c}
.fd-anillo b,.fd-anillo small{position:relative;display:block;text-align:center;line-height:1}
.fd-anillo b{font-size:1.5rem;color:#a6f34a;margin-top:.6rem}.fd-anillo small{color:#9aa69e;font-weight:700;font-size:.7rem;margin-top:-1.4rem}
.fd-top-t{flex:1 1 260px}.fd-top-t>b{font-size:1.15rem}.fd-top-t p{color:#c9d3cc;margin:.3rem 0 .5rem;font-weight:500}.fd-top-t p b{color:#fff}
.fd-prov{display:inline-block;background:#1c2a21;color:#a6f34a;border-radius:999px;padding:.3rem .8rem;font-weight:700;font-size:.82rem}
.fd-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:1rem;align-items:start}
@media (min-width:1100px){.fd-grid{grid-template-columns:minmax(0,1.2fr) minmax(0,1fr)}}
.fd-gr{font-size:.72rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#16b34a;margin:.9rem 0 .35rem}
.fd-req{display:flex;gap:.7rem;align-items:flex-start;padding:.55rem 0;border-bottom:1px dashed #e3ebe6}
.fd-req b{display:block;font-size:.92rem}.fd-req small{color:#6b7570;font-weight:600}
.fd-ic{width:26px;height:26px;border-radius:50%;display:grid;place-items:center;flex:none;background:#fef3c7;color:#92600a;font-size:.75rem}
.fd-req.ok .fd-ic{background:#16b34a;color:#fff}
.fd-masivo{display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;background:#fff5d6;border-radius:14px;padding:.6rem .8rem;margin-top:.8rem;font-weight:700;font-size:.85rem;color:#5c4300}
.fd-masivo select{width:auto}
.fd-rev{border-radius:16px;padding:.8rem 1rem;margin-top:.8rem}.fd-rev b,.fd-rev small{display:block}.fd-rev ul{margin:.4rem 0 0;padding-left:1.1rem;font-weight:600}
.fd-rev.bien{background:#e8f8ec;color:#11702f}.fd-rev.mal{background:#fee4e2;color:#912018}
.fd-filtros{display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:.6rem}.fd-filtros a{border:1.5px solid #e3ebe6;border-radius:999px;padding:.25rem .7rem;font-size:.8rem;font-weight:700;text-decoration:none;color:#151a17}.fd-filtros a.on{background:#0b0d0c;color:#fff;border-color:#0b0d0c}
.fd-doc{display:flex;justify-content:space-between;gap:.8rem;align-items:center;padding:.65rem 0;border-bottom:1px solid #eef2ef;flex-wrap:wrap}
.fd-doc-a{display:flex;gap:.4rem;align-items:center}
.fd-est{border-radius:999px;padding:.25rem .7rem;font-weight:800;font-size:.75rem;white-space:nowrap}
</style>
@endsection

@push('js')
<script>
// Dígito de verificación del NIT (módulo 11 de la DIAN) mientras escribe
(function () {
    var n = document.getElementById('fdNit'), d = document.getElementById('fdDv');
    if (!n || !d) return;
    function dv(v) {
        v = (v || '').replace(/\D/g, ''); if (!v || v.length > 15) return '';
        var p = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71], s = 0, a = v.split('').reverse();
        for (var i = 0; i < a.length; i++) s += (+a[i]) * p[i];
        var r = s % 11; return String(r > 1 ? 11 - r : r);
    }
    n.addEventListener('input', function () { var x = dv(n.value.replace(/-\s*\d$/, '')); if (x) d.value = x; });
})();
</script>
@endpush
