// Tablas en español para todos los módulos (Simple-DataTables)
window.LP_TABLA_ES = {
    labels: {
        placeholder: 'Buscar...',
        searchLabel: 'Buscar',
        searchTitle: 'Buscar en la tabla',
        pageTitle: 'Página {page}',
        perPage: 'registros por página',
        noRows: 'No hay registros',
        info: 'Mostrando {start} a {end} de {rows} registros',
        noResults: 'No se encontraron resultados'
    },
    perPageSelect: [10, 25, 50, 100]
};

// Buscador grande arriba de la tabla, siempre visible (en el celular la casilla de la librería quedaba escondida a la derecha)
window.LP_BUSCADOR_TABLA = function (tabla, dt) {
    if (!tabla || !dt || tabla.dataset.buscador) return;
    tabla.dataset.buscador = '1';
    if (!document.getElementById('lpTablaBuscarCss')) {
        var css = document.createElement('style');
        css.id = 'lpTablaBuscarCss';
        css.textContent =
            '.lp-tb{position:relative;margin:0 0 .8rem}' +
            '.lp-tb input{width:100%;border:1.5px solid #dfe6e2;border-radius:16px;padding:.8rem 2.6rem .8rem 2.7rem;font-size:16px;font-weight:600;background:#fff;outline:none}' +
            '.lp-tb input:focus{border-color:#2fd15a;box-shadow:0 0 0 3px rgba(47,209,90,.18)}' +
            '.lp-tb .lupa{position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#7a8580;pointer-events:none}' +
            '.lp-tb .borrar{position:absolute;right:.6rem;top:50%;transform:translateY(-50%);border:0;background:#eef1ef;border-radius:50%;width:28px;height:28px;display:none;align-items:center;justify-content:center;color:#55605a;cursor:pointer;font-weight:800}' +
            '.lp-tb.con .borrar{display:flex}' +
            '.lp-tb small{display:block;color:#6b7570;font-weight:600;margin-top:.3rem;padding-left:.3rem}' +
            '.datatable-wrapper .datatable-search{display:none}';
        document.head.appendChild(css);
    }
    var caja = document.createElement('div');
    caja.className = 'lp-tb';
    caja.innerHTML = '<span class="lupa"><i class="fa-solid fa-magnifying-glass"></i></span>' +
        '<input type="search" placeholder="Buscar por nombre, celular, cédula o lo que sea…" autocomplete="off" aria-label="Buscar en la tabla">' +
        '<button type="button" class="borrar" aria-label="Borrar búsqueda">✕</button>';
    var envoltura = tabla.closest('.datatable-wrapper') || tabla;
    envoltura.parentNode.insertBefore(caja, envoltura);
    var input = caja.querySelector('input'), t = null;
    function buscar() {
        var v = input.value.trim();
        caja.classList.toggle('con', !!v);
        dt.search(v);
    }
    input.addEventListener('input', function () { clearTimeout(t); t = setTimeout(buscar, 150); });
    caja.querySelector('.borrar').addEventListener('click', function () { input.value = ''; buscar(); input.focus(); });
};

window.addEventListener('DOMContentLoaded', function () {
    var tabla = document.getElementById('datatablesSimple');
    if (tabla && window.simpleDatatables) {
        var dt = new simpleDatatables.DataTable(tabla, window.LP_TABLA_ES);
        window.LP_BUSCADOR_TABLA(tabla, dt);
    }
});
