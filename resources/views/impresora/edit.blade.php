@extends('layouts.app')

@section('title', 'Impresora')

@push('css')
<style>
.im{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:1rem;align-items:start}
@media (max-width:900px){.im{grid-template-columns:1fr}}
.im-papel{display:grid;grid-template-columns:1fr 1fr;gap:.6rem}
.im-papel label{border:1.5px solid #e3ebe6;border-radius:16px;padding:.8rem;cursor:pointer;font-weight:800;display:flex;gap:.6rem;align-items:center}
.im-papel input{accent-color:#16b34a}
.im-papel label:has(input:checked){border-color:#16b34a;background:#f1fbf3}
.im-r{margin-top:1rem}
.im-r .top{display:flex;justify-content:space-between;align-items:center;font-weight:800}
.im-r .top output{background:#0b0d0c;color:#a6f34a;border-radius:999px;padding:.1rem .7rem;font-size:.85rem}
.im-r input[type=range]{width:100%;accent-color:#16b34a}
.im-r small{color:#5b665e;font-weight:600}
.im-prev{position:sticky;top:80px;background:#eef3ef;border-radius:22px;padding:12px}
.im-prev iframe{width:100%;height:520px;border:0;border-radius:14px;background:#fff}
.im-tips li{margin-bottom:.35rem}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-print me-1"></i>Mi negocio</small><h1>Mi <span>impresora</span></h1>
            <span class="text-muted fw-semibold">Ajuste su impresora de tiquetes para que el tiquete, los recibos y la comanda de cocina salgan derechos y completos.</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    <form method="post" action="{{ route('impresora.update') }}" id="imForm" class="im">
        @csrf
        <div>
            <div class="lp-box">
                <h3><span><i class="fa-solid fa-scroll me-1"></i>¿De qué tamaño es el rollo?</span></h3>
                <div class="im-papel">
                    @foreach (\App\Support\Impresora::PAPELES as $mm => $txt)
                    <label><input type="radio" name="papel" value="{{ $mm }}" @checked($imp['papel'] === $mm)><span>{{ $txt }}</span></label>
                    @endforeach
                </div>

                <div class="im-r">
                    <div class="top"><span>Ancho que imprime</span><output id="oAncho">{{ $imp['ancho'] }} mm</output></div>
                    <input type="range" name="ancho" id="ancho" min="40" max="{{ $imp['papel'] }}" step="0.5" value="{{ $imp['ancho'] }}">
                    <small>Si se cortan las letras de los lados, bájelo. Las de 80 mm suelen imprimir 72 mm y las de 58 mm unos 48 mm.</small>
                </div>
                <div class="im-r">
                    <div class="top"><span>Mover a los lados</span><output id="oMargen">{{ $imp['margen'] }} mm</output></div>
                    <input type="range" name="margen" id="margen" min="-8" max="15" step="0.5" value="{{ $imp['margen'] }}">
                    <small>El tiquete ya sale centrado (0). Si se corta más un lado que el otro: corrido a la derecha → número negativo; corrido a la izquierda → positivo.</small>
                </div>
                <div class="im-r">
                    <div class="top"><span>Tamaño de la letra</span><output id="oLetra">{{ $imp['letra'] }} px</output></div>
                    <input type="range" name="letra" id="letra" min="9" max="18" step="1" value="{{ $imp['letra'] }}">
                </div>
                <div class="im-r">
                    <div class="top"><span>Espacio al final (para el corte)</span><output id="oFinal">{{ $imp['final'] }} mm</output></div>
                    <input type="range" name="final" id="final" min="0" max="40" step="1" value="{{ $imp['final'] }}">
                    <small>Si el cortador corta encima del texto, súbalo.</small>
                </div>
                <div class="form-check form-switch im-r">
                    <input class="form-check-input" type="checkbox" role="switch" name="negrita" id="negrita" value="1" @checked($imp['negrita'])>
                    <label class="form-check-label fw-bold" for="negrita">Letra más oscura (negrita) — sirve si el tiquete sale pálido</label>
                </div>

                <div class="d-flex gap-2 flex-wrap mt-3">
                    <button type="button" class="btn btn-dark btn-lg fw-bold" id="imProbar"><i class="fa-solid fa-print me-1"></i>Imprimir prueba</button>
                    <button class="btn btn-success btn-lg fw-bold"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar</button>
                    <button type="button" class="btn btn-light border btn-lg fw-bold" id="imReset">Valores de fábrica</button>
                </div>
            </div>

            <div class="lp-box mt-3 im-tips">
                <h3><span><i class="fa-solid fa-lightbulb me-1"></i>Si sigue saliendo chueco: revise la ventana de imprimir</span></h3>
                <ol class="fw-semibold mb-0">
                    <li><b>Impresora:</b> elija la térmica (DigitalPOS, POS-80, XP-80…), no "Guardar como PDF".</li>
                    <li><b>Más opciones → Márgenes:</b> <b>Ninguno</b>.</li>
                    <li><b>Escala:</b> <b>Predeterminada / 100 %</b> (no "Ajustar a la página").</li>
                    <li>Quite el chulo de <b>Encabezados y pies de página</b> (si no, sale la fecha y la dirección de la página).</li>
                    <li><b>Tamaño del papel:</b> 80 mm (o 72 mm / 58 mm según su rollo), el que traiga el driver de la impresora.</li>
                    <li>Chrome recuerda estas opciones: se hacen una sola vez en cada computador.</li>
                </ol>
            </div>
        </div>

        <div class="im-prev">
            <div class="fw-bold mb-2"><i class="fa-solid fa-eye me-1"></i>Así sale el tiquete</div>
            <iframe id="imVista" title="Vista del tiquete de prueba" src="{{ route('impresora.prueba', $imp + ['embed' => 1]) }}"></iframe>
            <small class="text-muted fw-semibold d-block mt-2">Los cambios se ven aquí al instante. Imprima la prueba: las rayas <b>|&lt;</b> y <b>&gt;|</b> deben salir completas a los dos lados.</small>
        </div>
    </form>
</div>
@endsection

@push('js')
<script>
(function () {
    var f = document.getElementById('imForm'), vista = document.getElementById('imVista'), base = @json(route('impresora.prueba'));
    var DEF = { 80: { ancho: 72 }, 58: { ancho: 48 } };
    function val(n) { return f.elements[n]; }
    function datos() {
        return { papel: f.querySelector('input[name=papel]:checked').value, ancho: val('ancho').value, margen: val('margen').value,
            letra: val('letra').value, final: val('final').value, negrita: val('negrita').checked ? 1 : 0 };
    }
    var t = null;
    function pintar() {
        document.getElementById('oAncho').textContent = val('ancho').value + ' mm';
        document.getElementById('oMargen').textContent = val('margen').value + ' mm';
        document.getElementById('oLetra').textContent = val('letra').value + ' px';
        document.getElementById('oFinal').textContent = val('final').value + ' mm';
        clearTimeout(t);
        t = setTimeout(function () { vista.src = base + '?' + new URLSearchParams(Object.assign(datos(), { embed: 1 })).toString(); }, 250);
    }
    f.addEventListener('input', pintar);
    f.querySelectorAll('input[name=papel]').forEach(function (r) {
        r.addEventListener('change', function () {
            val('ancho').max = r.value; val('ancho').value = DEF[r.value].ancho; pintar();
        });
    });
    document.getElementById('imReset').addEventListener('click', function () {
        var p = f.querySelector('input[name=papel]:checked').value;
        val('ancho').max = p; val('ancho').value = DEF[p].ancho; val('margen').value = 0; val('letra').value = 12; val('final').value = 8; val('negrita').checked = false; pintar();
    });
    // Imprime la prueba con los ajustes de la pantalla (sin guardar todavía)
    document.getElementById('imProbar').addEventListener('click', function () {
        try { vista.contentWindow.focus(); vista.contentWindow.print(); }
        catch (e) { window.open(base + '?' + new URLSearchParams(datos()).toString(), '_blank'); }
    });
})();
</script>
@endpush
