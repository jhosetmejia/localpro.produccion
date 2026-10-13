{{-- Avisos, confirmaciones y campos de dinero con separador de miles (según el país de la tienda) --}}
<script>window.LP_MONEDA = @json(moneda());</script>
<script src="{{ asset('js/lp-ui.js') }}?v=5"></script>
<script>window.LP_PAIS = @json(\App\Support\TiendaActual::tienda()?->pais ?: 'CO');</script>
<script src="{{ asset('js/lp-campos.js') }}?v=2"></script>
