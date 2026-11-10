{{-- Una fila de "Otros días o lugares" del obituario --}}
<div class="of-acto row g-2 align-items-end mb-2 p-2 rounded-3" style="background:#f5f8f6">
    <div class="col-sm-4"><label class="form-label small fw-bold mb-0">Qué es</label><input class="form-control form-control-sm" name="actos[{{ $i }}][titulo]" maxlength="60" value="{{ $a['titulo'] ?? '' }}" placeholder="Ej: Homenaje" list="of-actos-sug"></div>
    <div class="col-6 col-sm-3"><label class="form-label small fw-bold mb-0">Fecha</label><input type="date" class="form-control form-control-sm" name="actos[{{ $i }}][fecha]" value="{{ $a['fecha'] ?? '' }}"></div>
    <div class="col-6 col-sm-2"><label class="form-label small fw-bold mb-0">Hora</label><input type="time" class="form-control form-control-sm" name="actos[{{ $i }}][hora]" value="{{ isset($a['hora']) ? substr((string) $a['hora'], 0, 5) : '' }}"></div>
    <div class="col-10 col-sm-3"><label class="form-label small fw-bold mb-0">Lugar</label><input class="form-control form-control-sm" name="actos[{{ $i }}][lugar]" maxlength="150" value="{{ $a['lugar'] ?? '' }}" placeholder="Ej: Colegio San José"></div>
    <div class="col-2 col-sm-12 text-sm-end"><button type="button" class="btn btn-sm btn-outline-danger" data-acto-quitar title="Quitar"><i class="fa-solid fa-xmark"></i></button></div>
</div>
