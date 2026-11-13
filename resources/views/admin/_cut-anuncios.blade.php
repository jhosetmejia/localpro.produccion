{{-- Anuncios de LocalProCut: se ven en el panel de calidad y mientras el video se exporta --}}
@include('layouts.partials.lp-flash')
@php $anuncios = \App\Support\AnunciosCut::todos(); $filas = array_merge($anuncios, array_fill(0, 2, ['e' => '', 't' => '', 's' => '', 'u' => '', 'img' => '', 'hasta' => null, 'on' => true])); @endphp
<form method="post" action="{{ route('admin.cut.anuncios') }}" class="lp-box mt-3">@csrf
    <h3><i class="fa-solid fa-bullhorn me-1"></i>Anuncios del editor</h3>
    <p class="text-muted small fw-semibold">Salen mientras la gente espera que se exporte su video (van cambiando cada pocos segundos) y en el botón de calidad. Llene título y enlace; los vencidos ("hasta") se ocultan solos. Deje en blanco una fila para quitarla.</p>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead><tr><th style="width:56px">Ícono</th><th>Título</th><th>Texto</th><th>Enlace</th><th>Imagen (enlace, opcional)</th><th style="width:140px">Hasta</th><th>Activo</th></tr></thead>
            <tbody>
            @foreach ($filas as $i => $a)
                <tr>
                    <td><input name="a[{{ $i }}][e]" value="{{ $a['e'] ?? '' }}" class="form-control form-control-sm text-center" maxlength="8"></td>
                    <td><input name="a[{{ $i }}][t]" value="{{ $a['t'] ?? '' }}" class="form-control form-control-sm" maxlength="90" placeholder="Ej: Las mejores chaquetas de Pasto"></td>
                    <td><input name="a[{{ $i }}][s]" value="{{ $a['s'] ?? '' }}" class="form-control form-control-sm" maxlength="120"></td>
                    <td><input name="a[{{ $i }}][u]" value="{{ $a['u'] ?? '' }}" class="form-control form-control-sm" maxlength="400" placeholder="https://localproo.com/..."></td>
                    <td><input name="a[{{ $i }}][img]" value="{{ $a['img'] ?? '' }}" class="form-control form-control-sm" maxlength="400"></td>
                    <td><input type="date" name="a[{{ $i }}][hasta]" value="{{ $a['hasta'] ?? '' }}" class="form-control form-control-sm"></td>
                    <td class="text-center"><input type="hidden" name="a[{{ $i }}][on]" value="0"><input type="checkbox" name="a[{{ $i }}][on]" value="1" class="form-check-input" @checked($a['on'] ?? true)></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Guardar anuncios</button>
        <button class="btn btn-light" name="restaurar" value="1"><i class="fa-solid fa-rotate me-1"></i>Volver a los automáticos</button>
    </div>
</form>
