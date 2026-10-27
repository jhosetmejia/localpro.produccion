{{-- Campos de una persona de la red. $a = Asesor o null --}}
<div class="col-md-6"><label class="form-label">Nombre completo</label><input name="name" class="form-control" maxlength="80" required value="{{ old('name', $a?->nombre) }}"></div>
<div class="col-md-6"><label class="form-label">Cargo</label>
    <select name="cargo" class="form-select" required>
        @foreach (\App\Models\Asesor::CARGOS as $k => $c)
        <option value="{{ $k }}" @selected(old('cargo', $a->cargo ?? 'asesor') === $k)>{{ $c[0] }}</option>
        @endforeach
    </select>
</div>
<div class="col-md-6"><label class="form-label">Su líder <span class="text-muted fw-normal">(a quién responde)</span></label>
    <select name="padre_id" class="form-select">
        <option value="">— Ninguno (responde a LocalPro) —</option>
        @foreach ($lideres as $l)
        @continue($a && $l->id === $a->id)
        <option value="{{ $l->id }}" @selected((int) old('padre_id', $a->padre_id ?? 0) === $l->id)>{{ $l->nombre }} · {{ $l->cargo_nombre }}{{ $l->ciudad ? ' · ' . $l->ciudad : ($l->departamento ? ' · ' . $l->departamento : '') }}</option>
        @endforeach
    </select>
    <small class="text-muted">Asesor → líder de zona (máx. 10 asesores) → líder de ciudad → líder de departamento → líder de país.</small>
</div>
<div class="col-md-6"><label class="form-label">Celular / WhatsApp</label><input name="telefono" class="form-control" maxlength="20" inputmode="tel" value="{{ old('telefono', $a?->user?->telefono) }}"></div>
@include('layouts.partials.ubicacion', ['pais' => $a->pais ?? 'CO', 'departamento' => $a->departamento ?? '', 'ciudad' => $a->ciudad ?? '', 'obligatorio' => false])
<div class="col-md-6"><label class="form-label">Zona / comuna <span class="text-muted fw-normal">(líder de zona y asesores)</span></label><input name="zona" class="form-control" maxlength="60" value="{{ old('zona', $a?->zona) }}" placeholder="Ej: Comuna 10"></div>
<div class="col-md-6"><label class="form-label">Cédula</label><input name="documento" class="form-control" maxlength="20" value="{{ old('documento', $a?->documento) }}"></div>
@include('layouts.partials.pago', ['banco' => $a?->banco, 'pais' => $a->pais ?? 'CO', 'titulo' => 'Dónde se le paga'])
<div class="col-md-6"><label class="form-label">Correo (para entrar)</label><input type="email" name="email" class="form-control" maxlength="120" required value="{{ old('email', $a?->user?->email) }}"></div>
<div class="col-md-6"><label class="form-label">{{ $a ? 'Nueva contraseña (opcional)' : 'Contraseña' }}</label><input type="text" name="password" class="form-control" minlength="8" {{ $a ? '' : 'required' }} autocomplete="new-password" placeholder="Mínimo 8 caracteres"></div>
