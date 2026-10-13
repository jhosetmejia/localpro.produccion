/* LocalPro: ayudas en los campos de los formularios (se activan solas en todo el sistema)
   - Imágenes: al elegirla se revisa de una vez; si pesa mucho se achica sola en el celular/PC antes de enviar.
     Límite por campo con data-max-kb (por defecto 2000 KB). data-sin-comprimir para dejarla igual.
   - Cédula / documento: solo números (o letras y números si es pasaporte o extranjería) y con el largo correcto.
   - Celular / WhatsApp: solo números y la cantidad exacta de dígitos del país (Colombia: 10).
   Para excluir un campo: data-libre. */
(function () {
    if (window.__lpCampos) return;
    window.__lpCampos = true;

    function aviso(m, t) { if (window.LP && LP.toast) LP.toast(m, t || 'error'); }
    function pais(el) {
        var f = el && el.form, s = f && f.querySelector('select[name="pais"]');
        return ((s && s.value) || window.LP_PAIS || 'CO').toUpperCase();
    }

    // ---------- Mensaje debajo del campo ----------
    var css = document.createElement('style');
    css.textContent = '.lpc-err{display:block;color:#c0264f;font-weight:700;font-size:.8rem;margin-top:.3rem}.lpc-mal{border-color:#e0457b!important;box-shadow:0 0 0 3px rgba(224,69,123,.12)!important}' +
        '.lpc-ok{display:block;color:#11702f;font-weight:700;font-size:.8rem;margin-top:.3rem}';
    document.head.appendChild(css);
    function marcar(el, msg) {
        var n = el.__lpcMsg;
        if (!msg) { el.classList.remove('lpc-mal'); if (n) { n.remove(); el.__lpcMsg = null; } return; }
        el.classList.add('lpc-mal');
        if (!n) { n = document.createElement('small'); n.className = 'lpc-err'; (el.closest('.input-group') || el).insertAdjacentElement('afterend', n); el.__lpcMsg = n; }
        n.className = 'lpc-err'; n.textContent = msg;
    }
    function nota(el, msg) {
        var n = el.__lpcMsg;
        if (!n) { n = document.createElement('small'); (el.closest('.input-group') || el).insertAdjacentElement('afterend', n); el.__lpcMsg = n; }
        n.className = 'lpc-ok'; n.textContent = msg; el.classList.remove('lpc-mal');
    }

    // ---------- Celular ----------
    // Dígitos del número nacional por país [mínimo, máximo] e indicativo
    var TEL = { CO: [10, 10, '57'], CR: [8, 8, '506'], SV: [8, 8, '503'], EC: [9, 10, '593'], PE: [9, 9, '51'], MX: [10, 10, '52'], CL: [9, 9, '56'], AR: [10, 11, '54'], VE: [10, 11, '58'], BO: [8, 8, '591'], PY: [9, 10, '595'], UY: [8, 9, '598'], PA: [8, 8, '507'], GT: [8, 8, '502'], HN: [8, 8, '504'], NI: [8, 8, '505'], DO: [10, 10, '1'], ES: [9, 9, '34'], US: [10, 10, '1'] };
    var SEL_TEL = 'input[type="tel"],input[name="telefono"],input[name="cliente_telefono"],input[name="celular"],input[name="whatsapp"],#ncTel,#actTel,#okTel,#cliTel,input[data-celular]';
    function limpiarTel(el) {
        var r = TEL[pais(el)] || [7, 15, ''];
        var v = el.value.replace(/\D/g, '');
        // Si lo pegaron con indicativo (+57 300…) se quita
        if (r[2] && v.length > r[1] && v.indexOf(r[2]) === 0) v = v.slice(r[2].length);
        if (v.length > r[1]) v = v.slice(0, r[1]);
        if (v !== el.value) el.value = v;
        el.setAttribute('maxlength', r[1]);
        return v;
    }
    function revisarTel(el, alSalir) {
        var v = limpiarTel(el), p = pais(el), r = TEL[p] || [7, 15];
        if (!v) { marcar(el, el.required && alSalir ? 'Escriba el número.' : null); return !el.required; }
        if (v.length < r[0]) { if (alSalir) marcar(el, 'Faltan ' + (r[0] - v.length) + ' dígitos: el número debe tener ' + (r[0] === r[1] ? r[0] : r[0] + ' a ' + r[1]) + ' dígitos.'); return false; }
        if (p === 'CO' && !/^(3|60)/.test(v)) { if (alSalir) marcar(el, 'En Colombia el celular empieza por 3 (o 60 si es fijo).'); return false; }
        marcar(el, null); return true;
    }

    // Indicativo fijo delante del campo (+57 si es Colombia): sale solo según el país y cambia si cambian el país
    var BANDERA = { CO: '🇨🇴', CR: '🇨🇷', SV: '🇸🇻', EC: '🇪🇨', PE: '🇵🇪', MX: '🇲🇽', CL: '🇨🇱', AR: '🇦🇷', VE: '🇻🇪', BO: '🇧🇴', PY: '🇵🇾', UY: '🇺🇾', PA: '🇵🇦', GT: '🇬🇹', HN: '🇭🇳', NI: '🇳🇮', DO: '🇩🇴', ES: '🇪🇸', US: '🇺🇸' };
    css.textContent += '.lpc-tel{position:relative;display:block;width:100%}.lpc-tel>.lpc-ind{position:absolute;left:12px;top:50%;transform:translateY(-50%);font-weight:800;color:#151a17;pointer-events:none;white-space:nowrap;font-size:.95em;line-height:1}' +
        '.lpc-tel>.lpc-ind:after{content:"";display:inline-block;width:1px;height:1.1em;background:#cfd8d2;margin-left:8px;vertical-align:middle}';
    function prefijo(el) {
        if (el.closest('[data-sin-indicativo]') || el.hasAttribute('data-sin-indicativo')) return;
        var p = pais(el), r = TEL[p] || [7, 15, ''];
        var txt = r[2] ? (BANDERA[p] ? BANDERA[p] + ' ' : '') + '+' + r[2] : '';
        // Que el navegador autocomplete el número nacional (sin +1, +57…)
        el.setAttribute('autocomplete', 'tel-national');
        el.setAttribute('inputmode', 'numeric');
        var grupo = el.closest('.input-group');
        var chip = el.__lpcInd;
        if (!chip) {
            if (grupo) {
                chip = document.createElement('span'); chip.className = 'input-group-text fw-bold lpc-ind-g';
                el.insertAdjacentElement('beforebegin', chip);
            } else {
                var w = document.createElement('span'); w.className = 'lpc-tel';
                el.parentNode.insertBefore(w, el); w.appendChild(el);
                chip = document.createElement('span'); chip.className = 'lpc-ind';
                w.insertBefore(chip, el);
                el.__lpcPad = parseFloat(getComputedStyle(el).paddingLeft) || 12;
            }
            el.__lpcInd = chip;
        }
        chip.textContent = txt;
        chip.style.display = txt ? '' : 'none';
        if (!grupo) el.style.paddingLeft = txt ? ((chip.offsetWidth || txt.length * 9) + 22) + 'px' : '';
        if (!el.placeholder || /^\+?\d/.test(el.placeholder)) el.placeholder = r[0] === r[1] ? r[0] + ' dígitos' : r[0] + ' a ' + r[1] + ' dígitos';
    }
    function prepararTels(raiz) {
        (raiz || document).querySelectorAll(SEL_TEL).forEach(function (t) {
            if (!esTel(t)) return;
            prefijo(t);
            if (t.value) { quitarOtroIndicativo(t); limpiarTel(t); }
        });
    }
    // Si el navegador autocompletó con otro país (+1 207…), se quita ese indicativo
    function quitarOtroIndicativo(el) {
        var v = el.value.trim();
        var r = TEL[pais(el)] || [7, 15, ''];
        if (!/^\+/.test(v) || !r[2]) return;
        var d = v.replace(/\D/g, '');
        if (d.indexOf(r[2]) === 0) return; // ya trae el del país: limpiarTel lo quita
        // Otro indicativo: se dejan solo los últimos dígitos del número nacional
        el.value = d.slice(-r[1]);
    }

    // ---------- Cédula / documento ----------
    var SEL_DOC = 'input[name="numero_documento"],input[name="documento"],input[name="cedula"],#ncDoc,#actDoc,input[data-cedula]';
    function tipoDoc(el) {
        var f = el.form || el.closest('.md,.modal,form'), s = f && f.querySelector('select[name="documento_id"],select[name="tipo_documento"]');
        var t = s && s.options[s.selectedIndex] ? s.options[s.selectedIndex].text.toLowerCase() : '';
        if (/pasaporte|extranj|pep|ppt/.test(t)) return 'alfa';
        if (/nit|ruc/.test(t)) return 'nit';
        return pais(el) === 'CO' ? 'cc' : 'otro';
    }
    var DOC = { cc: [6, 10, /\D/g, 'La cédula tiene de 6 a 10 números'], nit: [9, 10, /\D/g, 'El NIT tiene 9 o 10 números (sin el dígito de verificación)'], alfa: [5, 15, /[^0-9A-Za-z]/g, 'El documento tiene de 5 a 15 letras o números'], otro: [5, 15, /[^0-9A-Za-z]/g, 'El documento tiene de 5 a 15 caracteres'] };
    function limpiarDoc(el) {
        var d = DOC[tipoDoc(el)];
        var v = el.value;
        // Placeholders internos del sistema (cliente sin cédula) no se tocan
        if (/^(TEL-|IMP-|C-)/i.test(v)) return v;
        v = v.replace(d[2], '').toUpperCase();
        if (v.length > d[1]) v = v.slice(0, d[1]);
        if (v !== el.value) el.value = v;
        el.setAttribute('maxlength', d[1]);
        if (d[2].source === '\\D') el.setAttribute('inputmode', 'numeric'); else el.removeAttribute('inputmode');
        return v;
    }
    function revisarDoc(el, alSalir) {
        var v = limpiarDoc(el), d = DOC[tipoDoc(el)];
        if (!v || /^(TEL-|IMP-|C-)/i.test(v)) { marcar(el, null); return true; }
        if (/^0/.test(v) && tipoDoc(el) === 'cc') { if (alSalir) marcar(el, 'La cédula no empieza por 0.'); return false; }
        if (v.length < d[0]) { if (alSalir) marcar(el, d[3] + '.'); return false; }
        marcar(el, null); return true;
    }

    function esTel(el) { return el.matches && el.matches(SEL_TEL) && !el.hasAttribute('data-libre') && el.type !== 'hidden'; }
    function esDoc(el) { return el.matches && el.matches(SEL_DOC) && !el.hasAttribute('data-libre') && el.type !== 'hidden'; }

    document.addEventListener('input', function (e) {
        var el = e.target;
        if (esTel(el)) { quitarOtroIndicativo(el); limpiarTel(el); if (el.classList.contains('lpc-mal')) revisarTel(el, false); }
        else if (esDoc(el)) { limpiarDoc(el); if (el.classList.contains('lpc-mal')) revisarDoc(el, false); }
    }, true);
    document.addEventListener('blur', function (e) {
        var el = e.target;
        if (esTel(el)) revisarTel(el, true); else if (esDoc(el)) revisarDoc(el, true);
    }, true);
    document.addEventListener('change', function (e) {
        // Al cambiar el tipo de documento se vuelve a revisar el número
        if (e.target.matches && e.target.matches('select[name="documento_id"],select[name="tipo_documento"],select[name="pais"]')) {
            var f = e.target.form; if (!f) return;
            f.querySelectorAll(SEL_DOC).forEach(function (d) { if (esDoc(d) && d.value) revisarDoc(d, true); });
            f.querySelectorAll(SEL_TEL).forEach(function (t) { if (esTel(t)) { prefijo(t); if (t.value) revisarTel(t, true); } });
        } else if (esTel(e.target)) {
            // Autocompletado del navegador
            quitarOtroIndicativo(e.target); limpiarTel(e.target);
        }
    }, true);

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { prepararTels(); });
    else prepararTels();
    // Campos que aparecen después (ventanas, formularios armados con JS) y autocompletado tardío
    document.addEventListener('focusin', function (e) { if (esTel(e.target)) prefijo(e.target); }, true);
    setTimeout(prepararTels, 800);
    window.LP_prepararTels = prepararTels;

    // ---------- Imágenes: se revisan y se achican al elegirlas ----------
    function esImagen(el) {
        if (!el.matches || !el.matches('input[type="file"]') || el.hasAttribute('data-sin-comprimir')) return false;
        var a = (el.getAttribute('accept') || '').toLowerCase();
        return a.indexOf('image') >= 0 || /\b(img|imagen|logo|foto)/.test(el.name || '');
    }
    function kb(n) { return n >= 1048576 ? (n / 1048576).toFixed(1).replace('.', ',') + ' MB' : Math.round(n / 1024) + ' KB'; }
    function cargar(file) {
        return new Promise(function (ok, mal) {
            var u = URL.createObjectURL(file), im = new Image();
            im.onload = function () { ok(im); setTimeout(function () { URL.revokeObjectURL(u); }, 1000); };
            im.onerror = function () { mal(new Error('img')); };
            im.src = u;
        });
    }
    function aBlob(cv, tipo, q) { return new Promise(function (ok) { cv.toBlob(ok, tipo, q); }); }
    async function achicar(file, maxBytes, aceptaPng) {
        var im = await cargar(file);
        var lado = 1600, png = aceptaPng && file.type === 'image/png';
        for (var intento = 0; intento < 6; intento++) {
            var e = Math.min(1, lado / Math.max(im.naturalWidth, im.naturalHeight));
            var cv = document.createElement('canvas');
            cv.width = Math.max(1, Math.round(im.naturalWidth * e)); cv.height = Math.max(1, Math.round(im.naturalHeight * e));
            var cx = cv.getContext('2d');
            if (!png) { cx.fillStyle = '#fff'; cx.fillRect(0, 0, cv.width, cv.height); }
            cx.drawImage(im, 0, 0, cv.width, cv.height);
            var b = await aBlob(cv, png ? 'image/png' : 'image/jpeg', png ? undefined : (intento < 2 ? 0.85 : 0.75));
            if (b && b.size <= maxBytes) {
                var nom = (file.name || 'imagen').replace(/\.[^.]+$/, '') + (png ? '.png' : '.jpg');
                return new File([b], nom, { type: b.type, lastModified: Date.now() });
            }
            if (png && intento >= 1) png = false; // el PNG no baja más: se pasa a JPG
            lado = Math.round(lado * 0.8);
        }
        return null;
    }
    document.addEventListener('change', async function (e) {
        var el = e.target;
        if (!esImagen(el) || !el.files || !el.files.length || el.__lpcCambio) return;
        var max = (parseInt(el.getAttribute('data-max-kb'), 10) || 2000) * 1024;
        var f = el.files[0];
        if (!/^image\//.test(f.type)) {
            el.value = ''; marcar(el, 'Ese archivo no es una imagen. Elija una foto JPG o PNG.'); aviso('Ese archivo no es una imagen.'); return;
        }
        var acepta = (el.getAttribute('accept') || '');
        var formatoOk = !acepta || acepta === 'image/*' || acepta.indexOf(f.type) >= 0;
        if (f.size <= max && formatoOk && f.type !== 'image/heic') { marcar(el, null); return; }
        el.__lpcProc = true; el.dataset.procesando = '1';
        nota(el, 'Optimizando la imagen (' + kb(f.size) + ')…');
        try {
            var nuevo = await achicar(f, max, acepta === 'image/*' || acepta.indexOf('png') >= 0);
            if (!nuevo) throw new Error('grande');
            var dt = new DataTransfer(); dt.items.add(nuevo); el.files = dt.files;
            nota(el, '✓ Imagen lista: ' + kb(f.size) + ' → ' + kb(nuevo.size));
            aviso('Imagen optimizada: ' + kb(f.size) + ' → ' + kb(nuevo.size), 'ok');
            el.__lpcCambio = true; el.dispatchEvent(new Event('change', { bubbles: true })); el.__lpcCambio = false;
        } catch (x) {
            el.value = '';
            marcar(el, 'La imagen pesa ' + kb(f.size) + ' y no se pudo achicar. Elija otra de máximo ' + kb(max) + '.');
            aviso('La imagen es muy pesada (' + kb(f.size) + '). Elija otra.');
        } finally { el.__lpcProc = false; delete el.dataset.procesando; }
    }, true);

    // ---------- Antes de enviar: revisa todo y no deja perder lo escrito ----------
    document.addEventListener('submit', function (e) {
        var f = e.target, malo = null;
        f.querySelectorAll('input[type="file"]').forEach(function (i) { if (i.__lpcProc) malo = malo || i; });
        if (malo) { e.preventDefault(); e.stopImmediatePropagation(); aviso('Espere un momento: estamos optimizando la imagen.', 'info'); return; }
        f.querySelectorAll(SEL_TEL).forEach(function (t) { if (esTel(t) && (t.value || t.required) && !revisarTel(t, true)) malo = malo || t; });
        f.querySelectorAll(SEL_DOC).forEach(function (d) { if (esDoc(d) && !revisarDoc(d, true)) malo = malo || d; });
        if (malo) {
            e.preventDefault(); e.stopImmediatePropagation();
            malo.focus(); malo.scrollIntoView({ block: 'center', behavior: 'smooth' });
            aviso(malo.__lpcMsg ? malo.__lpcMsg.textContent : 'Revise el campo marcado.');
        }
    }, true);

    // Para los formularios hechos con JavaScript (caja, separados…)
    window.LPCampos = {
        celularValido: function (el) { return revisarTel(el, true); },
        documentoValido: function (el) { return revisarDoc(el, true); }
    };
})();
