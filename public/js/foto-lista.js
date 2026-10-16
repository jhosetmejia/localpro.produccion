/*
 * Deja lista cualquier foto antes de subirla: la convierte a WebP (liviano, buena calidad) en el
 * mismo navegador. Sirve para AVIF (Temu), HEIC (iPhone), GIF, BMP, PNG o JPG pesados.
 * Así nunca sale "debe ser una imagen" y el servidor guarda fotos livianas.
 * Si el navegador no sabe hacer WebP, la deja en JPG.
 * Uso: <input type="file" data-foto-lista> (opcional data-max-lado="1600")
 */
(function () {
    var MAX_BYTES = 600 * 1024; // un WebP liviano de hasta este peso se deja tal cual

    function aviso(input, txt) {
        var id = input.id + '-aviso', el = document.getElementById(id);
        if (!el) {
            el = document.createElement('small');
            el.id = id; el.className = 'd-block mt-1 fw-bold';
            (input.closest('label') || input).insertAdjacentElement('afterend', el);
        }
        el.style.color = txt && /no se pudo/i.test(txt) ? '#b42318' : '#11702f';
        el.textContent = txt || '';
    }

    function cargar(archivo) {
        if (window.createImageBitmap) {
            return createImageBitmap(archivo).catch(function () { return porImagen(archivo); });
        }
        return porImagen(archivo);
    }
    function porImagen(archivo) {
        return new Promise(function (ok, mal) {
            var im = new Image(), url = URL.createObjectURL(archivo);
            im.onload = function () { ok(im); };
            im.onerror = function () { URL.revokeObjectURL(url); mal(); };
            im.src = url;
        });
    }

    function preparar(input) {
        if (input.dataset.fotoListaOk) return;
        input.dataset.fotoListaOk = '1';
        input.addEventListener('change', function (e) {
            if (input.dataset.convirtiendo) return;
            var a = input.files && input.files[0]; if (!a) return;
            var sirve = (a.type || '').toLowerCase() === 'image/webp' && a.size <= MAX_BYTES;
            aviso(input, '');
            if (sirve) return;
            // Se detiene el cambio hasta tener la foto convertida
            e.stopImmediatePropagation();
            aviso(input, 'Preparando la foto…');
            var lado = parseInt(input.dataset.maxLado || '1600', 10);
            cargar(a).then(function (img) {
                var w = img.width || img.naturalWidth, h = img.height || img.naturalHeight;
                var k = Math.min(1, lado / Math.max(w, h));
                var c = document.createElement('canvas');
                c.width = Math.round(w * k); c.height = Math.round(h * k);
                var x = c.getContext('2d');
                x.imageSmoothingQuality = 'high';
                x.drawImage(img, 0, 0, c.width, c.height);
                function listo(b, tipo, ext) {
                    if (!b) { aviso(input, 'No se pudo usar esa foto. Tómele un pantallazo y suba el pantallazo.'); input.value = ''; return; }
                    // Si la original ya era más liviana y sirve, se deja la original
                    if (b.size >= a.size && ['image/jpeg', 'image/png', 'image/webp'].indexOf(a.type) !== -1 && a.size <= 3.5 * 1024 * 1024 && k === 1) { aviso(input, ''); return terminar(a); }
                    terminar(new File([b], (a.name || 'foto').replace(/\.[^.]+$/, '') + '.' + ext, { type: tipo }), b.size);
                }
                function terminar(f, peso) {
                    var dt = new DataTransfer();
                    dt.items.add(f);
                    input.files = dt.files;
                    if (peso) aviso(input, 'Foto lista ✓ (' + Math.max(1, Math.round(peso / 1024)) + ' KB)');
                    input.dataset.convirtiendo = '1';
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    delete input.dataset.convirtiendo;
                }
                c.toBlob(function (b) {
                    if (b && b.type === 'image/webp') return listo(b, 'image/webp', 'webp');
                    // Navegador sin WebP: JPG con fondo blanco
                    var c2 = document.createElement('canvas'); c2.width = c.width; c2.height = c.height;
                    var x2 = c2.getContext('2d'); x2.fillStyle = '#fff'; x2.fillRect(0, 0, c2.width, c2.height); x2.drawImage(c, 0, 0);
                    c2.toBlob(function (j) { listo(j, 'image/jpeg', 'jpg'); }, 'image/jpeg', 0.85);
                }, 'image/webp', 0.82);
            }).catch(function () {
                input.value = '';
                aviso(input, 'No se pudo abrir esa foto (formato no compatible). Tómele un pantallazo y suba el pantallazo.');
            });
        }, true);
    }

    function iniciar() { document.querySelectorAll('input[type=file][data-foto-lista]').forEach(preparar); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();
    window.LPFotoLista = { preparar: preparar };
})();
