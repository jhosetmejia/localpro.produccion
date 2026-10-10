@if (session('success'))
<div class="alert alert-success d-flex align-items-center gap-2 border-0" style="background:#e8f8ec;color:#11702f"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if ($errors->any())
<div class="alert border-0" style="background:#ffe9f3;color:#9b1c5a"><i class="fa-solid fa-triangle-exclamation me-1"></i> {{ $errors->first() }}</div>
@endif
