@extends('layouts.app')

@section('title', 'Carta para imprimir')

@push('css')
<style>
.mc-frame{display:block;width:100%;height:calc(100vh - 56px);border:0;background:#e9ece8}
</style>
@endpush

@section('content')
<iframe class="mc-frame" src="{{ $src }}" title="Carta para imprimir"></iframe>
@endsection

@push('js')
<script>
// En computador el menú lateral queda contraído para que la hoja se vea grande (se abre con el botón ☰ de arriba)
if (window.innerWidth >= 992) document.body.classList.add('sb-sidenav-toggled');
</script>
@endpush
