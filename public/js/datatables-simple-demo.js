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

window.addEventListener('DOMContentLoaded', function () {
    var tabla = document.getElementById('datatablesSimple');
    if (tabla && window.simpleDatatables) {
        new simpleDatatables.DataTable(tabla, window.LP_TABLA_ES);
    }
});
