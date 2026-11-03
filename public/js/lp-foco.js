/* LocalPro: guía con foco (como CapCut). Oscurece la pantalla, ilumina el botón o campo que toca usar
   y muestra un globo amarillo con lo que hay que hacer, paso a paso.
   Uso: LPFoco.iniciar([{ sel: 'input[name=logo]', titulo: 'Su logo', texto: 'Toque aquí…' }, …], { fin: fn, boton: 'Listo' })
   Si un selector no está en la página, el globo sale en el centro (nunca se queda trabado). */
(function () {
    if (window.LPFoco) return;
    var css = document.createElement('style');
    css.textContent =
        '.lpf-velo{position:fixed;inset:0;z-index:2147483000;pointer-events:auto}' +
        '.lpf-luz{position:fixed;z-index:2147483001;border-radius:14px;box-shadow:0 0 0 9999px rgba(8,10,9,.62),0 0 0 3px #ffe14d;transition:all .25s ease;pointer-events:none}' +
        '.lpf-globo{position:fixed;z-index:2147483002;width:min(320px,calc(100vw - 24px));background:#ffe14d;color:#151a17;border-radius:16px;padding:14px 16px 12px;box-shadow:0 18px 40px -12px rgba(0,0,0,.55);font-size:.92rem;line-height:1.35;transition:top .25s,left .25s}' +
        '.lpf-globo b{display:block;font-size:1rem;margin-bottom:4px;padding-right:22px}' +
        '.lpf-globo .x{position:absolute;top:8px;right:10px;border:0;background:none;font-size:1.1rem;cursor:pointer;color:#151a17;opacity:.7}' +
        '.lpf-globo .pie{display:flex;align-items:center;justify-content:space-between;margin-top:10px;gap:8px}' +
        '.lpf-globo .n{font-size:.75rem;font-weight:800;opacity:.7}' +
        '.lpf-globo .ok{background:#0b0d0c;color:#fff;border:0;border-radius:10px;padding:.45rem 1rem;font-weight:800;cursor:pointer}' +
        '.lpf-globo .pico{position:absolute;width:14px;height:14px;background:#ffe14d;transform:rotate(45deg)}';
    document.head.appendChild(css);

    function visible(el) { var r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0 && getComputedStyle(el).visibility !== 'hidden'; }
    function buscar(sel) {
        if (!sel) return null;
        try {
            var l = document.querySelectorAll(sel);
            for (var i = 0; i < l.length; i++) {
                var el = l[i];
                // Los archivos y casillas suelen estar escondidos: se ilumina su etiqueta o su caja
                if (!visible(el)) el = (el.id && document.querySelector('label[for="' + el.id + '"]')) || el.closest('label, .form-check, .form-switch, .mb-3, div') || el;
                if (visible(el)) return el;
            }
        } catch (e) {}
        return null;
    }

    window.LPFoco = {
        iniciar: function (pasos, op) {
            op = op || {};
            pasos = (pasos || []).filter(Boolean);
            if (!pasos.length) return;
            var i = 0, el = null;
            var velo = document.createElement('div'); velo.className = 'lpf-velo';
            var luz = document.createElement('div'); luz.className = 'lpf-luz';
            var globo = document.createElement('div'); globo.className = 'lpf-globo';
            globo.setAttribute('role', 'dialog');
            document.body.append(velo, luz, globo);

            function cerrar(terminado) {
                velo.remove(); luz.remove(); globo.remove();
                window.removeEventListener('resize', ubicar); window.removeEventListener('scroll', ubicar, true);
                if (op.fin) op.fin(terminado);
            }
            function ubicar() {
                var r = el ? el.getBoundingClientRect() : null, pad = 6, W = innerWidth, H = innerHeight;
                if (!r) {
                    luz.style.cssText = 'top:50%;left:50%;width:0;height:0';
                    globo.style.top = Math.max(12, H / 2 - globo.offsetHeight / 2) + 'px'; globo.style.left = Math.max(12, W / 2 - globo.offsetWidth / 2) + 'px';
                    return;
                }
                luz.style.cssText = 'top:' + (r.top - pad) + 'px;left:' + (r.left - pad) + 'px;width:' + (r.width + pad * 2) + 'px;height:' + (r.height + pad * 2) + 'px';
                var gw = globo.offsetWidth, gh = globo.offsetHeight, top, left, pico = globo.querySelector('.pico');
                // A la derecha si cabe (como CapCut), si no abajo, si no arriba
                if (r.right + 18 + gw < W) { left = r.right + 18; top = Math.min(Math.max(12, r.top), H - gh - 12); pico.style.cssText = 'left:-6px;top:' + Math.max(14, Math.min(gh - 28, r.top + r.height / 2 - top - 7)) + 'px'; }
                else if (r.bottom + 16 + gh < H) { top = r.bottom + 16; left = Math.min(Math.max(12, r.left), W - gw - 12); pico.style.cssText = 'top:-6px;left:' + Math.max(14, Math.min(gw - 28, r.left + 20 - left)) + 'px'; }
                else { top = Math.max(12, r.top - gh - 16); left = Math.min(Math.max(12, r.left), W - gw - 12); pico.style.cssText = 'bottom:-6px;left:' + Math.max(14, Math.min(gw - 28, r.left + 20 - left)) + 'px'; }
                globo.style.top = top + 'px'; globo.style.left = left + 'px';
            }
            function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); }
            function mostrar() {
                var p = pasos[i], ultimo = i === pasos.length - 1;
                el = buscar(p.sel);
                globo.innerHTML = '<span class="pico"></span><button type="button" class="x" aria-label="Cerrar guía">✕</button>' +
                    '<b>' + esc(p.titulo) + '</b><div>' + esc(p.texto) + '</div>' +
                    '<div class="pie"><span class="n">' + (pasos.length > 1 ? (i + 1) + ' de ' + pasos.length : '') + '</span>' +
                    '<button type="button" class="ok">' + esc(ultimo ? (op.boton || 'Entendido') : 'Siguiente') + '</button></div>';
                if (!el) globo.querySelector('.pico').style.display = 'none';
                globo.querySelector('.x').onclick = function () { cerrar(false); };
                globo.querySelector('.ok').onclick = function () { if (ultimo) cerrar(true); else { i++; mostrar(); } };
                if (el) el.scrollIntoView({ block: 'center', behavior: 'smooth' });
                ubicar(); setTimeout(ubicar, 350);
                globo.querySelector('.ok').focus({ preventScroll: true });
            }
            // Tocar lo iluminado funciona normal: el velo deja pasar el clic solo en esa zona
            velo.addEventListener('click', function (e) {
                if (!el) return;
                var r = el.getBoundingClientRect();
                if (e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom) { cerrar(false); el.focus && el.focus(); el.click && el.click(); }
            });
            document.addEventListener('keydown', function k(e) { if (e.key === 'Escape') { document.removeEventListener('keydown', k); cerrar(false); } });
            window.addEventListener('resize', ubicar); window.addEventListener('scroll', ubicar, true);
            mostrar();
        }
    };
})();
