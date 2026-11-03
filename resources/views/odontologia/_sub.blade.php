{{-- Pestañas internas de Indicadores (solo dueño) --}}
<div class="d-flex gap-2 flex-wrap mb-3">
    <a href="{{ route('odonto.indicadores') }}" class="cs-btn sm {{ request()->routeIs('odonto.indicadores') ? 'dark' : '' }}"><i class="fa-solid fa-chart-line"></i>Indicadores y control de pacientes</a>
    <a href="{{ route('odonto.comisiones') }}" class="cs-btn sm {{ request()->routeIs('odonto.comisiones') ? 'dark' : '' }}"><i class="fa-solid fa-hand-holding-dollar"></i>Comisiones por odontólogo</a>
    <a href="{{ route('odonto.insumos') }}" class="cs-btn sm {{ request()->routeIs('odonto.insumos') ? 'dark' : '' }}"><i class="fa-solid fa-boxes-stacked"></i>Insumos por procedimiento</a>
</div>
