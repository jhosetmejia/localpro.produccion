<div class="col-md-6"><label class="form-label">Nombre del programa</label><input name="nombre" class="form-control" maxlength="120" required value="{{ $p->nombre ?? '' }}" placeholder="Ej: Auxiliar en Enfermería"></div>
<div class="col-md-6"><label class="form-label">Tipo</label><input name="tipo" class="form-control" maxlength="40" list="tipos-prog" value="{{ $p->tipo ?? 'Técnico laboral por competencias' }}">
    <datalist id="tipos-prog"><option>Técnico laboral por competencias</option><option>Curso corto</option><option>Diplomado</option><option>Seminario</option><option>Validación de bachillerato</option></datalist></div>
<div class="col-4 col-md-2"><label class="form-label">Semestres</label><input type="number" name="semestres" class="form-control" min="1" max="20" value="{{ $p->semestres ?? 2 }}"></div>
<div class="col-8 col-md-3"><label class="form-label">Precio pleno (semestre)</label><input data-dinero type="number" name="valor_semestre" class="form-control fp-pleno" min="0" value="{{ isset($p) ? (int) $p->valor_semestre : 0 }}"></div>
<div class="col-md-3"><label class="form-label">Valor inscripción</label><input data-dinero type="number" name="valor_inscripcion" class="form-control" min="0" value="{{ isset($p) ? (int) $p->valor_inscripcion : 0 }}"></div>
<div class="col-12"><label class="form-label">Módulos <span class="text-muted fw-normal">(opcional, uno por línea)</span></label><textarea name="modulos" class="form-control" rows="3" maxlength="5000" placeholder="Ej: Fundamentos de enfermería&#10;Primeros auxilios">{{ $p->modulos ?? '' }}</textarea></div>

@php $uid = 'fp' . ($p->id ?? 'n'); @endphp
<div class="col-12 fp-fin" id="{{ $uid }}">
    <div class="p-3 rounded-4" style="background:#f4fcf6;border:1.5px solid #bfeccb">
        <div class="fw-bold mb-1"><i class="fa-solid fa-hand-holding-dollar me-1" style="color:#16b34a"></i>Precio de contado y financiación <span class="text-muted fw-normal small">(opcional)</span></div>
        <div class="small text-muted mb-2">Como en su lista de precios: precio pleno, precio de contado con descuento, o financiado con una cuota inicial y cuotas mensuales. Al matricular se llenan solos.</div>
        <div class="row g-2">
            <div class="col-6 col-md-3"><label class="form-label small">Precio de contado</label><input data-dinero type="number" name="precio_contado" class="form-control fp-contado" min="0" value="{{ isset($p) && $p->precio_contado ? (int) $p->precio_contado : '' }}" placeholder="Ej: 1050000"></div>
            <div class="col-6 col-md-3"><label class="form-label small">Precio financiado</label><input data-dinero type="number" name="precio_financiado" class="form-control fp-finan" min="0" value="{{ isset($p) && $p->precio_financiado ? (int) $p->precio_financiado : '' }}" placeholder="Ej: 1140000"></div>
            <div class="col-6 col-md-3"><label class="form-label small">Cuota inicial</label><input data-dinero type="number" name="fin_cuota_inicial" class="form-control fp-ini" min="0" value="{{ isset($p) && $p->fin_cuota_inicial ? (int) $p->fin_cuota_inicial : '' }}" placeholder="Ej: 260000"></div>
            <div class="col-6 col-md-3"><label class="form-label small">No. de cuotas</label><input type="number" name="fin_num_cuotas" class="form-control fp-n" min="1" max="24" value="{{ $p->fin_num_cuotas ?? '' }}" placeholder="Ej: 4"></div>
        </div>
        <div class="small fw-semibold mt-2 fp-res" style="color:#11702f"></div>
    </div>
</div>
<script>
(function () {
    var box = document.getElementById(@json($uid)); if (!box) return;
    var form = box.closest('form'), fmt = new Intl.NumberFormat('es-CO');
    var q = function (c) { return (c === '.fp-pleno' ? form : box).querySelector(c); };
    function pct(pleno, v) { return pleno > 0 && v > 0 && v < pleno ? ' (' + (Math.round((pleno - v) / pleno * 10000) / 100).toString().replace('.', ',') + '% · −$' + fmt.format(pleno - v) + ')' : ''; }
    function calc() {
        var pleno = +q('.fp-pleno').value || 0, c = +q('.fp-contado').value || 0, f = +q('.fp-finan').value || 0, i = +q('.fp-ini').value || 0, n = +q('.fp-n').value || 0;
        var t = [];
        if (c > 0) t.push('Contado: $' + fmt.format(c) + pct(pleno, c));
        if (f > 0) t.push('Financiado: $' + fmt.format(f) + pct(pleno, f) + (n > 0 ? ' = cuota inicial $' + fmt.format(i) + ' + ' + n + ' cuotas de $' + fmt.format(Math.floor(Math.max(0, f - i) / n)) : ''));
        q('.fp-res').textContent = t.join(' · ');
    }
    form.addEventListener('input', calc); calc();
})();
</script>
