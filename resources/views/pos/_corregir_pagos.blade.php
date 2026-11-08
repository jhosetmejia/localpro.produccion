            <label class="form-label fw-bold small">¿Cómo pagó realmente?</label>
            @foreach ($venta->pagos as $p)
            <div class="d-flex align-items-center gap-2 mb-2">
                @if ($venta->pagos->count() > 1)<span class="small text-muted text-nowrap">@pesos($p->monto):</span>@endif
                <select name="metodos[{{ $p->id }}]" class="form-select" @disabled(($p->moneda ?? null) === 'VES')>
                    @if (!$metodos->contains('id', $p->metodo_pago_id))<option value="">{{ $p->metodo->nombre ?? 'Pago' }}</option>@endif
                    @foreach ($metodos as $m)<option value="{{ $m->id }}" @selected((int) $m->id === (int) $p->metodo_pago_id)>{{ $m->nombre }}</option>@endforeach
                </select>
            </div>
            @endforeach
            <small class="text-muted d-block mb-3">Si cambia Efectivo por Transferencia, la caja de ese día deja de esperar esa plata en efectivo.</small>
