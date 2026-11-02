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
.im-tabs{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem}
.im-tabs a{border-radius:999px;padding:.6rem 1.1rem;font-weight:800;text-decoration:none;background:#fff;border:1.5px solid #e3ebe6;color:#151a17}
.im-tabs a.on{background:#0b0d0c;color:#a6f34a;border-color:#0b0d0c}
.im-tabs a small{font-weight:700;opacity:.7;margin-left:.3rem}
.im-casos{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.8rem}
.im-caso{border:1.5px solid #e3ebe6;border-radius:18px;padding:.9rem 1rem;background:#fff}
.im-caso b{display:block;margin-bottom:.3rem}
.im-caso small{color:#4b5550;font-weight:600;display:block;line-height:1.45}
</style>
@endpush

@section('content')
<div class="lp-dash">
    <div class="lp-hello">
        <div><small><i class="fa-solid fa-print me-1"></i>Mi negocio</small><h1>Mi <span>impresora</span></h1>
            <span class="text-muted fw-semibold">Ajuste sus impresoras para que todo salga derecho y completo. Puede tener una para <b>caja</b> y otra para <b>cocina</b>, cada una con su tamaño.</span></div>
    </div>
    @include('layouts.partials.lp-flash')

    @if ($tienda->mod_pedidos)
    <div class="im-tabs">
        <a href="{{ route('impresora.edit', ['para' => 'caja']) }}" class="{{ $para === 'caja' ? 'on' : '' }}"><i class="fa-solid fa-cash-register me-1"></i>Impresora de caja<small>tiquetes, facturas, cuadre, recibos</small></a>
        <a href="{{ route('impresora.edit', ['para' => 'cocina']) }}" class="{{ $para === 'cocina' ? 'on' : '' }}"><i class="fa-solid fa-fire-burner me-1"></i>Impresora de cocina<small>comandas</small></a>
    </div>
    @if ($cocinaUsaCaja)
    <div class="alert border-0 fw-semibold" style="background:#eef6ff;color:#1e40af"><i class="fa-solid fa-circle-info me-1"></i>Por ahora las comandas usan los ajustes de la impresora de caja. Si la de cocina es de otro tamaño, ajústela aquí y guarde.</div>
    @endif
    @endif

    <form method="post" action="{{ route('impresora.update') }}" id="imForm" class="im">
        @csrf
        <input type="hidden" name="para" value="{{ $para }}">
        <div>
            <div class="lp-box">
                <h3><span><i class="fa-solid fa-scroll me-1"></i>{{ $para === 'cocina' ? 'Impresora de cocina: ¿de qué tamaño es el rollo?' : '¿De qué tamaño es el rollo?' }}</span></h3>
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

            <div class="lp-box mt-3" id="dos-impresoras">
                <h3><span><i class="fa-solid fa-print me-1"></i>¿Tiene una o dos impresoras? Así se organiza</span></h3>
                <div class="im-casos">
                    <div class="im-caso"><b>🖨 Una sola impresora</b><small>Ajuste solo la <b>de caja</b>. Si usa Pedidos, las comandas salen por la misma con esos ajustes. No tiene que hacer nada más.</small></div>
                    <div class="im-caso"><b>🖨🖨 Dos impresoras en aparatos distintos <span class="badge bg-success">recomendado</span></b><small>La de caja en el computador de caja y la de cocina en la tablet o PC de cocina. En la cocina abra <b>Pedidos → Estaciones</b> y prenda <b>Imprimir al llegar</b>. Chrome recuerda en cada aparato cuál es su impresora: cada una imprime lo suyo.</small></div>
                    <div class="im-caso"><b>🖨🖨 Dos impresoras en el mismo computador</b><small>Funciona, pero al imprimir revise en la ventana de Chrome cuál impresora está elegida: Chrome propone la última que usó. Truco: en Windows ponga la de caja como <b>predeterminada</b> y elija la de cocina solo para comandas.</small></div>
                    <div class="im-caso"><b>📏 Tamaños distintos (80 mm y 58 mm)</b><small>Ajuste cada una en su pestaña: <b>Impresora de caja</b> y <b>Impresora de cocina</b>. Cada una guarda su rollo, ancho, letra y espacio para el corte.</small></div>
                </div>
                <small class="text-muted fw-semibold d-block mt-2"><i class="fa-solid fa-lock me-1"></i>Por seguridad, ningún navegador deja que una página web imprima sin mostrar la ventana de imprimir. Por eso cada impresora va mejor en su propio aparato.</small>
            </div>
        </div>

        <div class="im-prev">
            <div class="fw-bold mb-2"><i class="fa-solid fa-eye me-1"></i>{{ $para === 'cocina' ? 'Así sale la comanda' : 'Así sale el tiquete' }}</div>
            <iframe id="imVista" title="Vista de la prueba" src="{{ route('impresora.prueba', $imp + ['embed' => 1, 'para' => $para]) }}"></iframe>
            <small class="text-muted fw-semibold d-block mt-2">Los cambios se ven aquí al instante. Imprima la prueba: las rayas <b>|&lt;</b> y <b>&gt;|</b> deben salir completas a los dos lados.</small>
        </div>
    </form>
    @if ($configurada)
    <form method="post" action="{{ route('impresora.borrar', ['para' => $para]) }}" class="mt-3" data-confirm="¿Quitar los ajustes de la impresora {{ $para === 'cocina' ? 'de cocina' : 'de caja' }}?" data-confirm-texto="El tiquete vuelve a salir como venía de fábrica." data-confirm-ok="Sí, quitar">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger fw-bold"><i class="fa-solid fa-rotate-left me-1"></i>Quitar mis ajustes (volver a como venía)</button>
        <small class="text-muted fw-semibold d-block mt-1">Los ajustes son solo de <b>{{ $tienda->nombre }}</b>: no cambian los tiquetes de otros negocios.</small>
    </form>
    @else
    <small class="text-muted fw-semibold d-block mt-3"><i class="fa-solid fa-circle-info me-1"></i>Este negocio no tiene ajustes guardados: su tiquete sale como venía. Los ajustes son solo de <b>{{ $tienda->nombre }}</b>.</small>
    @endif
</div>
@endsection

@push('js')
<script>
(function () {
    var f = document.getElementById('imForm'), vista = document.getElementById('imVista'), base = @json(route('impresora.prueba')), PARA = @json($para);
    var DEF = { 80: { ancho: 72 }, 58: { ancho: 48 } };
    function val(n) { return f.elements[n]; }
    function datos() {
        return { papel: f.querySelector('input[name=papel]:checked').value, ancho: val('ancho').value, margen: val('margen').value,
            letra: val('letra').value, final: val('final').value, negrita: val('negrita').checked ? 1 : 0, para: PARA };
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
