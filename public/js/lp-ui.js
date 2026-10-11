/* LocalPro: avisos, confirmaciones y campos de dinero con separador de miles.
   - LP.toast(mensaje, 'ok' | 'error' | 'info')
   - LP.confirmar({ titulo, texto, ok, cancelar, peligro }) -> Promise<boolean>
   - LP.pedir({ titulo, texto, placeholder, ok }) -> Promise<string|null>
   - <form data-confirm="¿Seguro?" data-confirm-ok="Sí, borrar" data-peligro> pide confirmación antes de enviar
   - <input data-dinero> muestra 1.250.000 (o 1,250.00 según el país) y envía el número limpio */
(function () {
    if (window.LP) return;
    var M = window.LP_MONEDA || { miles: '.', decimal: ',', decimales: 0, simbolo: '$' };

    // ---------- Estilos ----------
    var css = '' +
        '.lpx-ov{position:fixed;inset:0;z-index:2000;background:rgba(11,13,12,.55);display:flex;align-items:center;justify-content:center;padding:18px;opacity:0;transition:opacity .18s}' +
        '.lpx-ov.on{opacity:1}' +
        '.lpx-md{background:#fff;color:#151a17;border-radius:26px;width:min(420px,100%);padding:24px 22px 20px;box-shadow:0 40px 90px -30px rgba(0,0,0,.55);transform:translateY(14px) scale(.98);transition:transform .2s cubic-bezier(.2,.8,.2,1);font-family:inherit}' +
        '.lpx-ov.on .lpx-md{transform:none}' +
        '.lpx-ic{width:54px;height:54px;border-radius:18px;display:grid;place-items:center;font-size:1.6rem;font-weight:900;margin-bottom:14px;background:#e8f8ec;color:#16b34a}' +
        '.lpx-md.peligro .lpx-ic{background:#ffe9f3;color:#d61f69}' +
        '.lpx-md h4{font-size:1.2rem;font-weight:800;margin:0 0 6px;line-height:1.25}' +
        '.lpx-md p{color:#5b665e;font-weight:500;margin:0 0 18px;line-height:1.45;font-size:.95rem}' +
        '.lpx-md input{width:100%;border:2px solid #e3efe5;border-radius:14px;padding:.75rem .9rem;font-size:1rem;font-weight:600;margin:-4px 0 16px;outline:none}' +
        '.lpx-md input:focus{border-color:#2fd15a}' +
        '.lpx-bt{display:flex;gap:10px}' +
        '.lpx-bt button{flex:1;height:52px;border:0;border-radius:16px;font-weight:800;font-size:1rem;cursor:pointer;font-family:inherit}' +
        '.lpx-no{background:#f1f5f2;color:#151a17}' +
        '.lpx-si{background:linear-gradient(135deg,#a6f34a 0%,#2fd15a 45%,#16b34a 100%);color:#0b0d0c}' +
        '.lpx-md.peligro .lpx-si{background:#0b0d0c;color:#fff}' +
        '.lpx-ts{position:fixed;top:16px;left:50%;transform:translateX(-50%);z-index:2100;display:flex;flex-direction:column;gap:8px;align-items:center;pointer-events:none;width:calc(100% - 24px);max-width:460px}' +
        '.lpx-t{pointer-events:auto;display:flex;gap:10px;align-items:center;background:#0b0d0c;color:#fff;border-radius:16px;padding:.8rem 1rem;font-weight:700;font-size:.93rem;box-shadow:0 20px 40px -18px rgba(0,0,0,.6);transform:translateY(-20px);opacity:0;transition:transform .25s,opacity .25s;max-width:100%}' +
        '.lpx-t.on{transform:none;opacity:1}' +
        '.lpx-t b{flex:none;width:24px;height:24px;border-radius:50%;display:grid;place-items:center;background:#a6f34a;color:#0b0d0c;font-size:.8rem}' +
        '.lpx-t.error{background:#9b1c5a}.lpx-t.error b{background:#fff;color:#9b1c5a}' +
        '.lpx-t.info b{background:#fff;color:#0b0d0c}';
    var st = document.createElement('style'); st.textContent = css; document.head.appendChild(st);

    function el(tag, cls, txt) { var e = document.createElement(tag); if (cls) e.className = cls; if (txt != null) e.textContent = txt; return e; }

    // ---------- Avisos ----------
    var caja;
    function toast(msg, tipo) {
        if (!caja) { caja = el('div', 'lpx-ts'); document.body.appendChild(caja); }
        tipo = tipo || 'ok';
        var t = el('div', 'lpx-t ' + tipo);
        t.appendChild(el('b', null, tipo === 'error' ? '!' : (tipo === 'info' ? 'i' : '✓')));
        t.appendChild(el('span', null, msg));
        caja.appendChild(t);
        requestAnimationFrame(function () { t.classList.add('on'); });
        var quitar = function () { t.classList.remove('on'); setTimeout(function () { t.remove(); }, 260); };
        t.addEventListener('click', quitar);
        setTimeout(quitar, tipo === 'error' ? 4500 : 2600);
    }

    // ---------- Ventana de confirmación ----------
    function ventana(o, conCampo) {
        return new Promise(function (resolve) {
            var ov = el('div', 'lpx-ov'), md = el('div', 'lpx-md' + (o.peligro ? ' peligro' : ''));
            md.setAttribute('role', 'dialog'); md.setAttribute('aria-modal', 'true');
            md.appendChild(el('div', 'lpx-ic', o.icono || (o.peligro ? '!' : '?')));
            md.appendChild(el('h4', null, o.titulo || '¿Está seguro?'));
            if (o.texto) md.appendChild(el('p', null, o.texto));
            var campo;
            if (conCampo) { campo = el('input'); campo.placeholder = o.placeholder || ''; campo.value = o.valor || ''; md.appendChild(campo); }
            var bt = el('div', 'lpx-bt'), no = el('button', 'lpx-no', o.cancelar || 'Cancelar'), si = el('button', 'lpx-si', o.ok || 'Aceptar');
            no.type = si.type = 'button';
            bt.appendChild(no); bt.appendChild(si); md.appendChild(bt); ov.appendChild(md); document.body.appendChild(ov);
            requestAnimationFrame(function () { ov.classList.add('on'); (campo || si).focus(); });
            function fin(v) { document.removeEventListener('keydown', tecla, true); ov.classList.remove('on'); setTimeout(function () { ov.remove(); }, 200); resolve(v); }
            function tecla(e) {
                if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); fin(conCampo ? null : false); }
                else if (e.key === 'Enter') { e.preventDefault(); e.stopPropagation(); si.click(); }
            }
            document.addEventListener('keydown', tecla, true);
            no.onclick = function () { fin(conCampo ? null : false); };
            si.onclick = function () { if (conCampo) { var v = campo.value.trim(); if (!v) { campo.focus(); return; } fin(v); } else fin(true); };
            ov.addEventListener('click', function (e) { if (e.target === ov) fin(conCampo ? null : false); });
        });
    }

    // Formularios con data-confirm
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (!f.matches || !f.matches('form[data-confirm]') || f._lpOk) return;
        e.preventDefault();
        ventana({ titulo: f.dataset.confirm, texto: f.dataset.confirmTexto, ok: f.dataset.confirmOk || 'Sí, continuar', peligro: f.hasAttribute('data-peligro') })
            .then(function (si) {
                if (!si) return;
                f._lpOk = true;
                var b = e.submitter;
                if (f.requestSubmit) f.requestSubmit(b && b.form === f ? b : undefined); else f.submit();
                setTimeout(function () { f._lpOk = false; }, 500);
            });
    }, true);

    // ---------- Dinero con separador de miles ----------
    var proto = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
    function aNumero(txt) {
        txt = String(txt == null ? '' : txt);
        var limpio = '', hayDec = false;
        for (var i = 0; i < txt.length; i++) {
            var c = txt[i];
            if (c >= '0' && c <= '9') limpio += c;
            else if (M.decimales && c === M.decimal && !hayDec) { limpio += '.'; hayDec = true; }
        }
        if (hayDec) { var p = limpio.split('.'); limpio = p[0] + '.' + p[1].slice(0, M.decimales); }
        return limpio;
    }
    function bonito(num, escribiendo) {
        if (num === '' || num == null) return '';
        var s = String(num), p = s.split('.');
        var ent = (p[0] || '0').replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, M.miles);
        if (p.length > 1 && M.decimales) {
            var dec = escribiendo ? p[1] : (p[1] + '0000').slice(0, M.decimales);
            return ent + M.decimal + dec;
        }
        return ent;
    }
    function normal(v) { // valor que venga del servidor ("130000.00") -> "130000"
        if (v === '' || v == null) return '';
        var n = Number(v); if (isNaN(n)) return aNumero(v);
        return M.decimales ? String(Math.round(n * 100) / 100) : String(Math.round(n));
    }

    function prepararDinero(inp) {
        if (inp._lpDinero || inp.type === 'hidden') return;
        inp._lpDinero = true;
        var vis = document.createElement('input');
        vis.type = 'text'; vis.inputMode = M.decimales ? 'decimal' : 'numeric'; vis.autocomplete = 'off';
        vis.className = inp.className; vis.style.cssText = inp.style.cssText;
        vis.placeholder = inp.placeholder ? bonito(normal(inp.placeholder)) || inp.placeholder : '';
        if (inp.required) vis.required = true;
        if (inp.disabled) vis.disabled = true;
        if (inp.id) vis.setAttribute('aria-labelledby', inp.id);
        vis.setAttribute('data-dinero-vista', '');
        var foco = inp.autofocus || document.activeElement === inp;
        inp.parentNode.insertBefore(vis, inp.nextSibling); // después del original: los querySelector('.clase') siguen encontrando el valor limpio
        // Si una <label for="id"> apunta al original, que lleve al visible
        if (inp.id) document.querySelectorAll('label[for="' + inp.id + '"]').forEach(function (l) { l.addEventListener('click', function (e) { e.preventDefault(); vis.focus(); }); });

        proto.set.call(inp, normal(proto.get.call(inp)));
        inp.type = 'hidden';
        vis.value = bonito(proto.get.call(inp));
        // Cuando el código cambia el valor del original, se actualiza lo que se ve
        Object.defineProperty(inp, 'value', {
            configurable: true,
            get: function () { return proto.get.call(inp); },
            set: function (v) { proto.set.call(inp, normal(v)); if (document.activeElement !== vis) vis.value = bonito(proto.get.call(inp)); }
        });
        inp.focus = function () { vis.focus(); };
        inp.select = function () { vis.select(); };

        vis.addEventListener('input', function (e) {
            e.stopPropagation();
            var antes = vis.value, pos = vis.selectionStart, derecha = antes.length - pos;
            var num = aNumero(antes);
            proto.set.call(inp, num);
            vis.value = bonito(num, true);
            var np = Math.max(0, vis.value.length - derecha);
            try { vis.setSelectionRange(np, np); } catch (x) {}
            inp.dispatchEvent(new Event('input', { bubbles: true }));
        });
        vis.addEventListener('change', function (e) { e.stopPropagation(); vis.value = bonito(proto.get.call(inp)); inp.dispatchEvent(new Event('change', { bubbles: true })); });
        vis.addEventListener('blur', function () { vis.value = bonito(proto.get.call(inp)); });
        vis.addEventListener('focus', function () { setTimeout(function () { try { vis.select(); } catch (x) {} }, 0); });
        if (foco) setTimeout(function () { vis.focus(); }, 30);
    }
    function revisar(raiz) { (raiz || document).querySelectorAll('input[data-dinero]').forEach(prepararDinero); }
    function iniciar() {
        revisar();
        new MutationObserver(function (ms) {
            ms.forEach(function (m) { m.addedNodes.forEach(function (n) { if (n.nodeType === 1) { if (n.matches('input[data-dinero]')) prepararDinero(n); else revisar(n); } }); });
        }).observe(document.body, { childList: true, subtree: true });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();

    window.LP = {
        toast: toast,
        confirmar: function (o) { return ventana(typeof o === 'string' ? { titulo: o } : (o || {}), false); },
        pedir: function (o) { return ventana(typeof o === 'string' ? { titulo: o } : (o || {}), true); },
        formatoDinero: function (n) { return bonito(normal(n)); }
    };
})();
