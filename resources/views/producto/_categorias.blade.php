{{-- Categorías como botones para tocar (funciona bien en celular). Si no elige ninguna, queda en "General". --}}
@php $elegidas = collect(old('categorias', $seleccionadas ?? []))->map(fn ($x) => (int) $x)->all(); @endphp
<div class="col-12">
    <label class="form-label">Categorías <span class="text-muted fw-normal">(toque para elegir una o varias)</span></label>
    <div class="lp-chips">
        @foreach ($categorias as $item)
        <input type="checkbox" class="btn-check" name="categorias[]" id="cat{{ $item->id }}" value="{{ $item->id }}" autocomplete="off" @checked(in_array((int) $item->id, $elegidas, true))>
        <label class="lp-chip" for="cat{{ $item->id }}"><i class="fa-solid fa-check"></i> {{ $item->nombre }}</label>
        @endforeach
        @can('crear-categoria')
        <a href="{{ route('categorias.create') }}" class="lp-chip lp-chip-nueva" target="_blank"><i class="fa-solid fa-plus"></i> Nueva categoría</a>
        @endcan
    </div>
    <small class="text-muted">Si no elige ninguna, el producto queda en "General".</small>
    @error('categorias')
    <small class="text-danger d-block">{{'*'.$message}}</small>
    @enderror
</div>
