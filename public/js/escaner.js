/*
 * Escáner de código de barras con la cámara del celular (o del computador).
 * Lee los códigos de las etiquetas de LocalPro (CODE128) y también EAN/UPC y QR.
 *
 * Uso:
 *   LPEscaner.abrir({ titulo: 'Escanear prenda', continuo: true, alLeer: function (codigo) { ... } });
 * Con continuo:true la cámara sigue abierta para escanear una prenda tras otra (POS, conteo).
 * Botones automáticos: <button data-escanear="idDelInput"> pone el código en ese campo y lo busca.
 */
(function () {
    var LIB = ['https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js', 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js'];
    var cargando = null, lector = null, ov = null, ultimo = '', ultimoT = 0;

    function cargar() {
        if (window.Html5Qrcode) return Promise.resolve();
        if (cargando) return cargando;
        cargando = new Promise(function (ok, mal) {
            var i = 0;
            (function intentar() {
                if (i >= LIB.length) { cargando = null; return mal(); }
                var s = document.createElement('script');
                s.src = LIB[i++]; s.onload = function () { ok(); }; s.onerror = intentar;
                document.head.appendChild(s);
            })();
        });
        return cargando;
    }

    function css() {
        if (document.getElementById('lpEscCss')) return;
        var st = document.createElement('style'); st.id = 'lpEscCss';
        st.textContent =
            '.lpesc{position:fixed;inset:0;z-index:3000;background:rgba(5,8,6,.92);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:16px;color:#fff;font-family:inherit}' +
            '.lpesc h3{font-size:1.1rem;font-weight:800;margin:0 0 4px;text-align:center}' +
            '.lpesc p{margin:0 0 12px;color:#b8c4bc;font-size:.88rem;text-align:center}' +
            '.lpesc .cam{width:min(92vw,460px);aspect-ratio:4/3;border-radius:20px;overflow:hidden;background:#000;position:relative}' +
            '.lpesc .cam video{width:100%!important;height:100%!important;object-fit:cover}' +
            '.lpesc .linea{position:absolute;left:8%;right:8%;top:50%;height:2px;background:#2fd15a;box-shadow:0 0 12px #2fd15a;animation:lpescl 1.6s ease-in-out infinite}' +
            '@keyframes lpescl{0%,100%{transform:translateY(-40px)}50%{transform:translateY(40px)}}' +
            '.lpesc .estado{min-height:24px;margin-top:12px;font-weight:800;text-align:center}' +
            '.lpesc .estado.ok{color:#a6f34a}.lpesc .estado.mal{color:#ffb4b4}' +
            '.lpesc .bts{display:flex;gap:8px;margin-top:12px}' +
            '.lpesc button{border:0;border-radius:999px;padding:.75rem 1.4rem;font-weight:800;font-size:1rem;cursor:pointer}' +
            '.lpesc .cerrar{background:#fff;color:#0b0d0c}.lpesc .linterna{background:rgba(255,255,255,.15);color:#fff}';
        document.head.appendChild(st);
    }

    function pitido() {
        try {
            var c = new (window.AudioContext || window.webkitAudioContext)(), o = c.createOscillator(), g = c.createGain();
            o.frequency.value = 1150; o.connect(g); g.connect(c.destination); g.gain.value = .12;
            o.start(); o.stop(c.currentTime + .09);
        } catch (e) {}
        if (navigator.vibrate) navigator.vibrate(60);
    }

    function cerrar() {
        var l = lector; lector = null;
        if (l) { try { l.stop().then(function () { l.clear(); }).catch(function () {}); } catch (e) {} }
        if (ov) { ov.remove(); ov = null; }
    }

    function abrir(op) {
        op = op || {};
        css(); cerrar();
        ov = document.createElement('div'); ov.className = 'lpesc';
        ov.innerHTML = '<h3>' + (op.titulo || 'Escanear código de barras') + '</h3>' +
            '<p>Apunte la cámara al código de la etiqueta de la prenda.</p>' +
            '<div class="cam"><div id="lpEscCam" style="width:100%;height:100%"></div><div class="linea"></div></div>' +
            '<div class="estado" id="lpEscEstado">Abriendo la cámara…</div>' +
            '<div class="bts"><button type="button" class="cerrar">' + (op.continuo ? 'Listo' : 'Cerrar') + '</button></div>';
        document.body.appendChild(ov);
        ov.querySelector('.cerrar').addEventListener('click', cerrar);
        var estado = ov.querySelector('#lpEscEstado');
        function decir(t, cls) { if (!estado) return; estado.textContent = t; estado.className = 'estado ' + (cls || ''); }

        cargar().then(function () {
            if (!ov) return;
            var formatos = window.Html5QrcodeSupportedFormats;
            var cfg = { verbose: false };
            if (formatos) cfg.formatsToSupport = [formatos.CODE_128, formatos.EAN_13, formatos.EAN_8, formatos.UPC_A, formatos.UPC_E, formatos.CODE_39, formatos.QR_CODE];
            cfg.experimentalFeatures = { useBarCodeDetectorIfSupported: true };
            lector = new Html5Qrcode('lpEscCam', cfg);
            return lector.start({ facingMode: 'environment' }, { fps: 12, qrbox: function (w, h) { return { width: Math.floor(w * .84), height: Math.floor(h * .45) }; } },
                function (texto) {
                    texto = String(texto || '').trim();
                    var ahora = Date.now();
                    if (!texto || (texto === ultimo && ahora - ultimoT < 1800)) return; // no repetir la misma lectura seguida
                    ultimo = texto; ultimoT = ahora;
                    pitido();
                    decir('✓ ' + texto, 'ok');
                    if (op.alLeer) op.alLeer(texto);
                    if (!op.continuo) setTimeout(cerrar, 250);
                }, function () {}
            ).then(function () { decir('Buscando código…'); });
        }).catch(function (e) {
            var m = String(e && (e.message || e) || '');
            if (/Permission|NotAllowed/i.test(m)) decir('Permita el uso de la cámara para escanear (en el candado de la barra de direcciones).', 'mal');
            else if (/NotFound|no camera|Requested device/i.test(m)) decir('No se encontró una cámara en este dispositivo.', 'mal');
            else if (!window.Html5Qrcode) decir('No se pudo cargar el escáner. Revise la conexión.', 'mal');
            else decir('No se pudo abrir la cámara. Intente de nuevo.', 'mal');
        });
    }

    // <button data-escanear="idInput" [data-continuo]>: escribe el código en el campo y lo busca
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-escanear]'); if (!b) return;
        e.preventDefault();
        var inp = document.getElementById(b.dataset.escanear);
        abrir({
            titulo: b.dataset.titulo, continuo: b.hasAttribute('data-continuo'),
            alLeer: function (c) {
                if (!inp) return;
                inp.value = c;
                inp.dispatchEvent(new Event('input', { bubbles: true }));
                inp.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true }));
                if (b.hasAttribute('data-enviar') && inp.form) inp.form.submit();
            }
        });
    });

    window.LPEscaner = { abrir: abrir, cerrar: cerrar };
})();
