{{-- Ficha odontológica del paciente: odontograma, planes de tratamiento, consentimientos y radiografías --}}
@php
    $odo = $clinico ? $paciente->odontogramas()->first() : null;
    $planes = $paciente->tratamientos()->with('items')->get();
    $consent = $clinico ? $paciente->consentimientos()->get(['id', 'titulo', 'procedimiento', 'firmado_at', 'firmante_nombre']) : collect();
    $adjuntos = $clinico ? $paciente->adjuntos()->get() : collect();
    $saldoTotal = $planes->whereIn('estado', ['aprobado', 'terminado'])->sum(fn ($x) => $x->saldo);
@endphp
<style>
.fo-cab{display:flex;justify-content:space-between;align-items:center;gap:.8rem;flex-wrap:wrap;margin-bottom:.6rem}
.fo-acc{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-top:.8rem}
.fo-acc form{margin:0}
.fo-acc a,.fo-acc button{display:flex;flex-direction:column;align-items:flex-start;gap:.2rem;border:1.5px solid #d6eef3;background:linear-gradient(180deg,#fff,#f5fdff);border-radius:18px;padding:.8rem .9rem;text-decoration:none;color:var(--lp-ink);font-weight:800;text-align:left;width:100%}
.fo-acc a:hover,.fo-acc button:hover{border-color:#67e8f9;color:var(--lp-ink)}
.fo-acc i{font-size:1.25rem;color:#0891b2}
.fo-acc small{font-weight:600;color:var(--lp-muted);font-size:.74rem}
.fo-mini{overflow-x:auto;border:1.5px dashed #d6eef3;border-radius:18px;padding:.4rem .6rem;background:#fcfeff}
.fo-mini .odo-svg{min-width:520px}
.fo-mini .odo-cara{stroke:#94a3b8;stroke-width:1.1}.fo-mini .odo-num{font:800 11px system-ui,sans-serif;fill:#475569}.fo-mini .odo-sim{font:900 9px system-ui,sans-serif;fill:#fff}.fo-mini .odo-medio{stroke:#cbd5e1;stroke-dasharray:3 4}
.fo-plan{display:flex;gap:.8rem;align-items:center;padding:.7rem 0;border-bottom:1px solid #eef3ef;text-decoration:none;color:inherit}
.fo-plan:last-child{border-bottom:0}.fo-plan .tx{flex:1;min-width:0}.fo-plan small{display:block;color:var(--lp-muted);font-weight:600}
.fo-bar{height:6px;border-radius:9px;background:#e2f3f6;overflow:hidden;margin-top:.3rem}.fo-bar i{display:block;height:100%;background:linear-gradient(90deg,#22d3ee,#0891b2)}
.fo-gal{display:grid;grid-template-columns:repeat(auto-fill,minmax(96px,1fr));gap:8px}
.fo-gal a{position:relative;display:block;aspect-ratio:1;border-radius:14px;overflow:hidden;background:#0f172a;border:1.5px solid #d6eef3}
.fo-gal img{width:100%;height:100%;object-fit:cover}
.fo-gal .doc{display:grid;place-items:center;height:100%;color:#a5f3fc;font-size:1.6rem;background:#164e63}
.fo-gal span{position:absolute;left:4px;right:4px;bottom:4px;background:rgba(8,51,68,.75);color:#fff;font-size:.62rem;font-weight:800;border-radius:8px;padding:.1rem .35rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.fo-con{display:flex;justify-content:space-between;gap:.5rem;padding:.5rem 0;border-bottom:1px dashed #e2eef1;text-decoration:none;color:inherit;font-size:.88rem}
@media (max-width:767px){.fo-acc{grid-template-columns:repeat(2,1fr)}}
</style>
<div class="cs-card mt-3" style="border-color:#a5f3fc;background:linear-gradient(180deg,#f5feff,#fff 120px)">
    <div class="fo-cab">
        <h3 class="m-0"><span><i class="fa-solid fa-tooth me-1" style="color:#0891b2"></i>Ficha odontológica</span></h3>
        @if ($saldoTotal > 0)<span class="cs-est" style="background:#e11d48;font-size:.8rem">Saldo pendiente {{ dinero($saldoTotal) }}</span>@endif
    </div>
    @if ($clinico)
        @if ($odo)
        <a href="{{ route('odonto.odontograma', $paciente) }}" class="fo-mini d-block text-decoration-none" title="Abrir odontograma"><div id="fo-mini"></div></a>
        <div class="small text-muted mt-1">Último odontograma: {{ $odo->created_at->format('d/m/Y') }} · {{ $odo->profesional_nombre }}</div>
        @endif
        <div class="fo-acc">
            <a href="{{ route('odonto.odontograma', $paciente) }}"><i class="fa-solid fa-tooth"></i>{{ $odo ? 'Odontograma' : 'Hacer odontograma' }}<small>Caries, resinas, ausentes, coronas…</small></a>
            <form method="post" action="{{ route('odonto.plan.crear', $paciente) }}">@csrf<button><i class="fa-solid fa-file-invoice-dollar"></i>Nuevo plan<small>Presupuesto por pieza con abonos</small></button></form>
            <a href="{{ route('odonto.consentimiento.nuevo', $paciente) }}"><i class="fa-solid fa-file-signature"></i>Consentimiento<small>Firma en la pantalla</small></a>
            <a href="#fo-adj" onclick="document.getElementById('fo-archivos').click();return false;"><i class="fa-solid fa-x-ray"></i>Radiografías y fotos<small>Suba desde el celular o el PC</small></a>
            <a href="{{ route('odonto.perio', $paciente) }}"><i class="fa-solid fa-teeth"></i>Periodontograma<small>Bolsas, sangrado, movilidad</small></a>
            <form method="post" action="{{ route('odonto.plan.crear', $paciente) }}">@csrf<input type="hidden" name="tipo" value="ortodoncia"><button><i class="fa-solid fa-teeth-open"></i>Ortodoncia<small>Cuotas mensuales y controles</small></button></form>
            <a href="{{ route('odonto.laboratorio', ['paciente' => $paciente->id]) }}#nueva"><i class="fa-solid fa-flask"></i>Enviar a laboratorio<small>Coronas, prótesis, placas</small></a>
            <form method="post" action="{{ route('consultorio.atencion.nueva', $paciente) }}">@csrf<button><i class="fa-solid fa-prescription"></i>Atención y receta<small>Recetas dentales listas</small></button></form>
        </div>
    @endif

    <div class="mt-3">
        <h3>Planes de tratamiento <small>{{ $planes->count() }}</small></h3>
        @forelse ($planes as $pl)
        @php [$en, $ec] = \App\Models\TratamientoPlan::ESTADOS[$pl->estado]; @endphp
        <a href="{{ route('odonto.plan', $pl) }}" class="fo-plan">
            <div class="cs-av" style="background:#e0f7fa;color:#0e7490"><i class="fa-solid {{ $pl->es_ortodoncia ? 'fa-teeth-open' : 'fa-file-invoice-dollar' }}"></i></div>
            <div class="tx"><b>{{ $pl->numero }} · {{ $pl->nombre }}</b>
                <small>@if ($pl->es_ortodoncia && $pl->atraso > 0)<b style="color:#e11d48">{{ $pl->cuotas_atrasadas }} cuota(s) atrasada(s) · </b>@endif{{ $pl->items->count() }} procedimiento(s) · Total {{ dinero($pl->total) }} · Abonado {{ dinero($pl->abonado) }}{{ $pl->saldo > 0 ? ' · Debe ' . dinero($pl->saldo) : '' }}</small>
                <div class="fo-bar"><i style="width:{{ $pl->avance }}%"></i></div></div>
            <span class="cs-est" style="background:{{ $ec }}">{{ $en }}</span>
        </a>
        @empty
        <div class="text-muted small">Sin planes de tratamiento.{{ $clinico ? ' Haga el odontograma y toque "Pasar al plan de tratamiento", o cree uno con "Nuevo plan".' : '' }}</div>
        @endforelse
    </div>

    @if ($clinico)
    <div class="mt-3">
        <h3>Consentimientos firmados <small>{{ $consent->count() }}</small></h3>
        @forelse ($consent as $c)
        <a href="{{ route('odonto.consentimiento', $c) }}" target="_blank" class="fo-con"><span><i class="fa-solid fa-file-signature me-1" style="color:#0891b2"></i><b>{{ $c->titulo }}</b>{{ $c->procedimiento ? ' · ' . $c->procedimiento : '' }}</span><small class="text-muted text-nowrap">{{ $c->firmado_at?->format('d/m/Y') }}</small></a>
        @empty<div class="text-muted small">Ninguno todavía.</div>@endforelse
    </div>

    <div class="mt-3" id="fo-adj">
        <h3>Radiografías, fotos y documentos <small>{{ $adjuntos->count() }}</small></h3>
        <form method="post" action="{{ route('odonto.adjunto.subir', $paciente) }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-center mb-2" id="fo-subir">
            @csrf
            <select name="tipo" class="form-select form-select-sm" data-nativo style="width:auto;border-radius:999px;font-weight:700">
                @foreach (\App\Models\PacienteAdjunto::TIPOS as $k => [$n])<option value="{{ $k }}">{{ $n }}</option>@endforeach
            </select>
            <input name="titulo" class="form-control form-control-sm" maxlength="120" placeholder="Título (opcional), ej. Periapical 36" style="max-width:260px;border-radius:999px">
            <input type="file" name="archivos[]" id="fo-archivos" accept="image/*,application/pdf" multiple class="d-none" onchange="if(this.files.length)this.form.submit()">
            <button type="button" class="cs-btn sm" onclick="document.getElementById('fo-archivos').click()"><i class="fa-solid fa-upload"></i>Subir archivos</button>
            <small class="text-muted">JPG, PNG, WEBP o PDF · hasta 10 MB c/u</small>
        </form>
        @if ($adjuntos->count())
        <div class="fo-gal">
            @foreach ($adjuntos as $ad)
            <a href="{{ route('odonto.adjunto', $ad) }}" target="_blank" rel="noopener" title="{{ $ad->titulo ?: \App\Models\PacienteAdjunto::TIPOS[$ad->tipo][0] ?? '' }} · {{ $ad->created_at->format('d/m/Y') }}">
                @if ($ad->es_imagen)<img src="{{ route('odonto.adjunto', $ad) }}" alt="" loading="lazy">@else<div class="doc"><i class="fa-solid fa-file-pdf"></i></div>@endif
                <span>{{ $ad->titulo ?: (\App\Models\PacienteAdjunto::TIPOS[$ad->tipo][0] ?? 'Archivo') }} · {{ $ad->created_at->format('d/m/y') }}</span>
            </a>
            @endforeach
        </div>
        @endif
    </div>
    @endif
</div>
@if ($odo)
<script src="{{ asset('js/odontograma.js') }}?v=2"></script>
<script>Odonto.montar(document.getElementById('fo-mini'), { piezas: {!! json_encode($odo->piezas ?: new \stdClass) !!}, denticion: {!! json_encode($odo->denticion) !!}, hallazgos: {!! json_encode(\App\Support\Odontologia::HALLAZGOS) !!}, soloVer: true });</script>
@endif
