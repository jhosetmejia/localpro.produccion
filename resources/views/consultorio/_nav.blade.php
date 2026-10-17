{{-- Navegación y estilos compartidos del consultorio --}}
@include('layouts.partials.alert')
<style>
.cs-tabs{display:flex;gap:6px;overflow-x:auto;margin:-.6rem 0 1.2rem;padding-bottom:2px;scrollbar-width:none}
.cs-tabs::-webkit-scrollbar{display:none}
.cs-tabs a{flex:none;display:flex;align-items:center;gap:.45rem;padding:.55rem 1rem;border-radius:999px;border:1.5px solid var(--lp-line);background:#fff;color:var(--lp-ink);font-weight:800;font-size:.86rem;text-decoration:none}
.cs-tabs a:hover{border-color:#bfeccb;color:var(--lp-ink)}
.cs-tabs a.on{background:var(--lp-black);border-color:var(--lp-black);color:#fff}
.cs-tabs a.on i{color:var(--lp-lime)}
.cs-err{background:#ffe9f3;color:#9b1c5a;border-radius:14px;padding:.7rem 1rem;font-weight:700;margin-bottom:1rem}
.cs-card{background:#fff;border:1.5px solid var(--lp-line);border-radius:22px;padding:1.1rem 1.2rem}
.cs-card h3{font-size:1rem;font-weight:900;margin:0 0 .8rem;display:flex;justify-content:space-between;align-items:center;gap:.5rem}
.cs-card h3 small{font-weight:700;color:var(--lp-muted);font-size:.78rem}
.cs-pac{display:flex;gap:.8rem;align-items:center;padding:.65rem 0;border-bottom:1px solid #eef3ef;text-decoration:none;color:inherit}
.cs-pac:last-child{border-bottom:0}
.cs-pac:hover b{color:var(--lp-green-2)}
.cs-av{flex:none;width:42px;height:42px;border-radius:14px;display:grid;place-items:center;background:#e7f6ec;color:#11702f;font-weight:900}
.cs-pac .tx{flex:1;min-width:0}.cs-pac .tx b{display:block;line-height:1.2}.cs-pac .tx small{color:var(--lp-muted);font-weight:600}
.cs-est{display:inline-block;border-radius:999px;padding:.15rem .6rem;font-size:.72rem;font-weight:800;color:#fff;white-space:nowrap}
.cs-btn{display:inline-flex;align-items:center;gap:.4rem;border-radius:999px;padding:.6rem 1.1rem;font-weight:800;text-decoration:none;border:1.5px solid var(--lp-line);background:#fff;color:var(--lp-ink);font-size:.9rem}
.cs-btn:hover{color:var(--lp-ink);border-color:#bfeccb}
.cs-btn.prim{background:var(--lp-grad);border:0;color:#0b0d0c}
.cs-btn.dark{background:var(--lp-black);border-color:var(--lp-black);color:#fff}.cs-btn.dark i{color:var(--lp-lime)}
.cs-btn.sm{padding:.35rem .75rem;font-size:.8rem}
.cs-alerta{background:#fff1f2;border:1.5px solid #fecdd3;color:#9f1239;border-radius:16px;padding:.6rem .9rem;font-weight:800;font-size:.88rem}
.cs-privado{display:flex;gap:.5rem;align-items:center;color:var(--lp-muted);font-size:.78rem;font-weight:700}
</style>
@if ($errors->any())<div class="cs-err"><i class="fa-solid fa-triangle-exclamation me-1"></i>{{ $errors->first() }}</div>@endif
@php $clinicoNav = auth()->user()->es_superadmin || auth()->user()->can('historia-clinica'); @endphp
<nav class="cs-tabs">
    <a href="{{ route('consultorio.inicio') }}" class="{{ request()->routeIs('consultorio.inicio') ? 'on' : '' }}"><i class="fa-solid fa-house-medical"></i>Hoy</a>
    <a href="{{ route('consultorio.agenda') }}" class="{{ request()->routeIs('consultorio.agenda') ? 'on' : '' }}"><i class="fa-solid fa-calendar-check"></i>Agenda</a>
    <a href="{{ route('consultorio.pacientes') }}" class="{{ request()->routeIs('consultorio.pacientes*', 'consultorio.paciente', 'consultorio.atencion*') ? 'on' : '' }}"><i class="fa-solid fa-users"></i>Pacientes</a>
    <a href="{{ route('consultorio.configuracion') }}" class="{{ request()->routeIs('consultorio.configuracion') ? 'on' : '' }}"><i class="fa-solid fa-sliders"></i>{{ $clinicoNav ? 'Mi perfil y firma' : 'Configuración' }}</a>
</nav>
