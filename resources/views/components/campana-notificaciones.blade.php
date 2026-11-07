@php
    $notis = \App\Support\Notificaciones::paraUsuario(auth()->user());
    $nuevas = \App\Support\Notificaciones::sinVer(auth()->user(), $notis);
@endphp
<li class="nav-item dropdown lp-noti">
    <a class="nav-link position-relative" href="#" id="lpNotiBtn" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-label="Notificaciones{{ $nuevas ? ': ' . $nuevas . ' nuevas' : '' }}" title="Notificaciones">
        <i class="fa-solid fa-bell {{ $nuevas ? 'lp-noti-mueve' : '' }}"></i>
        @if ($nuevas)<span class="lp-noti-num" id="lpNotiNum">{{ $nuevas > 99 ? '99+' : $nuevas }}</span>@endif
    </a>
    <div class="dropdown-menu dropdown-menu-end lp-noti-menu">
        <div class="lp-noti-tit">Notificaciones</div>
        @forelse ($notis as $n)
        <div class="lp-noti-item">
            <a class="lp-noti-cuerpo" href="{{ $n['url'] ?? ($n['wa'] ?? '#') }}" @if (!$n['url'] && $n['wa']) target="_blank" rel="noopener" @endif>
                <span class="lp-noti-ic {{ $n['color'] }}"><i class="fa-solid {{ $n['icono'] }}"></i></span>
                <span class="lp-noti-txt">{!! $n['texto'] !!}<small class="{{ $n['color'] }}">{{ $n['cuando'] }}</small></span>
            </a>
            @if ($n['wa'])<a class="lp-noti-wa" href="{{ $n['wa'] }}" target="_blank" rel="noopener" title="{{ $n['wa_titulo'] }}"><i class="fa-brands fa-whatsapp"></i></a>@endif
        </div>
        @empty
        <div class="lp-noti-vacio"><i class="fa-regular fa-bell"></i><div>No tiene notificaciones</div></div>
        @endforelse
    </div>
</li>
<style>
.lp-noti .nav-link{font-size:1.15rem;padding:.35rem .6rem}
.lp-noti-num{position:absolute;top:0;right:0;background:#e41e3f;color:#fff;font-size:.65rem;font-weight:800;border-radius:999px;padding:1px 5px;line-height:1.3;border:2px solid #fff}
.lp-noti-mueve{animation:lpNoti 2.4s ease-in-out infinite;transform-origin:50% 0}
@keyframes lpNoti{0%,70%,100%{transform:rotate(0)}75%{transform:rotate(14deg)}80%{transform:rotate(-12deg)}85%{transform:rotate(8deg)}90%{transform:rotate(-6deg)}}
@media (prefers-reduced-motion:reduce){.lp-noti-mueve{animation:none}}
.lp-noti-menu{width:360px;max-width:94vw;max-height:75vh;overflow:auto;padding:.3rem;border-radius:14px;box-shadow:0 12px 32px rgba(0,0,0,.18)}
.lp-noti-tit{font-weight:800;font-size:1.25rem;padding:.5rem .7rem .4rem}
.lp-noti-item{display:flex;align-items:center;border-radius:10px}
.lp-noti-item:hover{background:#f2f3f5}
.lp-noti-cuerpo{flex:1;display:flex;gap:.7rem;align-items:center;padding:.5rem .6rem;color:inherit;text-decoration:none;min-width:0}
.lp-noti-ic{flex:0 0 44px;height:44px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.05rem;background:#1877f2}
.lp-noti-ic.verde{background:#16a34a}.lp-noti-ic.amarillo{background:#f59e0b}.lp-noti-ic.naranja{background:#ea580c}.lp-noti-ic.rojo{background:#dc2626}.lp-noti-ic.morado{background:#7c3aed}
.lp-noti-txt{font-size:.88rem;line-height:1.3}
.lp-noti-txt small{display:block;margin-top:2px;font-weight:700;color:#1877f2}
.lp-noti-txt small.rojo{color:#dc2626}.lp-noti-txt small.naranja{color:#ea580c}.lp-noti-txt small.amarillo{color:#b45309}.lp-noti-txt small.verde{color:#16a34a}
.lp-noti-tienda{display:block;color:#6b7280;font-size:.78rem}
.lp-noti-wa{color:#16a34a;font-size:1.35rem;padding:.3rem .55rem}
.lp-noti-vacio{text-align:center;color:#6b7280;padding:1.6rem .5rem}.lp-noti-vacio i{font-size:1.8rem;margin-bottom:.4rem}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var b = document.getElementById('lpNotiBtn');
    if (!b) return;
    b.addEventListener('show.bs.dropdown', function () {
        var n = document.getElementById('lpNotiNum');
        if (!n) return;
        n.remove(); b.querySelector('.fa-bell').classList.remove('lp-noti-mueve');
        fetch(@json(route('notificaciones.vistas')), { method: 'POST', headers: { 'X-CSRF-TOKEN': @json(csrf_token()), Accept: 'application/json' } });
    });
});
</script>
