{{-- Campos propios que el organizador agregó al formulario de registro --}}
@foreach ($evento->camposFormulario() as $c)
    @php $n = 'respuestas[' . $c['id'] . ']'; $v = old('respuestas.' . $c['id']); $idc = 'cp-' . $c['id']; @endphp
    <label class="lb" for="{{ $idc }}">{{ $c['etiqueta'] }}@unless ($c['obligatorio']) <small class="mu" style="font-weight:500">(opcional)</small>@endunless</label>
    @switch ($c['tipo'])
        @case('parrafo')
            <textarea class="in" id="{{ $idc }}" name="{{ $n }}" rows="3" maxlength="1000" @required($c['obligatorio'])>{{ $v }}</textarea>
            @break
        @case('numero')
            <input class="in" id="{{ $idc }}" type="number" name="{{ $n }}" value="{{ $v }}" inputmode="decimal" @required($c['obligatorio'])>
            @break
        @case('fecha')
            <input class="in" id="{{ $idc }}" type="date" name="{{ $n }}" value="{{ $v }}" @required($c['obligatorio'])>
            @break
        @case('lista')
            <select class="in" id="{{ $idc }}" name="{{ $n }}" @required($c['obligatorio'])>
                <option value="">Elige una opción</option>
                @foreach ($c['opciones'] as $o)<option value="{{ $o }}" @selected($v === $o)>{{ $o }}</option>@endforeach
            </select>
            @break
        @case('si_no')
            <select class="in" id="{{ $idc }}" name="{{ $n }}" @required($c['obligatorio'])>
                <option value="">Elige</option>
                <option value="Sí" @selected($v === 'Sí')>Sí</option>
                <option value="No" @selected($v === 'No')>No</option>
            </select>
            @break
        @default
            <input class="in" id="{{ $idc }}" name="{{ $n }}" value="{{ $v }}" maxlength="200" @required($c['obligatorio'])>
    @endswitch
@endforeach
