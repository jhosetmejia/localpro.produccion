{{-- Avisos de LocalPro: lo guardado sale como aviso flotante; los errores del formulario quedan a la vista --}}
@include('layouts.partials.alert')
@if ($errors->any())
<div class="alert border-0 d-flex align-items-center gap-2" style="background:#ffe9f3;color:#9b1c5a;border-radius:16px;font-weight:600"><i class="fa-solid fa-triangle-exclamation"></i> {{ $errors->first() }}</div>
@endif
