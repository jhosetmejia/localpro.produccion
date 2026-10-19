/* LocalPro: avisos, confirmaciones y campos de dinero con separador de miles.
   - LP.toast(mensaje, 'ok' | 'error' | 'info')
   - LP.confirmar({ titulo, texto, ok, cancelar, peligro }) -> Promise<boolean>
   - LP.pedir({ titulo, texto, placeholder, ok }) -> Promise<string|null>
   - <form data-confirm="¿Seguro?" data-confirm-ok="Sí, borrar" data-peligro> pide confirmación antes de enviar
   - <select class="form-select"> se vuelve una lista moderna con buscador (data-nativo para dejarla normal)
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
        '.lpx-ts{position:fixed;top:18px;right:18px;z-index:2100;display:flex;flex-direction:column;gap:10px;align-items:flex-end;pointer-events:none;width:min(380px,calc(100% - 24px))}' +
        '@media(max-width:575px){.lpx-ts{right:12px;left:12px;width:auto;align-items:stretch;top:12px}}' +
        '.lpx-t{pointer-events:auto;position:relative;overflow:hidden;display:flex;gap:12px;align-items:flex-start;width:100%;background:#fff;color:#151a17;border-radius:20px;padding:14px 14px 16px 14px;box-shadow:0 24px 50px -22px rgba(11,13,12,.45),0 0 0 1px rgba(11,13,12,.06);transform:translateX(24px) scale(.97);opacity:0;transition:transform .32s cubic-bezier(.2,.9,.25,1.15),opacity .25s;font-family:inherit;cursor:default}' +
        '@media(max-width:575px){.lpx-t{transform:translateY(-16px) scale(.97)}}' +
        '.lpx-t.on{transform:none;opacity:1}' +
        '.lpx-t.out{opacity:0;transform:translateX(24px) scale(.97)}' +
        '.lpx-ti{flex:none;width:40px;height:40px;border-radius:14px;display:grid;place-items:center;background:linear-gradient(135deg,#a6f34a 0%,#2fd15a 55%,#16b34a 100%);color:#0b0d0c;box-shadow:0 8px 18px -8px rgba(22,179,74,.7)}' +
        '.lpx-ti svg{width:22px;height:22px;fill:none;stroke:currentColor;stroke-width:3;stroke-linecap:round;stroke-linejoin:round}' +
        '.lpx-ti svg path{stroke-dasharray:30;stroke-dashoffset:30;animation:lpxDraw .45s .15s ease-out forwards}' +
        '@keyframes lpxDraw{to{stroke-dashoffset:0}}' +
        '.lpx-tx{flex:1;min-width:0;padding-top:1px}' +
        '.lpx-tx strong{display:block;font-size:.95rem;font-weight:800;line-height:1.2;margin-bottom:2px}' +
        '.lpx-tx span{display:block;font-size:.88rem;font-weight:500;color:#5b665e;line-height:1.4;word-wrap:break-word}' +
        '.lpx-tc{flex:none;width:28px;height:28px;margin:-4px -4px 0 0;border:0;border-radius:10px;background:transparent;color:#9aa59d;font-size:1.15rem;line-height:1;cursor:pointer;display:grid;place-items:center;transition:background .15s,color .15s}' +
        '.lpx-tc:hover{background:#f1f5f2;color:#151a17}' +
        '.lpx-tb{position:absolute;left:0;bottom:0;height:4px;width:100%;background:linear-gradient(90deg,#a6f34a,#2fd15a);transform-origin:left;animation:lpxBar linear forwards}' +
        '.lpx-t:hover .lpx-tb{animation-play-state:paused}' +
        '@keyframes lpxBar{from{transform:scaleX(1)}to{transform:scaleX(0)}}' +
        '.lpx-t.error .lpx-ti{background:linear-gradient(135deg,#ff7ab0,#d61f69);color:#fff;box-shadow:0 8px 18px -8px rgba(214,31,105,.7)}.lpx-t.error .lpx-tb{background:linear-gradient(90deg,#ff7ab0,#d61f69)}' +
        '.lpx-t.info .lpx-ti{background:#0b0d0c;color:#a6f34a;box-shadow:0 8px 18px -8px rgba(0,0,0,.6)}.lpx-t.info .lpx-tb{background:#0b0d0c}' +
        '.lpx-t.aviso .lpx-ti{background:linear-gradient(135deg,#ffd76a,#f5a623);color:#0b0d0c;box-shadow:0 8px 18px -8px rgba(245,166,35,.7)}.lpx-t.aviso .lpx-tb{background:linear-gradient(90deg,#ffd76a,#f5a623)}';
    var st = document.createElement('style'); st.textContent = css; document.head.appendChild(st);

    function el(tag, cls, txt) { var e = document.createElement(tag); if (cls) e.className = cls; if (txt != null) e.textContent = txt; return e; }

    // ---------- Avisos ----------
    var caja;
    var ICONOS = {
        ok: '<svg viewBox="0 0 24 24"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
        error: '<svg viewBox="0 0 24 24"><path d="M12 6v7M12 17.5v.5"/></svg>',
        aviso: '<svg viewBox="0 0 24 24"><path d="M12 6v7M12 17.5v.5"/></svg>',
        info: '<svg viewBox="0 0 24 24"><path d="M12 11v6M12 6.5v.5"/></svg>'
    };
    var TITULOS = { ok: '¡Listo!', error: 'Algo salió mal', aviso: 'Atención', info: 'Para tener en cuenta' };
    // LP.toast(mensaje, tipo, { titulo, duracion })   tipo: 'ok' | 'error' | 'info' | 'aviso'
    function toast(msg, tipo, op) {
        if (msg == null || msg === '') return;
        if (typeof tipo === 'object' && tipo) { op = tipo; tipo = op.tipo; }
        op = op || {};
        tipo = { success: 'ok', exito: 'ok', warning: 'aviso', danger: 'error', question: 'info' }[tipo] || tipo || 'ok';
        if (!ICONOS[tipo]) tipo = 'ok';
        if (!caja || !document.body.contains(caja)) { caja = el('div', 'lpx-ts'); caja.setAttribute('aria-live', 'polite'); document.body.appendChild(caja); }
        while (caja.children.length >= 4) caja.firstChild.remove();
        var dur = op.duracion || (tipo === 'error' ? 5500 : 3400);
        var t = el('div', 'lpx-t ' + tipo); t.setAttribute('role', tipo === 'error' ? 'alert' : 'status');
        var ic = el('div', 'lpx-ti'); ic.innerHTML = ICONOS[tipo]; t.appendChild(ic);
        var tx = el('div', 'lpx-tx');
        tx.appendChild(el('strong', null, op.titulo || TITULOS[tipo]));
        tx.appendChild(el('span', null, String(msg)));
        t.appendChild(tx);
        var x = el('button', 'lpx-tc', '×'); x.type = 'button'; x.setAttribute('aria-label', 'Cerrar'); t.appendChild(x);
        var bar = el('div', 'lpx-tb'); bar.style.animationDuration = dur + 'ms'; t.appendChild(bar);
        caja.appendChild(t);
        requestAnimationFrame(function () { requestAnimationFrame(function () { t.classList.add('on'); }); });
        var ido = false;
        var quitar = function () { if (ido) return; ido = true; t.classList.remove('on'); t.classList.add('out'); setTimeout(function () { t.remove(); }, 300); };
        x.addEventListener('click', quitar);
        bar.addEventListener('animationend', quitar);
        return { cerrar: quitar };
    }

    // Compatibilidad: si alguna pantalla vieja llama Swal.fire, sale con el estilo LocalPro
    if (!window.Swal) {
        var tipoSwal = function (i) { return { success: 'ok', error: 'error', warning: 'aviso', info: 'info', question: 'info' }[i] || 'ok'; };
        var fire = function (a, b, c) {
            var o = typeof a === 'object' ? a : { title: a, text: b, icon: c };
            if (o.showCancelButton) {
                return ventana({ titulo: o.title, texto: o.text || (typeof o.html === 'string' ? o.html.replace(/<[^>]*>/g, '') : ''), ok: o.confirmButtonText, cancelar: o.cancelButtonText, peligro: o.icon === 'warning' || o.icon === 'error' })
                    .then(function (r) { return { isConfirmed: !!r, value: r }; });
            }
            var m = o.text || (typeof o.html === 'string' ? o.html.replace(/<[^>]*>/g, '') : '');
            toast(m || o.title, tipoSwal(o.icon), { titulo: m ? o.title : undefined, duracion: o.timer });
            return Promise.resolve({ isConfirmed: true });
        };
        window.Swal = { fire: fire, mixin: function (base) { return { fire: function (o) { return fire(Object.assign({}, base, o)); } }; }, stopTimer: function () {}, resumeTimer: function () {} };
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
            if (conCampo) { campo = el('input'); campo.placeholder = o.placeholder || ''; campo.value = o.valor || ''; if (o.inputmode) campo.inputMode = o.inputmode; md.appendChild(campo); }
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

    // ---------- Listas desplegables con buscador ----------
    // Se aplica a <select class="form-select">, <select class="in"> y <select data-buscar>.
    // <select data-nativo> se deja como está.
    var cssSel = '' +
        '.lps{position:relative;display:block;min-width:0}' +
        '.lps>select.lps-o{position:absolute!important;left:0;right:0;bottom:0;width:100%!important;height:1px!important;opacity:0!important;pointer-events:none!important;padding:0!important;border:0!important}' +
        '.lps-b{display:flex!important;align-items:center;gap:.5rem;width:100%;text-align:left;cursor:pointer;background-image:none!important;-webkit-appearance:none;appearance:none;min-height:calc(1.5em + 1.2rem + 3px)}' +
        '.lps-b.form-select-sm{min-height:0}' +
        '.lps-b .lps-t{flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}' +
        '.lps-b.lps-ph .lps-t{color:#8a958f;font-weight:500}' +
        '.lps-b .lps-c{flex:none;width:22px;height:22px;border-radius:8px;display:grid;place-items:center;background:#f1f5f2;color:#33403a;transition:transform .15s,background .15s}' +
        '.lps-b[aria-expanded=true] .lps-c{transform:rotate(180deg);background:#dcfce7;color:#14753a}' +
        '.lps-b:disabled{opacity:.6;cursor:not-allowed;background-color:#f3f6f4}' +
        '.lps-b.lps-inv{border-color:#d61f69!important;box-shadow:0 0 0 4px rgba(214,31,105,.12)!important}' +
        '.lps-ov{position:fixed;inset:0;z-index:2050}' +
        '.lps-p{position:fixed;background:#fff;border:1px solid #e3ebe6;border-radius:18px;box-shadow:0 24px 60px -20px rgba(11,13,12,.45);display:flex;flex-direction:column;overflow:hidden;opacity:0;transform:translateY(-6px);transition:opacity .14s,transform .14s;font-family:inherit;color:#151a17}' +
        '.lps-ov.on .lps-p{opacity:1;transform:none}' +
        '.lps-ov.arriba .lps-p{transform:translateY(6px)}.lps-ov.arriba.on .lps-p{transform:none}' +
        '.lps-h{display:none}' +
        '.lps-s{padding:.6rem .6rem .3rem;position:relative}' +
        '.lps-s svg{position:absolute;left:1.35rem;top:50%;margin-top:.15rem;transform:translateY(-50%);color:#7a8580;pointer-events:none}' +
        '.lps-s input{width:100%;border:1.5px solid #e3ebe6;border-radius:12px;padding:.6rem .8rem .6rem 2.2rem;font:inherit;font-size:.95rem;font-weight:600;outline:none;background:#f7faf8}' +
        '.lps-s input:focus{border-color:#2fd15a;background:#fff;box-shadow:0 0 0 4px rgba(47,209,90,.15)}' +
        '.lps-l{overflow-y:auto;padding:.35rem;overscroll-behavior:contain;flex:1;min-height:0}' +
        '.lps-g{font-size:.68rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:#7a8580;padding:.7rem .7rem .3rem}' +
        '.lps-i{display:flex;align-items:center;gap:.5rem;padding:.6rem .7rem;border-radius:11px;cursor:pointer;font-weight:600;font-size:.95rem;line-height:1.25}' +
        '.lps-i span{flex:1;min-width:0}' +
        '.lps-i .lps-sub{color:#7a8580;font-weight:600;font-size:.78rem}' +
        '.lps-i mark{background:#dcfce7;color:inherit;padding:0;border-radius:3px}' +
        '.lps-i.act{background:#f1f5f2}' +
        '.lps-i.sel{background:#e8f8ec;color:#0f6b2e}' +
        '.lps-i.sel:after{content:"";flex:none;width:18px;height:18px;border-radius:50%;background:#16b34a url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 16 16%27%3E%3Cpath fill=%27none%27 stroke=%27%23fff%27 stroke-width=%272.4%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27 d=%27M4 8.5l2.6 2.5L12 5.5%27/%3E%3C/svg%3E") center/14px no-repeat}' +
        '.lps-i.vac{color:#8a958f;font-weight:500}' +
        '.lps-i.dis{opacity:.45;cursor:not-allowed}' +
        '.lps-v{padding:1.2rem;text-align:center;color:#7a8580;font-weight:600;font-size:.92rem}' +
        '@media (max-width:575.98px){' +
        '.lps-ov{background:rgba(11,13,12,.5);opacity:0;transition:opacity .18s}.lps-ov.on{opacity:1}' +
        '.lps-p{left:0!important;right:0!important;bottom:0!important;top:auto!important;width:auto!important;max-height:78vh!important;max-height:78dvh!important;border-radius:24px 24px 0 0;transform:translateY(30px)!important;padding-bottom:env(safe-area-inset-bottom)}' +
        '.lps-ov.on .lps-p{transform:none!important}' +
        '.lps-h{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1rem .2rem;font-weight:800;font-size:1.05rem}' +
        '.lps-h:before{content:"";position:absolute;left:50%;top:7px;width:42px;height:4px;border-radius:9px;background:#dfe6e2;transform:translateX(-50%)}' +
        '.lps-h button{border:0;background:#f1f5f2;border-radius:10px;width:34px;height:34px;font-size:1.1rem;font-weight:800;color:#33403a}' +
        '.lps-i{padding:.8rem .8rem;font-size:1rem}}';
    var stS = document.createElement('style'); stS.textContent = cssSel; document.head.appendChild(stS);

    var pSel = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
    var pIdx = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'selectedIndex');
    var CHEV = '<svg width="12" height="12" viewBox="0 0 16 16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M4 6l4 4 4-4"/></svg>';
    var LUPA = '<svg width="15" height="15" viewBox="0 0 16 16" aria-hidden="true"><circle cx="7" cy="7" r="5" fill="none" stroke="currentColor" stroke-width="2"/><path d="M11 11l3.5 3.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>';
    function plano(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase(); }
    function escHtml(s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
    function resaltar(txt, q) {
        if (!q) return escHtml(txt);
        var i = plano(txt).indexOf(q);
        if (i < 0) return escHtml(txt);
        return escHtml(txt.slice(0, i)) + '<mark>' + escHtml(txt.slice(i, i + q.length)) + '</mark>' + escHtml(txt.slice(i + q.length));
    }
    function etiquetaDe(sel) {
        var l = sel.id && document.querySelector('label[for="' + sel.id + '__lp"],label[for="' + sel.id + '"]');
        if (!l) { var c = sel.closest('.lps') && sel.closest('.lps').parentNode; l = c && c.querySelector('label'); }
        return l ? l.textContent.replace(/\s+/g, ' ').trim() : 'Elija una opción';
    }

    function prepararSelect(sel) {
        if (sel.__lp || sel.multiple || sel.size > 1 || sel.hasAttribute('data-nativo') || sel.classList.contains('selectpicker')) return;
        if (!(sel.classList.contains('form-select') || sel.classList.contains('in') || sel.hasAttribute('data-buscar'))) return;
        if (sel.closest('.lp-vista,.bootstrap-select,[data-nativo]')) return;
        sel.__lp = true;
        var w = el('div', 'lps'), b = el('button'), tx = el('span', 'lps-t'), ch = el('span', 'lps-c');
        ch.innerHTML = CHEV;
        b.type = 'button'; b.setAttribute('aria-haspopup', 'listbox'); b.setAttribute('aria-expanded', 'false');
        b.appendChild(tx); b.appendChild(ch);
        sel.parentNode.insertBefore(w, sel); w.appendChild(sel); w.appendChild(b);
        var estilo = sel.getAttribute('style') || '';
        if (estilo) { b.setAttribute('style', estilo); b.style.display = ''; b.style.maxWidth = ''; w.style.maxWidth = sel.style.maxWidth; }
        if (sel.id) {
            b.id = sel.id + '__lp';
            document.querySelectorAll('label[for="' + sel.id + '"]').forEach(function (l) { l.htmlFor = b.id; });
        }
        sel.tabIndex = -1; sel.setAttribute('aria-hidden', 'true');
        sel.__lpW = w;

        function pintar() {
            var o = sel.options[pIdx.get.call(sel)];
            var cls = (sel.className || '').replace(/\blps-o\b/g, '').trim();
            b.className = cls + ' lps-b' + (!o || o.value === '' ? ' lps-ph' : '') + (b.classList.contains('lps-inv') && !sel.checkValidity() ? ' lps-inv' : '');
            tx.textContent = o ? o.text : 'Seleccione…';
            b.disabled = sel.disabled;
            w.style.display = (sel.style.display === 'none' || sel.hidden) ? 'none' : '';
            if (!sel.classList.contains('lps-o')) sel.classList.add('lps-o');
        }
        sel.__lpPintar = pintar;
        pintar();
        sel.addEventListener('change', function () { b.classList.remove('lps-inv'); pintar(); });
        new MutationObserver(pintar).observe(sel, { childList: true, subtree: true, characterData: true, attributes: true, attributeFilter: ['disabled', 'style', 'hidden', 'class', 'selected'] });
        Object.defineProperty(sel, 'value', { configurable: true, get: function () { return pSel.get.call(sel); }, set: function (v) { pSel.set.call(sel, v); pintar(); } });
        Object.defineProperty(sel, 'selectedIndex', { configurable: true, get: function () { return pIdx.get.call(sel); }, set: function (v) { pIdx.set.call(sel, v); pintar(); } });
        sel.addEventListener('invalid', function () { b.classList.add('lps-inv'); });
        if (sel.form) sel.form.addEventListener('reset', function () { setTimeout(pintar, 0); });
        b.addEventListener('click', function () { abrirSel(sel, b); });
        b.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === ' ') { e.preventDefault(); abrirSel(sel, b); }
            else if (e.key.length === 1 && /\S/.test(e.key) && !e.ctrlKey && !e.metaKey && !e.altKey) { e.preventDefault(); abrirSel(sel, b, e.key); }
        });
    }

    var selAbierto = null;
    function cerrarSel(devolverFoco) {
        if (!selAbierto) return;
        var a = selAbierto; selAbierto = null;
        a.b.setAttribute('aria-expanded', 'false');
        document.removeEventListener('keydown', a.tecla, true);
        window.removeEventListener('resize', a.ubicar);
        window.removeEventListener('scroll', a.alScroll, true);
        if (window.visualViewport && a.teclado) { window.visualViewport.removeEventListener('resize', a.teclado); window.visualViewport.removeEventListener('scroll', a.teclado); }
        a.ov.classList.remove('on');
        setTimeout(function () { a.ov.remove(); }, 180);
        if (devolverFoco) a.b.focus({ preventScroll: true });
    }
    function abrirSel(sel, b, letra) {
        if (sel.disabled) return;
        cerrarSel(false);
        var movil = window.matchMedia('(max-width:575.98px)').matches;
        var ov = el('div', 'lps-ov'), p = el('div', 'lps-p');
        p.setAttribute('role', 'dialog');
        var cab = el('div', 'lps-h'), cerrar = el('button', null, '×');
        cerrar.type = 'button'; cerrar.setAttribute('aria-label', 'Cerrar');
        cab.appendChild(el('span', null, etiquetaDe(sel))); cab.appendChild(cerrar); p.appendChild(cab);

        var opts = [].slice.call(sel.options);
        var conBuscar = opts.length > 7 || !!letra || sel.hasAttribute('data-buscar');
        var maxVer = +sel.getAttribute('data-max') || 0;
        var q = null;
        if (conBuscar) {
            var sb = el('div', 'lps-s'); sb.innerHTML = LUPA;
            q = el('input'); q.type = 'search'; q.placeholder = 'Buscar…'; q.autocomplete = 'off'; q.spellcheck = false;
            q.setAttribute('aria-label', 'Buscar');
            sb.appendChild(q); p.appendChild(sb);
        }
        var lista = el('div', 'lps-l'); lista.setAttribute('role', 'listbox'); p.appendChild(lista);
        var vacio = el('div', 'lps-v', 'No hay resultados'); vacio.style.display = 'none'; p.appendChild(vacio);

        var items = [], cabeza = null, grupoAnt = null;
        opts.forEach(function (o, i) {
            if (o.hidden) return;
            if (o.value === '' && sel.required && !o.selected) return;
            var g = o.parentNode && o.parentNode.tagName === 'OPTGROUP' ? o.parentNode : null;
            if (g !== grupoAnt) { cabeza = g ? el('div', 'lps-g', g.label) : null; if (cabeza) lista.appendChild(cabeza); grupoAnt = g; }
            var it = el('div', 'lps-i' + (o.selected ? ' sel' : '') + (o.disabled ? ' dis' : '') + (o.value === '' ? ' vac' : ''));
            it.setAttribute('role', 'option');
            var sp = el('span'); sp.textContent = o.text; it.appendChild(sp);
            var sub = o.getAttribute('data-sub') || '';
            if (sub) { var sm = el('small', 'lps-sub', sub); sp.appendChild(document.createElement('br')); it.__sub = sm; }
            it.__i = i; it.__t = o.text; it.__k = plano(o.text + ' ' + sub + ' ' + (g ? g.label : '') + ' ' + o.value); it.__cab = cabeza; it.__ok = !o.disabled; it.__s = sub;
            lista.appendChild(it); items.push(it);
        });

        var act = -1;
        function visibles() { return [].filter.call(lista.querySelectorAll('.lps-i'), function (it) { return it.style.display !== 'none' && it.__ok; }); }
        function marcar(n, mover) {
            var v = visibles(); if (!v.length) { act = -1; return; }
            act = Math.max(0, Math.min(n, v.length - 1));
            items.forEach(function (it) { it.classList.remove('act'); });
            v[act].classList.add('act');
            if (mover) v[act].scrollIntoView({ block: 'nearest' });
        }
        var mas = el('div', 'lps-v'); mas.style.display = 'none'; mas.style.padding = '.6rem'; p.appendChild(mas);
        // Al buscar, primero lo que empieza con lo escrito (ej. "Na" → Nariño), luego inicio de palabra y luego el resto
        var sinGrupos = !lista.querySelector('.lps-g');
        function puntaje(it, t) {
            var k = it.__k, i = k.indexOf(t);
            if (i === 0) return 0;
            if (i > 0 && /[\s(\-.,/]/.test(k.charAt(i - 1))) return 1;
            return 2;
        }
        function ordenar(t) {
            if (!sinGrupos) return;
            var orden = items.slice();
            if (t) orden.sort(function (a, b) { return (puntaje(a, t) - puntaje(b, t)) || (a.__i - b.__i); });
            orden.forEach(function (it) { lista.appendChild(it); });
        }
        function filtrar() {
            var t = q ? plano(q.value.trim()) : '', n = 0, cabs = [], tot = 0;
            ordenar(t);
            var lista2 = sinGrupos ? [].slice.call(lista.children) : items;
            lista2.forEach(function (it) {
                var ok = !t || it.__k.indexOf(t) !== -1;
                if (ok) tot++;
                // Con data-max solo se muestran unos pocos (la opción elegida siempre) y se busca para ver más
                if (ok && maxVer && n >= maxVer && !it.classList.contains('sel')) ok = false;
                it.style.display = ok ? '' : 'none';
                it.firstChild.innerHTML = resaltar(it.__t, t) + (it.__s ? '<br><small class="lps-sub">' + resaltar(it.__s, t) + '</small>' : '');
                if (ok) { n++; if (it.__cab) cabs.push(it.__cab); }
            });
            lista.querySelectorAll('.lps-g').forEach(function (h) { h.style.display = cabs.indexOf(h) !== -1 ? '' : 'none'; });
            vacio.style.display = n ? 'none' : '';
            mas.style.display = tot > n ? '' : 'none';
            mas.textContent = 'Mostrando ' + n + ' de ' + tot + ' · escriba nombre, código o correo para buscar';
            marcar(t ? 0 : visibles().findIndex(function (it) { return it.classList.contains('sel'); }), true);
        }
        function elegir(it) {
            if (!it || !it.__ok) return;
            var antes = pIdx.get.call(sel);
            pIdx.set.call(sel, it.__i);
            if (sel.__lpPintar) sel.__lpPintar();
            cerrarSel(true);
            if (antes !== it.__i) {
                sel.dispatchEvent(new Event('input', { bubbles: true }));
                sel.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
        lista.addEventListener('click', function (e) { var it = e.target.closest('.lps-i'); if (it) elegir(it); });
        lista.addEventListener('mousemove', function (e) { var it = e.target.closest('.lps-i'); if (it && it.__ok) { var v = visibles(); var n = v.indexOf(it); if (n !== act) marcar(n, false); } });
        if (q) q.addEventListener('input', filtrar);
        cerrar.addEventListener('click', function () { cerrarSel(true); });
        ov.addEventListener('mousedown', function (e) { if (e.target === ov) { e.preventDefault(); cerrarSel(true); } });
        ov.addEventListener('touchstart', function (e) { if (e.target === ov) { e.preventDefault(); cerrarSel(false); } }, { passive: false });

        // En el celular el teclado tapa la parte de abajo: la hoja se sube justo encima del teclado
        function ajustarTeclado() {
            var vv = window.visualViewport;
            if (!movil || !vv) return;
            var tapado = Math.max(0, window.innerHeight - (vv.height + vv.offsetTop));
            p.style.setProperty('bottom', tapado + 'px', 'important');
            p.style.setProperty('max-height', Math.round(vv.height * 0.85) + 'px', 'important');
        }
        function ubicar() {
            if (movil) { ajustarTeclado(); return; }
            var r = b.getBoundingClientRect(), vh = window.innerHeight, vw = window.innerWidth;
            var ancho = Math.min(Math.max(r.width, 240), vw - 16);
            var izq = Math.min(Math.max(8, r.left), vw - ancho - 8);
            var abajo = vh - r.bottom - 12, arriba = r.top - 12;
            var haciaArriba = abajo < 260 && arriba > abajo;
            p.style.left = izq + 'px'; p.style.width = ancho + 'px';
            if (haciaArriba) { p.style.top = 'auto'; p.style.bottom = (vh - r.top + 6) + 'px'; p.style.maxHeight = Math.min(arriba, 380) + 'px'; }
            else { p.style.bottom = 'auto'; p.style.top = (r.bottom + 6) + 'px'; p.style.maxHeight = Math.min(abajo, 380) + 'px'; }
            ov.classList.toggle('arriba', haciaArriba);
        }
        function alScroll(e) { if (!p.contains(e.target)) { if (b.getBoundingClientRect().bottom < 0 || b.getBoundingClientRect().top > window.innerHeight) cerrarSel(false); else ubicar(); } }
        function tecla(e) {
            if (e.key === 'Escape') { e.preventDefault(); e.stopPropagation(); cerrarSel(true); }
            else if (e.key === 'ArrowDown') { e.preventDefault(); marcar(act + 1, true); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); marcar(act - 1, true); }
            else if (e.key === 'Enter') { e.preventDefault(); e.stopPropagation(); elegir(visibles()[act]); }
            else if (e.key === 'Tab') { cerrarSel(false); }
        }

        ov.appendChild(p); document.body.appendChild(ov);
        selAbierto = { ov: ov, b: b, tecla: tecla, ubicar: ubicar, alScroll: alScroll, teclado: ajustarTeclado };
        b.setAttribute('aria-expanded', 'true');
        document.addEventListener('keydown', tecla, true);
        window.addEventListener('resize', ubicar);
        window.addEventListener('scroll', alScroll, true);
        if (window.visualViewport) { window.visualViewport.addEventListener('resize', ajustarTeclado); window.visualViewport.addEventListener('scroll', ajustarTeclado); }
        ubicar();
        if (q && letra) q.value = letra;
        filtrar();
        requestAnimationFrame(function () {
            ov.classList.add('on');
            if (q && (!movil || letra)) { q.focus({ preventScroll: true }); try { q.setSelectionRange(q.value.length, q.value.length); } catch (x) {} }
            var s = lista.querySelector('.lps-i.sel'); if (s && !letra) s.scrollIntoView({ block: 'center' });
        });
    }
    function revisarSelects(raiz) { (raiz || document).querySelectorAll('select').forEach(prepararSelect); }

    // ---------- Borradores: si se sale de la pantalla (o el celular cierra la app) no se pierde lo escrito ----------
    function claveBorrador(f) { return 'lp_borrador:' + location.pathname + ':' + (f.getAttribute('action') || '') + ':' + [].indexOf.call(document.forms, f); }
    function camposDe(f) {
        return [].filter.call(f.elements, function (e) {
            if (!e.name || e.disabled || e.name === '_token' || e.name === '_method') return false;
            var t = (e.type || '').toLowerCase();
            if (t === 'hidden') return e.hasAttribute('data-dinero');
            return ['password', 'file', 'submit', 'button', 'reset'].indexOf(t) === -1;
        });
    }
    function prepararBorrador(f) {
        if (f.__lpB) return; f.__lpB = true;
        var m = (f.getAttribute('method') || 'get').toLowerCase();
        if (m !== 'post' || f.hasAttribute('data-sin-borrador') || f.querySelector('input[type=password]') || camposDe(f).length < 3) return;
        var k = claveBorrador(f);
        var leer = function () { try { return JSON.parse(localStorage.getItem(k) || 'null'); } catch (x) { return null; } };
        var b = leer();
        if (b && Date.now() - b.t < 3 * 24 * 3600 * 1000) {
            var cambiados = 0;
            camposDe(f).forEach(function (e) {
                if (!(e.name in b.v)) return;
                var val = b.v[e.name];
                if (e.type === 'checkbox' || e.type === 'radio') {
                    var on = Array.isArray(val) ? val.indexOf(e.value) !== -1 : val === e.value;
                    if (e.checked !== on) { e.checked = on; cambiados++; }
                } else if (e.tagName === 'SELECT' && e.multiple) {
                    [].forEach.call(e.options, function (o) { o.selected = (val || []).indexOf(o.value) !== -1; });
                } else if (e.value !== val) { e.value = val; cambiados++; e.dispatchEvent(new Event('input', { bubbles: true })); }
            });
            if (cambiados) {
                setTimeout(function () { toast('Recuperamos lo que estaba escribiendo ✍️', 'info'); }, 400);
            }
        }
        var tm;
        var guardar = function () {
            clearTimeout(tm);
            tm = setTimeout(function () {
                var v = {};
                camposDe(f).forEach(function (e) {
                    if (e.type === 'checkbox') { if (!Array.isArray(v[e.name])) v[e.name] = []; if (e.checked) v[e.name].push(e.value); }
                    else if (e.type === 'radio') { if (e.checked) v[e.name] = e.value; }
                    else if (e.tagName === 'SELECT' && e.multiple) v[e.name] = [].filter.call(e.options, function (o) { return o.selected; }).map(function (o) { return o.value; });
                    else v[e.name] = e.value;
                });
                try { localStorage.setItem(k, JSON.stringify({ t: Date.now(), v: v })); } catch (x) {}
            }, 300);
        };
        f.addEventListener('input', guardar);
        f.addEventListener('change', guardar);
        f.addEventListener('submit', function () { clearTimeout(tm); try { localStorage.removeItem(k); } catch (x) {} });
    }
    // Si el servidor respondió con errores de validación, el formulario ya viene con lo escrito: se conserva el borrador
    function revisarBorradores() { [].forEach.call(document.forms, prepararBorrador); }

    function revisar(raiz) { (raiz || document).querySelectorAll('input[data-dinero]').forEach(prepararDinero); revisarSelects(raiz); }
    function iniciar() {
        revisar();
        try { revisarBorradores(); } catch (x) {}
        new MutationObserver(function (ms) {
            ms.forEach(function (m) { m.addedNodes.forEach(function (n) { if (n.nodeType === 1) { if (n.matches('input[data-dinero]')) prepararDinero(n); else if (n.matches('select')) prepararSelect(n); else revisar(n); } }); });
        }).observe(document.body, { childList: true, subtree: true });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();

    window.LP = {
        toast: toast,
        confirmar: function (o) { return ventana(typeof o === 'string' ? { titulo: o } : (o || {}), false); },
        pedir: function (o) { return ventana(typeof o === 'string' ? { titulo: o } : (o || {}), true); },
        formatoDinero: function (n) { return bonito(normal(n)); },
        select: prepararSelect,
        refrescarSelect: function (s) { if (s && s.__lpPintar) s.__lpPintar(); }
    };
})();
