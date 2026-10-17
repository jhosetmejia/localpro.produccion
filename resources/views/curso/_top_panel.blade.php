{{-- Dentro del panel no va la barra propia del curso: solo el aviso de vista de administrador --}}
@if (!empty($admin))
<div class="msg ok" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between">
    <span><i class="fa-solid fa-user-shield"></i> Vista de administrador: así lo ve la red. Su avance no se guarda y el certificado es de muestra.</span>
    <span style="display:flex;gap:8px"><form method="post" action="{{ route('curso.reiniciar') }}" style="margin:0">@csrf<button class="btn btn-l" style="padding:.45rem .9rem;font-size:.85rem">Reiniciar</button></form><a href="{{ route('admin.red.index') }}" class="btn btn-k" style="padding:.45rem .9rem;font-size:.85rem">Volver al admin</a></span>
</div>
@endif
