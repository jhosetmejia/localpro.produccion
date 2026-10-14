@if(isset($alquiler) && $alquiler->exists && $alquiler->cobrado && $alquiler->estado !== 'cancelado')
<form method="POST" action="{{ route('alquileres.corregirPago', $alquiler) }}"
      onsubmit="return confirm('Esto anula la venta y registra solo lo que pagó. ¿Continuar?')"
      style="margin-top:16px;padding:12px;border:1px solid #f59e0b;border-radius:8px">
    @csrf
    <strong>¿Quedó como pagado completo pero no pagó todo?</strong>
    <div style="margin-top:8px">
        <label>Cuánto pagó realmente (0 si nada)</label>
        <input type="number" name="corr_pagado" step="0.01" min="0" required>
    </div>
    <div style="margin-top:8px">
        <label>Cómo pagó</label>
        <select name="corr_metodo">
            @foreach($metodos as $m)
                <option value="{{ $m->id }}">{{ $m->nombre }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" style="margin-top:10px">Corregir pago</button>
</form>
@endif