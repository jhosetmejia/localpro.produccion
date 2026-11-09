{{-- Pestañas de Visitas y uso: todo el sistema / LocalProCut --}}
<div class="vs-tabs">
    <a href="{{ route('admin.sitio') }}" class="{{ request()->routeIs('admin.sitio*') ? 'on' : '' }}"><i class="fa-solid fa-globe me-1"></i>Todo el sistema</a>
    <a href="{{ route('admin.cut') }}" class="{{ request()->routeIs('admin.cut*') ? 'on' : '' }}"><i class="fa-solid fa-scissors me-1"></i>LocalProCut (editor)</a>
</div>
<style>
.vs-tabs{display:flex;gap:.3rem;border-bottom:2px solid #e3efe5;margin:0 0 1rem}
.vs-tabs a{padding:.6rem 1.1rem;font-weight:800;color:#5b665e;text-decoration:none;border-radius:14px 14px 0 0;margin-bottom:-2px;border-bottom:3px solid transparent}
.vs-tabs a.on{color:#0b0d0c;border-bottom-color:#16b84e;background:#fff}
</style>
