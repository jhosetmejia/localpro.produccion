<!DOCTYPE html>
<html lang="es">
<head>
    @include('layouts.partials.landing-head')
    <title>Gana como asesor · LocalPro</title>
    <meta name="robots" content="noindex">
    <style>
        :root{--g:linear-gradient(135deg,#a6f34a 0%,#2fd15a 45%,#16b34a 100%);--k:#0b0d0c;--ink:#151a17;--mut:#5b665e;--line:#e3efe5;--bg:#f4f8f5;--lime:#a6f34a;--pink:#ff5d8f;--gold:#ffcc33}
        *{box-sizing:border-box}
        body{margin:0;font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:var(--bg);color:var(--ink);-webkit-font-smoothing:antialiased}
        a{color:inherit}
        .w{max-width:1080px;margin:0 auto;padding:0 16px}
        .top{display:flex;align-items:center;justify-content:space-between;padding:16px 0}
        .brand{display:flex;align-items:center;gap:.5rem;font-weight:800;text-decoration:none;font-size:1.1rem}
        .brand b{width:32px;height:32px;border-radius:10px;background:var(--k);color:var(--lime);display:grid;place-items:center}
        .btn{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border:0;border-radius:999px;padding:.85rem 1.3rem;font-weight:800;text-decoration:none;cursor:pointer;font-family:inherit;font-size:1rem}
        .btn-g{background:var(--g);color:var(--k);box-shadow:0 14px 30px -14px rgba(47,209,90,.9)}
        .btn-k{background:var(--k);color:#fff}
        .btn-w{background:#25d366;color:#fff}
        .hero{background:var(--k);color:#fff;border-radius:32px;padding:36px 24px;position:relative;overflow:hidden;margin-bottom:22px}
        .hero::after{content:"";position:absolute;right:-80px;top:-80px;width:280px;height:280px;border-radius:50%;background:var(--g);opacity:.35;filter:blur(10px)}
        .hero small{color:var(--lime);font-weight:800;letter-spacing:.12em;text-transform:uppercase;font-size:.75rem}
        .hero h1{font-size:clamp(2rem,6vw,3.4rem);line-height:1.02;margin:.4rem 0 .8rem;letter-spacing:-.03em;position:relative;z-index:1}
        .hero h1 em{font-style:normal;background:var(--g);-webkit-background-clip:text;background-clip:text;color:transparent}
        .hero p{color:#cfd8d2;font-size:1.05rem;max-width:620px;margin:0 0 18px;position:relative;z-index:1}
        .chips{display:flex;flex-wrap:wrap;gap:8px;position:relative;z-index:1}
        .chips span{background:#1a201c;border-radius:999px;padding:.45rem .85rem;font-weight:700;font-size:.85rem}
        .grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.15fr);gap:18px;align-items:start}
        .card{background:#fff;border:1.5px solid var(--line);border-radius:26px;padding:22px}
        .card h2{margin:0 0 4px;font-size:1.3rem}
        .card .sub{color:var(--mut);font-weight:600;font-size:.92rem;margin:0 0 16px}
        .roles{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:6px;margin-bottom:18px}
        .roles button{border:2px solid var(--line);background:#fff;border-radius:16px;padding:.6rem .2rem;font-weight:800;font-size:.72rem;cursor:pointer;font-family:inherit;display:flex;flex-direction:column;align-items:center;gap:.3rem;line-height:1.1;color:var(--ink)}
        .roles button i,.roles button svg{font-size:1.1rem;color:#16b34a}
        .roles button.on{border-color:#2fd15a;background:#f2fbf3;box-shadow:0 0 0 4px rgba(47,209,90,.15)}
        .sl{margin-bottom:16px}
        .sl label{display:flex;justify-content:space-between;font-weight:700;font-size:.92rem;margin-bottom:6px}
        .sl label b{background:var(--k);color:var(--lime);border-radius:999px;padding:.1rem .65rem;font-size:.9rem}
        .sl input{width:100%;accent-color:#16b34a;height:28px}
        .sl small{color:var(--mut);font-weight:600;font-size:.78rem}
        .big{background:var(--g);border-radius:24px;padding:20px;color:var(--k);margin-bottom:14px}
        .big small{font-weight:800;text-transform:uppercase;letter-spacing:.08em;font-size:.72rem;opacity:.75}
        .big .v{font-size:clamp(2.2rem,7vw,3.2rem);font-weight:800;letter-spacing:-.04em;line-height:1.05}
        .big .s{font-weight:700;font-size:.92rem}
        .kpis{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px}
        .kpi{background:var(--bg);border-radius:18px;padding:14px}
        .kpi small{color:var(--mut);font-weight:800;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em}
        .kpi b{display:block;font-size:1.35rem;letter-spacing:-.02em;margin-top:2px}
        .det{list-style:none;margin:0 0 14px;padding:0}
        .det li{display:flex;justify-content:space-between;gap:10px;padding:9px 0;border-bottom:1px dashed var(--line);font-weight:600;font-size:.92rem}
        .det li b{white-space:nowrap}
        .chart{display:flex;align-items:flex-end;gap:3px;height:150px;padding:10px 4px 0;background:var(--bg);border-radius:18px;margin-bottom:6px}
        .chart div{flex:1;background:var(--g);border-radius:5px 5px 2px 2px;min-height:2px;position:relative}
        .chart div.r{background:linear-gradient(180deg,#ffcc33,#16b34a)}
        .ley{display:flex;justify-content:space-between;color:var(--mut);font-size:.75rem;font-weight:700;margin-bottom:14px}
        .note{color:var(--mut);font-size:.8rem;font-weight:600}
        .tabla{width:100%;border-collapse:collapse;font-size:.92rem}
        .tabla th,.tabla td{padding:10px 8px;border-bottom:1px solid var(--line);text-align:left}
        .tabla th{font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--mut)}
        .tabla td:not(:first-child),.tabla th:not(:first-child){text-align:right}
        .esc{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin:18px 0}
        .esc div{background:#fff;border:1.5px solid var(--line);border-radius:20px;padding:14px;text-align:center}
        .esc div i,.esc div svg{font-size:1.4rem;color:#16b34a;margin-bottom:6px}
        .esc b{display:block;font-size:.9rem}
        .esc small{color:var(--mut);font-weight:600;font-size:.76rem}
        .cta{background:var(--k);color:#fff;border-radius:28px;padding:28px 22px;text-align:center;margin:22px 0 30px}
        .cta h2{margin:0 0 6px;font-size:1.6rem}
        .cta p{color:#cfd8d2;margin:0 0 16px}
        @media (max-width:860px){.grid{grid-template-columns:minmax(0,1fr)}.esc{grid-template-columns:repeat(2,minmax(0,1fr))}.esc div:last-child{grid-column:1/-1}}
        @media (max-width:420px){.roles button{font-size:.62rem}.hero{padding:28px 18px}.card{padding:18px}}
    </style>
</head>
<body>
<div class="w">
    <div class="top">
        <a href="{{ url('/') }}" class="brand"><b>L</b>LocalPro</a>
        <a class="btn btn-k" style="padding:.6rem 1rem;font-size:.9rem" href="{{ auth()->user()->es_superadmin ? route('admin.red.index') : route('asesor.index') }}"><i class="fa-solid fa-arrow-left"></i> Mi panel</a>
    </div>

    <section class="hero">
        <small>Trabaja con LocalPro</small>
        <h1>Gana dinero ayudando a los negocios de tu ciudad a <em>vender más</em>.</h1>
        <p>LocalPro es el sistema de caja, inventario y catálogo que los negocios usan desde el celular. Tú los inscribes y ganas comisión por cada negocio, y cada año cuando renuevan.</p>
        <div class="chips"><span>💸 Sin inversión</span><span>📱 Desde tu celular</span><span>🔁 Ganas en cada renovación, de por vida</span><span>🚀 Creces a líder</span></div>
    </section>

    <div class="grid">
        <div class="card">
            <h2>Simula tus ganancias</h2>
            <p class="sub">Mueve las barras y mira cuánto podrías ganar.</p>
            <div class="roles" id="roles">
                <button type="button" data-r="1" class="on"><i class="fa-solid fa-user-tie"></i>Asesor</button>
                <button type="button" data-r="2"><i class="fa-solid fa-map-pin"></i>Líder de zona</button>
                <button type="button" data-r="3"><i class="fa-solid fa-city"></i>Líder de ciudad</button>
                <button type="button" data-r="4"><i class="fa-solid fa-flag"></i>Líder depto.</button>
                <button type="button" data-r="5"><i class="fa-solid fa-earth-americas"></i>Líder de país</button>
            </div>
            <div class="sl"><label>Negocios que inscribes tú al mes <b id="vPropios">10</b></label><input type="range" id="propios" min="0" max="100" value="10"></div>
            <div class="sl" data-min="2"><label>Asesores en tu zona <b id="vAsesores">10</b></label><input type="range" id="asesores" min="1" max="10" value="10"><small>Un líder de zona maneja hasta 10 asesores (una comuna).</small></div>
            <div class="sl" data-min="2"><label>Negocios que inscribe cada asesor al mes <b id="vPorAsesor">5</b></label><input type="range" id="porAsesor" min="1" max="100" value="5"></div>
            <div class="sl" data-min="3"><label>Zonas (comunas) en tu ciudad <b id="vZonas">5</b></label><input type="range" id="zonas" min="1" max="30" value="5"></div>
            <div class="sl" data-min="4"><label>Ciudades en tu departamento <b id="vCiudades">4</b></label><input type="range" id="ciudades" min="1" max="30" value="4"></div>
            <div class="sl" data-min="5"><label>Departamentos en el país <b id="vDeptos">5</b></label><input type="range" id="deptos" min="1" max="32" value="5"></div>
            <div class="sl"><label>Negocios que renuevan cada año <b id="vRenueva">80%</b></label><input type="range" id="renueva" min="0" max="100" step="5" value="80"></div>
        </div>

        <div class="card">
            <div class="big">
                <small>Ganarías en tu primer mes</small>
                <div class="v" id="rMes">$0</div>
                <div class="s" id="rMesTxt"></div>
            </div>
            <div class="kpis">
                <div class="kpi"><small>En tu primer año</small><b id="rAnio">$0</b></div>
                <div class="kpi"><small>Segundo año (con renovaciones)</small><b id="rAnio2">$0</b></div>
            </div>
            <ul class="det" id="det"></ul>
            <div class="chart" id="chart"></div>
            <div class="ley"><span>Mes 1</span><span>Mes 12</span><span>Mes 24</span></div>
            <p class="note">Ejemplo con los valores actuales de comisión. Lo que ganes depende de los negocios que inscribas y de los que renueven; no es un salario fijo ni una garantía. Las comisiones se pagan cuando el negocio paga su plan anual.</p>
        </div>
    </div>

    <h2 style="margin:30px 0 4px">Así crece tu camino</h2>
    <p class="sub" style="color:var(--mut);font-weight:600;margin:0">Empiezas como asesor y puedes subir a líder. Cada líder gana por su equipo, y también por sus propias ventas.</p>
    <div class="esc">
        <div><i class="fa-solid fa-user-tie"></i><b>Asesor</b><small>Inscribe negocios</small></div>
        <div><i class="fa-solid fa-map-pin"></i><b>Líder de zona</b><small>Hasta 10 asesores en su comuna</small></div>
        <div><i class="fa-solid fa-city"></i><b>Líder de ciudad</b><small>Todas las zonas de su ciudad</small></div>
        <div><i class="fa-solid fa-flag"></i><b>Líder de departamento</b><small>Todas sus ciudades</small></div>
        <div><i class="fa-solid fa-earth-americas"></i><b>Líder de país</b><small>Todo el país</small></div>
    </div>

    <div class="card">
        <h2>Lo que ganas por cada negocio</h2>
        <p class="sub">Plan anual de LocalPro de {{ '$' . number_format($valores['valor_plan'], 0, ',', '.') }}.</p>
        <table class="tabla">
            <thead><tr><th>Quién gana</th><th>Negocio nuevo</th><th>Cada renovación</th></tr></thead>
            <tbody>
            @foreach (['venta' => 'Quien inscribe el negocio', 'lider_zona' => 'Líder de zona', 'lider_ciudad' => 'Líder de ciudad', 'lider_departamento' => 'Líder de departamento', 'lider_pais' => 'Líder de país'] as $k => $n)
                <tr><td><b>{{ $n }}</b></td><td>{{ '$' . number_format($valores['nueva'][$k], 0, ',', '.') }}</td><td>{{ '$' . number_format($valores['renovacion'][$k], 0, ',', '.') }}</td></tr>
            @endforeach
            </tbody>
        </table>
        <p class="note" style="margin-top:10px">Si un líder inscribe un negocio él mismo, gana lo de quien inscribe más lo de su cargo y lo de los cargos de abajo.</p>
    </div>

    <div class="cta">
        <h2>¿Listo para empezar?</h2>
        <p>Muéstrale este simulador a quien quieras sumar a tu equipo y escríbenos para inscribirlo.</p>
        @if ($whatsapp)
            <a class="btn btn-w" target="_blank" href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Hola, tengo una persona para inscribir como asesor de LocalPro') }}"><i class="fa-brands fa-whatsapp"></i> Inscribir un asesor nuevo</a>
        @else
            <a class="btn btn-g" href="{{ url('/') }}">Conocer LocalPro</a>
        @endif
    </div>
</div>

<script>
(function () {
    var V = @json($valores);
    var N = V.nueva, R = V.renovacion;
    var rol = 1;
    var $ = function (id) { return document.getElementById(id); };
    var fmt = function (n) { return '$' + Math.round(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'); };
    var niveles = ['venta', 'lider_zona', 'lider_ciudad', 'lider_departamento', 'lider_pais'];
    // Lo que gana por un negocio que inscribe él mismo: su parte de asesor + la de su cargo y los de abajo
    var propio = function (T) { var s = 0; for (var i = 0; i < rol; i++) s += +T[niveles[i]] || 0; return s; };
    var deCargo = function (T) { return rol > 1 ? (+T[niveles[rol - 1]] || 0) : 0; };

    function calcular() {
        var p = +$('propios').value, a = +$('asesores').value, k = +$('porAsesor').value, z = +$('zonas').value, c = +$('ciudades').value, d = +$('deptos').value, ren = +$('renueva').value / 100;
        $('vPropios').textContent = p; $('vAsesores').textContent = a; $('vPorAsesor').textContent = k; $('vZonas').textContent = z; $('vCiudades').textContent = c; $('vDeptos').textContent = d; $('vRenueva').textContent = Math.round(ren * 100) + '%';
        document.querySelectorAll('.sl[data-min]').forEach(function (el) { el.style.display = rol >= +el.dataset.min ? '' : 'none'; });

        // Negocios que inscribe su equipo al mes (sin contar los propios)
        var red = 0;
        if (rol >= 2) red = a * k;
        if (rol >= 3) red = red * z;
        if (rol >= 4) red = red * c;
        if (rol >= 5) red = red * d;

        var mesNuevo = p * propio(N) + red * deCargo(N);
        var mesRenov = (p * propio(R) + red * deCargo(R)) * ren;
        var meses = [];
        for (var m = 1; m <= 24; m++) meses.push(mesNuevo + (m > 12 ? mesRenov : 0));
        var anio1 = mesNuevo * 12, anio2 = (mesNuevo + mesRenov) * 12;

        $('rMes').textContent = fmt(mesNuevo);
        $('rMesTxt').textContent = (p + red) + ' negocios nuevos al mes' + (red ? ' (' + p + ' tuyos + ' + red + ' de tu equipo)' : '');
        $('rAnio').textContent = fmt(anio1);
        $('rAnio2').textContent = fmt(anio2);

        var det = [];
        if (p) det.push(['Tus ' + p + ' negocios × ' + fmt(propio(N)), p * propio(N)]);
        if (red) det.push([red + ' negocios de tu equipo × ' + fmt(deCargo(N)), red * deCargo(N)]);
        det.push(['Desde el mes 13: renovaciones de por vida (' + Math.round(ren * 100) + '% renuevan) cada mes', mesRenov]);
        $('det').innerHTML = det.map(function (x) { return '<li><span>' + x[0] + '</span><b>' + fmt(x[1]) + '</b></li>'; }).join('');

        var max = Math.max.apply(null, meses) || 1;
        $('chart').innerHTML = meses.map(function (v, i) { return '<div class="' + (i >= 12 ? 'r' : '') + '" style="height:' + Math.max(2, v / max * 100) + '%" title="Mes ' + (i + 1) + ': ' + fmt(v) + '"></div>'; }).join('');
    }

    $('roles').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) return;
        [].forEach.call(this.children, function (x) { x.classList.toggle('on', x === b); });
        rol = +b.dataset.r;
        if (rol > 1 && +$('propios').value === 10) $('propios').value = 5;
        calcular();
    });
    document.querySelectorAll('input[type=range]').forEach(function (i) { i.addEventListener('input', calcular); });
    calcular();
})();
</script>
</body>
</html>
