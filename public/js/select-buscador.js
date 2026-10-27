/*
 * Select con buscador (estándar de LocalProo).
 * Uso: <select data-buscador name="..."> con <option data-icono="fa-store" data-desc="texto">Nombre</option>
 * Cambia el select por un botón que abre una lista con buscador (sin tildes ni mayúsculas).
 * El select original sigue en el formulario (se envía y avisa "change"), así que el resto del código no cambia.
 */
(function () {
    if (window.SelectBuscador) return;
    var css = '' +
        '.sb-wrap{position:relative}' +
        '.sb-wrap>select{position:absolute;inset:0;opacity:0;pointer-events:none}' +
        '.sb-btn{width:100%;display:flex;align-items:center;gap:.6rem;text-align:left;border:2px solid #dfe9e2;border-radius:14px;background:#fff;padding:.7rem .9rem;font:inherit;color:#1d2422;cursor:pointer;min-height:52px}' +
        '.sb-btn:focus{outline:none;border-color:#22a050;box-shadow:0 0 0 4px rgba(47,209,90,.15)}' +
        '.sb-btn .sb-ic{width:34px;height:34px;flex:0 0 34px;border-radius:10px;background:#eaf7ee;color:#11702f;display:grid;place-items:center}' +
        '.sb-btn .sb-tx{flex:1;min-width:0}.sb-btn .sb-tx b{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}' +
        '.sb-btn .sb-tx small{display:block;color:#6b7570;font-size:.78rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}' +
        '.sb-btn .sb-ph{color:#8a958f}.sb-btn .sb-flecha{color:#6b7570;transition:transform .15s}.sb-wrap.abierto .sb-flecha{transform:rotate(180deg)}' +
        '.sb-panel{position:absolute;left:0;right:0;top:calc(100% + 6px);z-index:1060;background:#fff;border:1.5px solid #dfe9e2;border-radius:16px;box-shadow:0 18px 40px -12px rgba(20,40,30,.28);padding:.5rem;display:none}' +
        '.sb-wrap.abierto .sb-panel{display:block}' +
        '.sb-buscar{width:100%;border:1.5px solid #dfe9e2;border-radius:12px;padding:.6rem .8rem;font:inherit;outline:none;margin-bottom:.4rem}' +
        '.sb-buscar:focus{border-color:#22a050}' +
        '.sb-lista{max-height:320px;overflow:auto;margin:0;padding:0;list-style:none}' +
        '.sb-op{display:flex;gap:.6rem;align-items:center;padding:.55rem .6rem;border-radius:10px;cursor:pointer}' +
        '.sb-op .sb-ic{width:30px;height:30px;flex:0 0 30px;border-radius:9px;background:#f1f6f2;color:#11702f;display:grid;place-items:center;font-size:.9rem}' +
        '.sb-op b{display:block;font-size:.93rem}.sb-op small{display:block;color:#6b7570;font-size:.76rem;line-height:1.25}' +
        '.sb-op.activo{background:#eef8f1}.sb-op.sel b{color:#11702f}.sb-op.sel::after{content:"\\2713";margin-left:auto;color:#22a050;font-weight:800}' +
        '.sb-nada{padding:.8rem;color:#6b7570;text-align:center;font-size:.9rem}';
    var st = document.createElement('style'); st.textContent = css; document.head.appendChild(st);

    function norm(t) { return String(t || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); }
    function esc(t) { var d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; }
    function icono(c) { return c ? '<span class="sb-ic"><i class="fa-solid ' + esc(c) + '"></i></span>' : ''; }

    function crear(sel) {
        if (sel.dataset.sbListo) return; sel.dataset.sbListo = '1';
        var wrap = document.createElement('div'); wrap.className = 'sb-wrap';
        sel.parentNode.insertBefore(wrap, sel); wrap.appendChild(sel);
        sel.tabIndex = -1;
        var btn = document.createElement('button'); btn.type = 'button'; btn.className = 'sb-btn';
        var panel = document.createElement('div'); panel.className = 'sb-panel';
        panel.innerHTML = '<input type="text" class="sb-buscar" placeholder="' + esc(sel.dataset.buscadorTexto || 'Escriba para buscar…') + '" autocomplete="off"><ul class="sb-lista" role="listbox"></ul>';
        wrap.appendChild(btn); wrap.appendChild(panel);
        var buscar = panel.querySelector('.sb-buscar'), lista = panel.querySelector('.sb-lista'), activo = -1, visibles = [];
        var ops = Array.prototype.slice.call(sel.options).filter(function (o) { return o.value !== ''; }).map(function (o) {
            return { v: o.value, t: o.textContent.trim(), d: o.dataset.desc || '', i: o.dataset.icono || '', n: norm(o.textContent + ' ' + (o.dataset.desc || '') + ' ' + (o.dataset.claves || '')) };
        });
        function pintarBtn() {
            var o = ops.filter(function (x) { return x.v === sel.value; })[0];
            btn.innerHTML = (o ? icono(o.i) + '<span class="sb-tx"><b>' + esc(o.t) + '</b>' + (o.d ? '<small>' + esc(o.d) + '</small>' : '') + '</span>'
                : '<span class="sb-tx sb-ph">' + esc(sel.dataset.buscadorVacio || 'Elija una opción') + '</span>') + '<i class="fa-solid fa-chevron-down sb-flecha"></i>';
        }
        function pintar() {
            var q = norm(buscar.value).trim();
            visibles = ops.filter(function (o) { return !q || q.split(/\s+/).every(function (p) { return o.n.indexOf(p) !== -1; }); });
            lista.innerHTML = visibles.length ? visibles.map(function (o, k) {
                return '<li class="sb-op' + (o.v === sel.value ? ' sel' : '') + (k === activo ? ' activo' : '') + '" role="option" data-k="' + k + '">' + icono(o.i) + '<span><b>' + esc(o.t) + '</b>' + (o.d ? '<small>' + esc(o.d) + '</small>' : '') + '</span></li>';
            }).join('') : '<li class="sb-nada">No encontramos "' + esc(buscar.value) + '". Pruebe con otra palabra.</li>';
        }
        function abrir() { wrap.classList.add('abierto'); buscar.value = ''; activo = -1; pintar(); setTimeout(function () { buscar.focus(); }, 0); }
        function cerrar() { wrap.classList.remove('abierto'); }
        function elegir(o) {
            if (!o) return;
            sel.value = o.v; pintarBtn(); cerrar(); btn.focus();
            sel.dispatchEvent(new Event('change', { bubbles: true }));
        }
        btn.addEventListener('click', function () { wrap.classList.contains('abierto') ? cerrar() : abrir(); });
        buscar.addEventListener('input', function () { activo = 0; pintar(); });
        lista.addEventListener('mousedown', function (e) { var li = e.target.closest('.sb-op'); if (li) { e.preventDefault(); elegir(visibles[+li.dataset.k]); } });
        buscar.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') { e.preventDefault(); activo = Math.min(visibles.length - 1, activo + 1); pintar(); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); activo = Math.max(0, activo - 1); pintar(); }
            else if (e.key === 'Enter') { e.preventDefault(); elegir(visibles[activo < 0 ? 0 : activo]); }
            else if (e.key === 'Escape') { cerrar(); btn.focus(); }
            var a = lista.querySelector('.activo'); if (a && a.scrollIntoView) a.scrollIntoView({ block: 'nearest' });
        });
        document.addEventListener('click', function (e) { if (!wrap.contains(e.target)) cerrar(); });
        sel.addEventListener('change', pintarBtn);
        // Si el formulario pide elegir y no eligió, se abre la lista
        sel.addEventListener('invalid', function (e) { e.preventDefault(); btn.style.borderColor = '#d64545'; abrir(); });
        pintarBtn();
    }
    window.SelectBuscador = { iniciar: function (raiz) { (raiz || document).querySelectorAll('select[data-buscador]').forEach(crear); } };
    // Los que ya están en la página se arman de una vez; los que vengan después, al terminar de cargar
    SelectBuscador.iniciar();
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { SelectBuscador.iniciar(); });
})();
