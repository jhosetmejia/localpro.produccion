/* LocalProo: achica las imágenes en el navegador antes de subirlas.
   Sube más rápido (también desde el celular) y no llena el servidor.
   Funciona solo en todos los campos de imagen; para saltarse uno: data-sin-optimizar. */
(function () {
    'use strict';
    var MIN = 250 * 1024; // menos de esto ya está liviana
    function maxPara(input) {
        if (input.dataset.maxPx) return +input.dataset.maxPx;
        var n = (input.name || '').toLowerCase();
        if (n.indexOf('logo') !== -1) return 1000;
        if (n.indexOf('img_path') !== -1) return 1400;
        if (n === 'imagen' || n.indexOf('fondo') !== -1) return 3840;
        return 2048;
    }
    function kb(b) { return b > 1048576 ? (b / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.round(b / 1024) + ' KB'; }
    function aviso(txt) {
        var d = document.createElement('div');
        d.textContent = txt;
        d.style.cssText = 'position:fixed;left:50%;bottom:22px;transform:translateX(-50%);z-index:99999;background:#0f1411;color:#a6f34a;font:600 14px system-ui,sans-serif;padding:10px 16px;border-radius:999px;box-shadow:0 10px 30px rgba(0,0,0,.3);transition:opacity .5s';
        document.body.appendChild(d);
        setTimeout(function () { d.style.opacity = '0'; }, 3200);
        setTimeout(function () { d.remove(); }, 3800);
    }
    function tieneAlfa(ctx, w, h) {
        try {
            var p = ctx.getImageData(0, 0, w, h).data;
            for (var i = 3; i < p.length; i += 4 * 7) if (p[i] < 250) return true;
        } catch (e) {}
        return false;
    }
    function optimizar(file, max) {
        return new Promise(function (ok) {
            if (!/^image\/(jpeg|png|webp)$/.test(file.type) || file.size < MIN) return ok(file);
            var url = URL.createObjectURL(file), img = new Image();
            img.onload = function () {
                var s = Math.min(1, max / Math.max(img.naturalWidth, img.naturalHeight));
                var w = Math.round(img.naturalWidth * s), h = Math.round(img.naturalHeight * s);
                var c = document.createElement('canvas'); c.width = w; c.height = h;
                var ctx = c.getContext('2d');
                ctx.drawImage(img, 0, 0, w, h);
                URL.revokeObjectURL(url);
                // Con transparencia: WebP (la conserva). Sin transparencia: JPG.
                var alfa = file.type !== 'image/jpeg' && tieneAlfa(ctx, w, h);
                var tipo = alfa ? 'image/webp' : 'image/jpeg';
                c.toBlob(function (b) {
                    if (!b || b.size >= file.size) return ok(file);
                    var nombre = file.name.replace(/\.[^.]+$/, '') + (alfa ? '.webp' : '.jpg');
                    ok(new File([b], nombre, { type: tipo, lastModified: Date.now() }));
                }, tipo, alfa ? 0.85 : 0.84);
            };
            img.onerror = function () { URL.revokeObjectURL(url); ok(file); };
            img.src = url;
        });
    }
    document.addEventListener('change', function (e) {
        var input = e.target;
        if (!input || input.type !== 'file' || input.dataset.sinOptimizar !== undefined || input.dataset.optimizado === '1') return;
        if (!input.files || !input.files.length || !window.DataTransfer) return;
        var lista = [].slice.call(input.files);
        if (!lista.some(function (f) { return /^image\/(jpeg|png|webp)$/.test(f.type) && f.size >= MIN; })) return;
        var antes = lista.reduce(function (s, f) { return s + f.size; }, 0);
        var max = maxPara(input);
        var form = input.form, boton = form && form.querySelector('[type=submit],button:not([type])');
        if (boton) boton.disabled = true;
        Promise.all(lista.map(function (f) { return optimizar(f, max); })).then(function (nuevos) {
            var despues = nuevos.reduce(function (s, f) { return s + f.size; }, 0);
            if (despues < antes) {
                var dt = new DataTransfer();
                nuevos.forEach(function (f) { dt.items.add(f); });
                input.files = dt.files;
                // avisa a las vistas previas que el archivo cambió
                input.dataset.optimizado = '1';
                input.dispatchEvent(new Event('change', { bubbles: true }));
                delete input.dataset.optimizado;
                aviso('Imagen optimizada: ' + kb(antes) + ' → ' + kb(despues));
            }
        }).finally(function () { if (boton) boton.disabled = false; });
    }, true);
})();
