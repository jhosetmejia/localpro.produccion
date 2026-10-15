@extends('layouts.app')

@php
    $nuevo = !$alquiler->exists;
    $conEnc = (bool) old('con_encargado', $alquiler->encargado_nombre ? 1 : 0);
    $conCom = (bool) old('con_comision', $alquiler->con_comision);
    $docFisico = (bool) old('doc_fisico', $alquiler->exists ? $alquiler->doc_fisico : true);
    $exigeDoc = !$alquiler->exists || ($alquiler->entrega !== 'devuelto' && $alquiler->estado !== 'cancelado');
    $puedePagar = !$alquiler->cobrado && $alquiler->estado !== 'cancelado';
    $yaAbono = (float) ($alquiler->abonado ?? 0);
    $pagoSel = old('pago', 'despues');
    $metodoSel = old('pago_metodo', optional($metodos->firstWhere('es_efectivo', true))->id ?? optional($metodos->first())->id);
    // Si hubo un error, se abre la pestaña donde está
    $erroresDe = fn (array $campos) => collect($campos)->contains(fn ($c) => $errors->has($c));
    $tabIni = $erroresDe(['que_alquila', 'lugar', 'fecha', 'hora', 'hora_fin', 'responsable_telefono', 'valor', 'nota']) ? 'evento'
        : ($erroresDe(['encargado_nombre', 'encargado_telefono', 'encargado_valor', 'encargado_nota', 'doc_tipo', 'doc_numero', 'doc_nombre', 'doc_foto']) ? 'encargado'
        : ($erroresDe(['pago', 'pago_monto', 'pago_metodo', 'comision']) ? 'pago' : 'evento'));
    $iconoMetodo = function ($m) {
        $n = mb_strtolower($m->nombre);
        if ($m->es_efectivo) return 'fa-money-bill-wave';
        if (str_contains($n, 'nequi') || str_contains($n, 'daviplata') || str_contains($n, 'movil') || str_contains($n, 'móvil')) return 'fa-mobile-screen-button';
        if (str_contains($n, 'tarjeta') || str_contains($n, 'datáfono') || str_contains($n, 'datafono')) return 'fa-credit-card';
        if (str_contains($n, 'transfer') || str_contains($n, 'banco') || str_contains($n, 'consign')) return 'fa-building-columns';
        return 'fa-wallet';
    };
@endphp
@section('title', $nuevo ? 'Crear servicio en alquiler' : 'Editar alquiler')

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-calendar-days me-1"></i>Alquiler</small>
            <h1>@if ($nuevo)Crear servicio en <span>alquiler</span>@else Editar <span>{{ $alquiler->numero }}</span>@endif</h1>
            <span class="text-muted fw-semibold">Llene las 3 pestañas: el evento, quién lo cubre y cómo paga.</span></div>
        <div class="lp-quick"><a href="{{ route('alquileres.index') }}" class="btn btn-light btn-lg"><i class="fa-solid fa-arrow-left me-1"></i>Alquiler</a></div>
    </div>
    @include('layouts.partials.lp-flash')

    <form method="post" action="{{ $nuevo ? route('alquileres.store') : route('alquileres.update', $alquiler) }}" class="lp-box lp-form-wide lp-alq" id="alqForm" enctype="multipart/form-data" novalidate>
        @csrf
        @unless ($nuevo) @method('PUT') @endunless

        @if ($alquiler->cobrado)
        <div class="alert border-0" style="background:#fff5d6;color:#8a6400"><i class="fa-solid fa-user-shield me-1"></i><b>Edición de administrador.</b> Este alquiler ya se cobró con la venta {{ $alquiler->venta?->numero }}. Si cambia el valor, también se cambia esa venta (solo si es tiquete y la caja sigue abierta).</div>
        @endif

        {{-- Pestañas --}}
        <div class="lp-tabs" role="tablist">
            <button type="button" class="lp-tab" role="tab" data-tab="evento">
                <span class="n">1</span><span class="tx"><b><i class="fa-solid fa-calendar-days"></i> Evento</b><small data-res="evento">Qué, cuándo, dónde y valor</small></span>
            </button>
            <button type="button" class="lp-tab" role="tab" data-tab="encargado">
                <span class="n">2</span><span class="tx"><b><i class="fa-solid fa-user-gear"></i> Encargado y respaldo</b><small data-res="encargado">Encargado o documento</small></span>
            </button>
            <button type="button" class="lp-tab" role="tab" data-tab="pago">
                <span class="n">3</span><span class="tx"><b><i class="fa-solid fa-cash-register"></i> Pago</b><small data-res="pago">Cómo paga y ganancia</small></span>
            </button>
        </div>

        {{-- 1. EVENTO --}}
        <section class="lp-panel" data-panel="evento">
            <div class="row g-3">
                <div class="col-lg-6">
                    <label class="form-label fw-bold" for="queAlq"><i class="fa-solid fa-tag me-1"></i> ¿Qué se alquila? *</label>
                    <input id="queAlq" name="que_alquila" class="form-control form-control-lg" maxlength="120" required autofocus
                        placeholder="Ej: Sonido para fiesta, salón de eventos, carpa 6x6…" value="{{ old('que_alquila', $alquiler->que_alquila) }}">
                    @error('que_alquila')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="col-lg-6">
                    <label class="form-label fw-bold" for="lugarAlq"><i class="fa-solid fa-location-dot me-1"></i> Lugar *</label>
                    <input id="lugarAlq" name="lugar" class="form-control form-control-lg" maxlength="150" required placeholder="Dirección o sitio donde se presta el servicio" value="{{ old('lugar', $alquiler->lugar) }}">
                    @error('lugar')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="col-12 col-sm-4 col-lg-2">
                    <label class="form-label fw-bold" for="fechaAlq">Fecha *</label>
                    <input id="fechaAlq" type="date" name="fecha" class="form-control" required value="{{ old('fecha', optional($alquiler->fecha)->format('Y-m-d')) }}">
                </div>
                <div class="col-6 col-sm-4 col-lg-2">
                    <label class="form-label fw-bold" for="horaIni">Hora de inicio *</label>
                    <input type="time" name="hora" id="horaIni" class="form-control" required value="{{ old('hora', $alquiler->hora ? substr($alquiler->hora, 0, 5) : '') }}">
                </div>
                <div class="col-6 col-sm-4 col-lg-2">
                    <label class="form-label fw-bold" for="horaFin">Hora de fin</label>
                    <input type="time" name="hora_fin" id="horaFin" class="form-control" value="{{ old('hora_fin', $alquiler->hora_fin ? substr($alquiler->hora_fin, 0, 5) : '') }}">
                    @error('hora_fin')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label fw-bold" for="cliTelAlq">Celular de quien contrata *</label>
                    <input id="cliTelAlq" type="tel" name="responsable_telefono" class="form-control" required data-celular value="{{ old('responsable_telefono', $alquiler->responsable_telefono) }}">
                    @error('responsable_telefono')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label fw-bold" for="valor">Valor del alquiler *</label>
                    <input name="valor" id="valor" type="number" min="0" class="form-control" required data-dinero placeholder="0" value="{{ old('valor', $alquiler->exists ? $alquiler->valor : '') }}">
                    @error('valor')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold" for="notaCli">Nota para el cliente <span class="text-muted fw-normal">(opcional · sale en su factura)</span></label>
                    <textarea id="notaCli" name="nota" class="form-control" rows="1" maxlength="255" placeholder="Ej: el valor incluye transporte, montaje y desmontaje…">{{ old('nota', $alquiler->nota) }}</textarea>
                </div>
            </div>
        </section>

        {{-- 2. ENCARGADO --}}
        <section class="lp-panel" data-panel="encargado" hidden>
            <div class="lp-sw-card">
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="conEnc" name="con_encargado" value="1" @checked($conEnc)>
                    <label class="form-check-label fw-bold" for="conEnc">Asignar un encargado de nuestro lado</label>
                </div>
                <small class="text-muted">La persona de su negocio que va a cubrir el evento. Si no aplica, déjelo apagado.</small>
            </div>
            <div class="row g-3 mt-0 lp-enc {{ $conEnc ? '' : 'd-none' }}">
                <div class="col-sm-6 col-lg-4">
                    <label class="form-label fw-bold" for="encNom">Nombre del encargado *</label>
                    <input id="encNom" name="encargado_nombre" class="form-control" maxlength="80" placeholder="Ej: Carlos" value="{{ old('encargado_nombre', $alquiler->encargado_nombre) }}">
                    @error('encargado_nombre')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="col-sm-6 col-lg-4">
                    <label class="form-label fw-bold" for="encTel">Celular del encargado</label>
                    <input id="encTel" type="tel" name="encargado_telefono" class="form-control" data-celular value="{{ old('encargado_telefono', $alquiler->encargado_telefono) }}">
                    @error('encargado_telefono')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="col-sm-6 col-lg-4">
                    <label class="form-label fw-bold" for="encVal">Valor del turno <span class="text-muted fw-normal">(se descuenta de su ganancia)</span></label>
                    <input id="encVal" name="encargado_valor" type="number" min="0" class="form-control" data-dinero placeholder="0" value="{{ old('encargado_valor', $alquiler->encargado_valor > 0 ? $alquiler->encargado_valor : '') }}">
                    @error('encargado_valor')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold" for="encNota">Nota para el encargado <span class="text-muted fw-normal">(le llega en el WhatsApp · el cliente no la ve)</span></label>
                    <textarea id="encNota" name="encargado_nota" class="form-control" rows="1" maxlength="255" placeholder="Ej: llegar con ropa negra, llevar extensión, preguntar por Andrea…">{{ old('encargado_nota', $alquiler->encargado_nota) }}</textarea>
                </div>
                <div class="col-12">
                    <div class="lp-wa-box">
                        <button type="button" class="btn btn-success btn-sm" id="encWa"><i class="fa-brands fa-whatsapp me-1"></i>Enviar detalles del evento al encargado</button>
                        <small class="text-muted">Le llega por WhatsApp con fecha, horario, lugar, valor del turno, su nota y el aviso de estar <b>1 hora antes</b> por el tráfico.</small>
                    </div>
                </div>
            </div>

            {{-- Documento de respaldo: obligatorio si el elemento sale sin encargado del negocio --}}
            <div class="lp-doc mt-3" id="docBox" data-exige="{{ $exigeDoc ? 1 : 0 }}">
                <div class="lp-doc-head">
                    <div class="ic"><i class="fa-solid fa-id-card"></i></div>
                    <div>
                        <b>Documento de respaldo <span class="lp-doc-badge" id="docBadge"></span></b>
                        <small id="docAyuda" class="d-block text-muted"></small>
                    </div>
                </div>
                <div class="row g-3 mt-0">
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label fw-bold" for="docTipo">Tipo de documento <span class="doc-ast">*</span></label>
                        <select id="docTipo" name="doc_tipo" class="form-select" data-nativo>
                            <option value="">Elija…</option>
                            @foreach (\App\Models\Alquiler::DOC_TIPOS as $k => $v)
                            <option value="{{ $k }}" @selected(old('doc_tipo', $alquiler->doc_tipo ?? ($alquiler->exists ? null : 'cedula')) === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('doc_tipo')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label fw-bold" for="docNum">Número <span class="doc-ast">*</span></label>
                        <input id="docNum" name="doc_numero" class="form-control" maxlength="30" inputmode="text" placeholder="Ej: 1085123456" value="{{ old('doc_numero', $alquiler->doc_numero) }}">
                        @error('doc_numero')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-sm-12 col-lg-6">
                        <label class="form-label fw-bold" for="docNom">A nombre de <span class="doc-ast">*</span></label>
                        <input id="docNom" name="doc_nombre" class="form-control" maxlength="80" placeholder="Nombre completo de quien deja el documento" value="{{ old('doc_nombre', $alquiler->doc_nombre) }}">
                        @error('doc_nombre')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label fw-bold" for="docFoto">Foto o PDF del documento <span class="text-muted fw-normal">(opcional · solo lo ve su negocio)</span></label>
                        <input id="docFoto" name="doc_foto" type="file" class="form-control" accept="image/*,application/pdf" capture="environment">
                        @error('doc_foto')<small class="text-danger">{{ $message }}</small>@enderror
                        @if ($alquiler->doc_archivo)
                        <small class="d-block mt-1"><a href="{{ route('alquileres.documento', $alquiler) }}" target="_blank" class="fw-bold"><i class="fa-solid fa-paperclip me-1"></i>Ver documento guardado</a> · si sube otro, se reemplaza.</small>
                        @endif
                    </div>
                    <div class="col-sm-6 d-flex align-items-end">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="docFis" name="doc_fisico" value="1" @checked($docFisico)>
                            <label class="form-check-label fw-bold" for="docFis">Dejó el documento físico en el local</label>
                            <small class="d-block text-muted">Al devolver el alquiler le recordamos entregárselo.</small>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- 3. PAGO --}}
        <section class="lp-panel" data-panel="pago" hidden>
          <div class="lp-pago-grid">
            <div class="lp-pago-izq">
            @if ($puedePagar)
            <div class="lp-pago" id="lpPago" data-abonado="{{ $yaAbono }}">
                <div class="lp-pago-top">
                    <label class="form-label fw-bold mb-0">¿Cómo paga el cliente?</label>
                    @if ($yaAbono > 0)<small class="lp-pago-ya">Ya abonó <b>{{ dinero($yaAbono) }}</b> · falta <b>{{ dinero($alquiler->saldo) }}</b></small>@endif
                </div>
                <div class="lp-opc3" role="radiogroup">
                    <input type="radio" class="btn-check" name="pago" id="pagoDesp" value="despues" @checked($pagoSel === 'despues')>
                    <label for="pagoDesp"><i class="fa-regular fa-clock"></i><span><b>Paga después</b><small>Lo cobra desde la lista</small></span></label>
                    <input type="radio" class="btn-check" name="pago" id="pagoComp" value="completo" @checked($pagoSel === 'completo')>
                    <label for="pagoComp"><i class="fa-solid fa-circle-check"></i><span><b>{{ $yaAbono > 0 ? 'Paga lo que falta' : 'Paga completo' }}</b><small>Queda pagado y facturado</small></span></label>
                    <input type="radio" class="btn-check" name="pago" id="pagoAbo" value="abono" @checked($pagoSel === 'abono')>
                    <label for="pagoAbo"><i class="fa-solid fa-hand-holding-dollar"></i><span><b>{{ $yaAbono > 0 ? 'Abona otra parte' : 'Abona' }}</b><small>Deja una parte y el resto después</small></span></label>
                </div>

                <div id="pagoCampos" class="mt-3">
                    <div id="pagoMontoBox">
                        <label class="form-label fw-bold" for="pagoMonto">¿Cuánto abona? *</label>
                        <input name="pago_monto" id="pagoMonto" type="number" min="1" class="form-control form-control-lg" data-dinero placeholder="0" value="{{ old('pago_monto') }}">
                    </div>
                    <div class="lp-met-col">
                    <label class="form-label fw-bold">¿Cómo le pagaron? *</label>
                    <div class="lp-metodos" role="radiogroup">
                        @foreach ($metodos as $m)
                        <input type="radio" class="btn-check" name="pago_metodo" id="pm{{ $m->id }}" value="{{ $m->id }}" data-efectivo="{{ $m->es_efectivo ? 1 : 0 }}" data-nombre="{{ $m->nombre }}" @checked($metodoSel == $m->id)>
                        <label for="pm{{ $m->id }}"><i class="fa-solid {{ $iconoMetodo($m) }}"></i> {{ $m->nombre }}</label>
                        @endforeach
                    </div>
                    <small class="lp-metodo-ayuda" id="metodoAyuda"></small>
                    </div>
                </div>

                <div class="lp-pago-res mt-3" id="pagoRes">
                    <div><small>Cobra ahora</small><b id="resCobra">$0</b></div>
                    <div class="falta"><small>Queda debiendo</small><b id="resFalta">$0</b></div>
                </div>
                @unless ($cajaAbierta)
                <small class="lp-pago-aviso" id="pagoAviso"><i class="fa-solid fa-triangle-exclamation me-1"></i>Para recibir el pago aquí, primero <a href="{{ route('cajas.abrir') }}">abra su caja</a>. Mientras tanto guárdelo como "Paga después".</small>
                @endunless
                @error('pago')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                @error('pago_monto')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
                @error('pago_metodo')<small class="text-danger d-block mt-1">{{ $message }}</small>@enderror
            </div>
            @elseif ($alquiler->cobrado)
            @php
                $venta = $alquiler->venta;
                $pagosVenta = $venta ? $venta->pagos()->with('metodo')->get() : collect();
                $abonos = $alquiler->abonos()->with('metodo')->where('anulado', false)->get();
            @endphp
            <div class="lp-pagado">
                <div class="ic"><i class="fa-solid fa-circle-check"></i></div>
                <div class="t">
                    <b>Pagado completo · {{ dinero($alquiler->valor) }}</b>
                    <small>Venta {{ $venta?->numero }}@if ($alquiler->cobrado_en) · {{ $alquiler->cobrado_en->format('d/m/Y g:i a') }}@endif</small>
                    @if ($abonos->isNotEmpty())
                        <ul>@foreach ($abonos as $ab)<li>{{ $ab->numero }} · {{ $ab->created_at->format('d/m/Y') }} · {{ $ab->metodo?->nombre }} · <b>{{ dinero($ab->monto) }}</b></li>@endforeach</ul>
                    @else
                        <ul>@foreach ($pagosVenta as $p)<li><i class="fa-solid {{ $p->metodo ? $iconoMetodo($p->metodo) : 'fa-wallet' }} me-1"></i>{{ $p->metodo?->nombre }} · <b>{{ dinero($p->monto) }}</b>@if ($p->metodo?->es_efectivo) <span class="text-muted">(entró como efectivo a la caja)</span>@endif</li>@endforeach</ul>
                    @endif
                </div>
            </div>
            @else
            <div class="lp-pagado off"><div class="ic"><i class="fa-solid fa-ban"></i></div><div class="t"><b>Alquiler cancelado</b><small>No recibe pagos.</small></div></div>
            @endif

            </div>

            {{-- Ganancia y comisión --}}
            <div class="lp-costos">
                <div class="lp-cuenta-gan" id="boxGan">
                    <div><span>Valor del alquiler</span><b id="gVal">$0</b></div>
                    <div class="menos"><span>− Comisión</span><b id="gCom">$0</b></div>
                    <div class="menos"><span>− Turno del encargado</span><b id="gTur">$0</b></div>
                    <div class="tot"><span>Ganancia del local</span><b id="ganancia">$0</b></div>
                </div>
                <div class="lp-sw-card">
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="conCom" name="con_comision" value="1" @checked($conCom)>
                        <label class="form-check-label fw-bold" for="conCom">Se paga comisión</label>
                    </div>
                    <small class="text-muted">Al dueño del equipo, a quien consiguió el cliente…</small>
                    <div id="boxCom" class="mt-2 {{ $conCom ? '' : 'd-none' }}">
                        <label class="form-label fw-bold" for="comision">Valor de la comisión *</label>
                        <input name="comision" id="comision" type="number" min="0" class="form-control" data-dinero placeholder="0" value="{{ old('comision', $alquiler->con_comision ? $alquiler->comision : '') }}">
                        @error('comision')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>
            </div>
          </div>
        </section>

        <div class="lp-form-actions sticky lp-alq-acc">
            <button type="button" class="btn btn-light" id="tabAtras"><i class="fa-solid fa-arrow-left me-1"></i>Atrás</button>
            <button type="button" class="btn btn-dark" id="tabSig">Siguiente<i class="fa-solid fa-arrow-right ms-1"></i></button>
            <span class="flex-grow-1"></span>
            <a href="{{ route('alquileres.index') }}" class="btn btn-light btn-lg">Cancelar</a>
            <button class="btn btn-primary btn-lg" id="alqGuardar"><i class="fa-solid fa-floppy-disk me-1"></i>{{ $nuevo ? 'Crear alquiler' : 'Guardar cambios' }}</button>
        </div>
    </form>

    @include('alquiler.corregir-pago')
</div>
@endsection

@push('css')
<style>
.lp-doc{border:1.5px solid var(--lp-line,#e3efe5);border-radius:18px;padding:.9rem 1rem;background:#fff}
.lp-doc.obligatorio{border-color:#f5b6cf;background:#fff7fb}
.lp-doc-head{display:flex;gap:.7rem;align-items:center}
.lp-doc-head .ic{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;background:#e8f8ec;color:#16b34a;flex:none}
.lp-doc.obligatorio .lp-doc-head .ic{background:#ffe9f3;color:#d61f69}
.lp-doc-badge{font-size:.72rem;font-weight:800;border-radius:99px;padding:.15rem .55rem;margin-left:.3rem;vertical-align:middle;background:#eef1ef;color:#45504a}
.lp-doc.obligatorio .lp-doc-badge{background:#d61f69;color:#fff}
.lp-doc:not(.obligatorio) .doc-ast{display:none}
</style>
@endpush

@push('js')
<script>
(function () {
    var form = document.getElementById('alqForm');
    var $ = function (id) { return document.getElementById(id); };
    var M = window.LP_MONEDA || { miles: '.', decimales: 0 };
    function num(el) { if (!el) return 0; var v = parseFloat(String(el.value || '0').replace(/[^0-9.\-]/g, '')); return isNaN(v) ? 0 : v; }
    function fmt(n) { return '$' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, M.miles || '.'); }
    function val(n) { var e = form.elements[n]; return e ? String(e.value || '').trim() : ''; }
    function h12(t) { if (!t) return ''; var p = t.split(':'), hh = +p[0], ap = hh >= 12 ? 'pm' : 'am'; hh = hh % 12 || 12; return hh + ':' + p[1] + ' ' + ap; }

    // ---------- Pestañas ----------
    var orden = ['evento', 'encargado', 'pago'], actual = @json($tabIni);
    function ir(t) {
        actual = t;
        form.querySelectorAll('.lp-tab').forEach(function (b) { var on = b.dataset.tab === t; b.classList.toggle('on', on); b.setAttribute('aria-selected', on); });
        form.querySelectorAll('.lp-panel').forEach(function (p) { p.hidden = p.dataset.panel !== t; });
        var i = orden.indexOf(t);
        $('tabAtras').style.display = i > 0 ? '' : 'none';
        $('tabSig').style.display = i < orden.length - 1 ? '' : 'none';
        form.querySelectorAll('[data-panel="' + t + '"] textarea').forEach(function (x) { x.style.height = 'auto'; x.style.height = Math.min(x.scrollHeight + 2, 140) + 'px'; });
        resumen();
    }
    form.querySelectorAll('.lp-tab').forEach(function (b) { b.addEventListener('click', function () { ir(b.dataset.tab); }); });
    $('tabSig').addEventListener('click', function () {
        if (!validar(actual)) return;
        ir(orden[orden.indexOf(actual) + 1]);
    });
    $('tabAtras').addEventListener('click', function () { ir(orden[orden.indexOf(actual) - 1]); });

    // Revisa los campos obligatorios de una pestaña (o de todas) y lleva al primero que falte
    function visibleDe(el) { return el.type === 'hidden' && el.nextElementSibling && el.nextElementSibling.hasAttribute('data-dinero-vista') ? el.nextElementSibling : el; }
    function validar(tab) {
        var campos = form.querySelectorAll((tab ? '[data-panel="' + tab + '"] ' : '') + '[required]');
        for (var i = 0; i < campos.length; i++) {
            var c = campos[i];
            if (c.closest('.d-none')) continue;
            var vacio = !String(c.value || '').trim();
            if (vacio || (c.checkValidity && !c.checkValidity())) {
                var p = c.closest('.lp-panel'); if (p && p.dataset.panel !== actual) ir(p.dataset.panel);
                var vis = visibleDe(c); vis.classList.add('is-invalid'); vis.focus();
                var lbl = form.querySelector('label[for="' + c.id + '"]');
                var msg = 'Falta: ' + (lbl ? lbl.textContent.replace(/[*(].*$/, '').trim() : 'un campo obligatorio');
                (window.LP && LP.toast) ? LP.toast(msg, 'error') : alert(msg);
                return false;
            }
        }
        return true;
    }
    form.addEventListener('input', function (e) { var t = e.target; t.classList.remove('is-invalid'); }, true);
    form.addEventListener('submit', function (e) { if (!validar(null)) e.preventDefault(); });

    // ---------- Resumen en cada pestaña ----------
    function resumen() {
        var fe = val('fecha').split('-').reverse().join('/');
        var r1 = [val('que_alquila'), fe, h12(val('hora')) + (val('hora_fin') ? ' a ' + h12(val('hora_fin')) : '')].filter(Boolean).join(' · ');
        form.querySelector('[data-res="evento"]').textContent = r1 || 'Qué, cuándo, dónde y valor';
        var enc = $('conEnc').checked;
        var docTxt = val('doc_numero') ? ' · 🪪 ' + val('doc_numero') : '';
        form.querySelector('[data-res="encargado"]').textContent = enc ? ((val('encargado_nombre') || 'Sin nombre') + (num($('encVal')) > 0 ? ' · turno ' + fmt(num($('encVal'))) : '') + docTxt) : (docTxt ? 'Sin encargado' + docTxt : 'Sin encargado: falta documento');
        var sel = (form.querySelector('input[name=pago]:checked') || {}).value;
        var met = (form.querySelector('input[name=pago_metodo]:checked') || {}).dataset;
        var r3 = @json($alquiler->cobrado) ? 'Pagado completo' : (sel === 'completo' ? 'Paga completo' + (met ? ' · ' + met.nombre : '') : sel === 'abono' ? 'Abona ' + fmt(num($('pagoMonto'))) + (met ? ' · ' + met.nombre : '') : 'Paga después');
        form.querySelector('[data-res="pago"]').textContent = r3;
    }

    // ---------- Encargado ----------
    var enc = $('conEnc');
    function verEnc() {
        form.querySelectorAll('.lp-enc').forEach(function (e) { e.classList.toggle('d-none', !enc.checked); });
        $('encNom').required = enc.checked;
        // Sin encargado del negocio: el documento de respaldo es obligatorio
        var oblig = !enc.checked && $('docBox').dataset.exige === '1';
        $('docBox').classList.toggle('obligatorio', oblig);
        ['docTipo', 'docNum', 'docNom'].forEach(function (id) { $(id).required = oblig; });
        $('docBadge').textContent = oblig ? 'Obligatorio' : 'Opcional';
        $('docAyuda').textContent = oblig
            ? 'El elemento sale sin un encargado de su negocio: pida un documento que respalde el alquiler (cédula, licencia…).'
            : 'Va un encargado de su negocio con el alquiler. Si quiere, igual puede pedir un documento.';
    }
    enc.addEventListener('change', function () { verEnc(); calc(); resumen(); });
    verEnc();

    $('encWa').addEventListener('click', function () {
        var tel = val('encargado_telefono').replace(/\D/g, ''), ind = String(M.indicativo || '57');
        if (!tel) { (window.LP && LP.toast) ? LP.toast('Escriba el celular del encargado.', 'error') : alert('Escriba el celular del encargado.'); $('encTel').focus(); return; }
        if (!(tel.indexOf(ind) === 0 && tel.length > 10)) tel = ind + tel.replace(/^0+/, '');
        var ini = val('hora'), fin = val('hora_fin'), fe = val('fecha').split('-').reverse().join('/'), lleg = '';
        if (ini) { var p = ini.split(':'), d = new Date(2000, 0, 1, +p[0], +p[1]); d.setHours(d.getHours() - 1); lleg = h12(('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2)); }
        var turno = num($('encVal')), numero = @json($alquiler->numero);
        var l = ['Hola ' + val('encargado_nombre') + ' 👋 Le comparto los detalles del evento' + (numero ? ' (' + numero + ')' : '') + ':', '',
            '📦 *Servicio:* ' + val('que_alquila'), '📅 *Fecha:* ' + fe, '🕐 *Horario:* ' + h12(ini) + (fin ? ' a ' + h12(fin) : ''),
            '📍 *Lugar:* ' + val('lugar'), '📞 *Cliente:* ' + val('responsable_telefono')];
        if (turno > 0) l.push('💵 *Valor de su turno:* ' + fmt(turno));
        if (val('encargado_nota')) l.push('📝 *Indicaciones:* ' + val('encargado_nota'));
        l.push('', '⚠️ Por favor esté *1 hora antes*' + (lleg ? ' (' + lleg + ')' : '') + ' para evitar retrasos por el tráfico.', '— ' + @json(\App\Models\Negocio::datos()->nombre ?? ''));
        window.open('https://wa.me/' + tel + '?text=' + encodeURIComponent(l.join('\n')), '_blank');
    });

    // ---------- Comisión y ganancia ----------
    var chk = $('conCom'), com = $('comision');
    function calc() {
        var v = num($('valor')), c = chk.checked ? num(com) : 0, t = enc.checked ? num($('encVal')) : 0, g = v - c - t;
        $('boxCom').classList.toggle('d-none', !chk.checked);
        com.required = chk.checked;
        $('gVal').textContent = fmt(v); $('gCom').textContent = fmt(c); $('gTur').textContent = fmt(t);
        $('gCom').parentNode.style.display = c > 0 ? '' : 'none';
        $('gTur').parentNode.style.display = t > 0 ? '' : 'none';
        var out = $('ganancia'); out.textContent = fmt(g); out.style.color = g < 0 ? '#ff6f9c' : '#2fd15a';
    }
    chk.addEventListener('change', calc);

    // ---------- ¿Cómo paga? ----------
    var box = $('lpPago');
    function pago() {
        if (!box) return;
        var ya = parseFloat(box.dataset.abonado || '0') || 0, monto = $('pagoMonto');
        var sel = (box.querySelector('input[name=pago]:checked') || {}).value || 'despues';
        var falta = Math.max(0, num($('valor')) - ya), cobra = 0;
        $('pagoCampos').style.display = sel === 'despues' ? 'none' : '';
        $('pagoMontoBox').style.display = sel === 'abono' ? '' : 'none';
        monto.required = sel === 'abono';
        if (sel === 'completo') cobra = falta;
        if (sel === 'abono') cobra = Math.min(num(monto), falta);
        $('resCobra').textContent = fmt(cobra);
        $('resFalta').textContent = fmt(falta - cobra);
        $('pagoRes').classList.toggle('ok', falta - cobra <= 0 && sel !== 'despues');
        visibleDe(monto).classList.toggle('is-invalid', sel === 'abono' && num(monto) > falta);
        box.dataset.sel = sel;
        var m = box.querySelector('input[name=pago_metodo]:checked'), ay = $('metodoAyuda');
        ay.innerHTML = !m ? '' : (m.dataset.efectivo === '1'
            ? '<i class="fa-solid fa-cash-register me-1"></i>Este dinero entra en <b>efectivo a su caja</b>: debe estar cuando haga el cierre.'
            : '<i class="fa-solid fa-mobile-screen-button me-1"></i>Queda registrado como pagado por <b>' + m.dataset.nombre + '</b> (a la cuenta del negocio). No suma al efectivo de la caja.');
    }
    if (box) box.addEventListener('change', pago);

    document.addEventListener('input', function () { setTimeout(function () { calc(); pago(); resumen(); }, 0); }, true);
    document.addEventListener('change', function () { setTimeout(function () { calc(); pago(); resumen(); }, 0); }, true);

    document.querySelectorAll('textarea[name=nota], textarea[name=encargado_nota]').forEach(function (t) {
        function crecer() { t.style.height = 'auto'; t.style.height = Math.min(t.scrollHeight + 2, 140) + 'px'; }
        t.addEventListener('input', crecer); setTimeout(crecer, 0);
    });

    calc(); pago(); ir(actual);
})();
</script>
@endpush
