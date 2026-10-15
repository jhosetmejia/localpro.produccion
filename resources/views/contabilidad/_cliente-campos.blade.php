<div class="row g-2">
    <div class="col-md-6"><label class="form-label small fw-bold">Nombre o razón social</label><input class="form-control" name="nombre" maxlength="150" required value="{{ old('nombre', $c->nombre) }}"></div>
    <div class="col-md-4"><label class="form-label small fw-bold">NIT o cédula</label><input class="form-control" name="nit" maxlength="20" required value="{{ old('nit', $c->nit) }}"></div>
    <div class="col-md-2"><label class="form-label small fw-bold">DV</label><input class="form-control" name="dv" maxlength="2" value="{{ old('dv', $c->dv) }}"></div>
    <div class="col-md-4"><label class="form-label small fw-bold">Tipo</label>
        <select class="form-select" name="tipo_persona">
            <option value="juridica" @selected(old('tipo_persona', $c->tipo_persona) === 'juridica')>Persona jurídica (empresa)</option>
            <option value="natural" @selected(old('tipo_persona', $c->tipo_persona) === 'natural')>Persona natural</option>
        </select></div>
    <div class="col-md-4"><label class="form-label small fw-bold">IVA</label>
        <select class="form-select" name="periodicidad_iva">
            @foreach (\App\Models\ContableCliente::PERIODICIDAD as $k => $n)<option value="{{ $k }}" @selected(old('periodicidad_iva', $c->periodicidad_iva) === $k)>{{ $n }}</option>@endforeach
        </select></div>
    <div class="col-md-4"><label class="form-label small fw-bold">Ciudad</label><input class="form-control" name="ciudad" maxlength="80" value="{{ old('ciudad', $c->ciudad) }}"></div>
    <div class="col-12 d-flex gap-4 flex-wrap mt-2">
        <input type="hidden" name="agente_retenedor" value="0">
        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="agente_retenedor" value="1" id="ar-{{ $c->id ?: 'n' }}" @checked(old('agente_retenedor', $c->agente_retenedor))><label class="form-check-label" for="ar-{{ $c->id ?: 'n' }}">Es agente de retención</label></div>
        <input type="hidden" name="autorretenedor" value="0">
        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="autorretenedor" value="1" id="au-{{ $c->id ?: 'n' }}" @checked(old('autorretenedor', $c->autorretenedor))><label class="form-check-label" for="au-{{ $c->id ?: 'n' }}">Practica autorretención</label></div>
        @if ($c->exists)
        <input type="hidden" name="activo" value="0">
        <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="activo" value="1" id="ac-{{ $c->id }}" @checked(old('activo', $c->activo))><label class="form-check-label" for="ac-{{ $c->id }}">Cliente activo</label></div>
        @endif
    </div>
    <div class="col-12"><small class="text-muted">Persona natural: es agente de retención si el año anterior tuvo ingresos brutos o patrimonio de 30.000 UVT o más. Las empresas siempre lo son.</small></div>
    <div class="col-12"><label class="form-label small fw-bold">Notas</label><textarea class="form-control" name="notas" rows="2" maxlength="2000">{{ old('notas', $c->notas) }}</textarea></div>
</div>
