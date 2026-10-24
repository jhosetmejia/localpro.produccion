@extends('layouts.app')

@section('title', 'Puerta · ' . $evento->nombre)

@push('css')
<style>
.pt-wrap{max-width:560px;margin:0 auto}
.pt-cam{width:100%;aspect-ratio:1/1;border-radius:24px;overflow:hidden;background:#0b0d0c;position:relative;display:grid;place-items:center;color:#9aa8a0}
.pt-cam video{width:100%!important;height:100%!important;object-fit:cover}
.pt-res{border-radius:24px;padding:1.2rem 1.3rem;margin:14px 0;color:#fff;display:none}
.pt-res.ok{background:#16a34a}.pt-res.usada{background:#dc2626}.pt-res.no_existe,.pt-res.anulada{background:#7f1d1d}.pt-res.pendiente{background:#d97706}.pt-res.valida{background:#2563eb}
.pt-res .t{font-size:1.5rem;font-weight:900;line-height:1.15}
.pt-res .n{font-size:1.25rem;font-weight:800;margin-top:.6rem}
.pt-res .d{opacity:.92}
.pt-cont{display:flex;justify-content:space-between;align-items:center;background:#fff;border:1.5px solid var(--lp-line);border-radius:18px;padding:.8rem 1rem;font-weight:800}
</style>
@endpush

@section('content')
<div class="lp-dash pt-wrap">
    <div class="lp-hello">
        <div><small>Puerta</small><h1>{{ $evento->nombre }}</h1></div>
        <div class="lp-quick"><a href="{{ route('eventos.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-left me-1"></i>Eventos</a></div>
    </div>

    <div class="pt-cont mb-3"><span><i class="fa-solid fa-door-open me-1"></i>Ingresaron</span><span><span id="pt-in">{{ $stats['ingresaron'] }}</span> / <span id="pt-tot">{{ $stats['aprobadas'] }}</span></span></div>

    <div class="pt-cam" id="pt-cam"><button class="btn btn-primary btn-lg" id="pt-abrir"><i class="fa-solid fa-camera me-1"></i>Abrir cámara</button></div>

    <div class="pt-res" id="pt-res"><div class="t" id="pt-t"></div><div class="n" id="pt-n"></div><div class="d" id="pt-d"></div>
        <button class="btn btn-light mt-3 w-100 fw-bold" id="pt-entrar" style="display:none"><i class="fa-solid fa-door-open me-1"></i>Dejar entrar</button></div>

    <form class="lp-box mt-3" id="pt-form">
        <label class="form-label fw-bold">¿No lee el QR? Busque por código o número de documento</label>
        <div class="d-flex gap-2"><input class="form-control form-control-lg" id="pt-cod" placeholder="LPE-… o documento" autocomplete="off">
            <button class="btn btn-dark"><i class="fa-solid fa-magnifying-glass"></i></button></div>
        <small class="text-muted d-block mt-2">Con el QR la persona queda adentro de una vez. Con el documento primero le mostramos los datos y usted toca "Dejar entrar".</small>
    </form>
</div>
@endsection

@push('js')
<script>
(function () {
    var URL_V = @json(route('eventos.validar', $evento)), TOKEN = @json(csrf_token());
    var LIB = ['https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js', 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js'];
    var res = document.getElementById('pt-res'), entrar = document.getElementById('pt-entrar'), ocupado = false, ultimo = '', ultimoT = 0, pendienteCod = null;

    function sonar(bien) {
        try {
            var c = new (window.AudioContext || window.webkitAudioContext)(), o = c.createOscillator(), g = c.createGain();
            o.frequency.value = bien ? 1100 : 260; o.connect(g); g.connect(c.destination); g.gain.value = .18;
            o.start(); o.stop(c.currentTime + (bien ? .15 : .45));
        } catch (e) {}
        if (navigator.vibrate) navigator.vibrate(bien ? 80 : [200, 80, 200]);
    }

    function mostrar(r) {
        res.className = 'pt-res ' + r.estado; res.style.display = 'block';
        document.getElementById('pt-t').textContent = r.mensaje;
        var p = r.persona;
        document.getElementById('pt-n').textContent = p ? p.nombre : '';
        document.getElementById('pt-d').textContent = p ? [p.documento, p.edad !== null ? p.edad + ' años' : '', p.colegio || ''].filter(Boolean).join(' · ') + (r.portero ? ' · Lo dejó entrar: ' + r.portero : '') : '';
        entrar.style.display = r.estado === 'valida' ? '' : 'none';
        pendienteCod = r.estado === 'valida' && p ? p.codigo : null;
        if (r.conteo) { document.getElementById('pt-in').textContent = r.conteo.ingresaron; document.getElementById('pt-tot').textContent = r.conteo.aprobadas; }
        sonar(r.estado === 'ok' || r.estado === 'valida');
        res.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function validar(codigo, revisar) {
        if (ocupado) return; ocupado = true;
        fetch(URL_V, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': TOKEN }, body: JSON.stringify({ codigo: codigo, revisar: revisar ? 1 : 0 }) })
            .then(function (r) { return r.json(); }).then(mostrar)
            .catch(function () { mostrar({ estado: 'no_existe', mensaje: 'Sin conexión. Intente de nuevo.' }); })
            .finally(function () { ocupado = false; });
    }

    entrar.addEventListener('click', function () { if (pendienteCod) validar(pendienteCod, false); });
    document.getElementById('pt-form').addEventListener('submit', function (e) {
        e.preventDefault(); var v = document.getElementById('pt-cod').value.trim(); if (!v) return;
        validar(v, !/^LPE-/i.test(v)); document.getElementById('pt-cod').value = '';
    });

    function cargar() {
        return new Promise(function (ok, mal) {
            if (window.Html5Qrcode) return ok(); var i = 0;
            (function intentar() { if (i >= LIB.length) return mal(); var s = document.createElement('script'); s.src = LIB[i++]; s.onload = ok; s.onerror = intentar; document.head.appendChild(s); })();
        });
    }
    document.getElementById('pt-abrir').addEventListener('click', function () {
        var cam = document.getElementById('pt-cam'); cam.innerHTML = '<div id="pt-lector" style="width:100%;height:100%"></div>';
        cargar().then(function () {
            var l = new Html5Qrcode('pt-lector');
            l.start({ facingMode: 'environment' }, { fps: 10, qrbox: function (w, h) { var m = Math.floor(Math.min(w, h) * .7); return { width: m, height: m }; } }, function (txt) {
                var ahora = Date.now();
                if (txt === ultimo && ahora - ultimoT < 4000) return;
                ultimo = txt; ultimoT = ahora;
                validar(txt, false);
            }, function () {}).catch(function () { cam.innerHTML = '<div class="p-4 text-center">No se pudo abrir la cámara. Dé permiso de cámara al navegador o busque por documento.</div>'; });
        }).catch(function () { cam.innerHTML = '<div class="p-4 text-center">No se pudo cargar el escáner. Revise la conexión.</div>'; });
    });
})();
</script>
@endpush
