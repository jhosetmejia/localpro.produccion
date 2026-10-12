{{-- Ojito para ver la clave: se agrega solo a todo campo de contraseña del sistema --}}
<style>
.lp-pw{position:relative;display:block}
.lp-pw>input{padding-right:46px!important;width:100%}
.lp-pw-btn{position:absolute;right:8px;top:50%;transform:translateY(-50%);width:34px;height:34px;border:0;background:none;color:#93a097;border-radius:10px;cursor:pointer;display:grid;place-items:center;padding:0;z-index:2}
.lp-pw-btn:hover{color:#16b34a;background:rgba(22,179,74,.08)}
.lp-pw-btn svg{width:20px;height:20px}
</style>
<script>
(function () {
    var ojo = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>';
    var tapado = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.1 10.1 0 0 1 12 19c-6.5 0-10-7-10-7a18.5 18.5 0 0 1 5.06-5.94M9.9 4.24A9.1 9.1 0 0 1 12 4c6.5 0 10 7 10 7a18.6 18.6 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><path d="M1 1l22 22"/></svg>';
    function preparar() {
        document.querySelectorAll('input[type=password]:not([data-sin-ojo])').forEach(function (inp) {
            if (inp._lpOjo) return;
            var p = inp.parentElement;
            // Si la pantalla ya trae su propio botón (ej. el login), no se duplica
            if (p && p.querySelector('.toggle-pass, .lp-pw-btn')) { inp._lpOjo = true; return; }
            inp._lpOjo = true;
            var caja = document.createElement('span'); caja.className = 'lp-pw';
            inp.parentNode.insertBefore(caja, inp); caja.appendChild(inp);
            var b = document.createElement('button'); b.type = 'button'; b.className = 'lp-pw-btn'; b.setAttribute('aria-label', 'Mostrar contraseña'); b.innerHTML = ojo;
            b.addEventListener('click', function () {
                var ver = inp.type === 'password';
                inp.type = ver ? 'text' : 'password';
                b.innerHTML = ver ? tapado : ojo;
                b.setAttribute('aria-label', ver ? 'Ocultar contraseña' : 'Mostrar contraseña');
                inp.focus();
            });
            caja.appendChild(b);
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', preparar); else preparar();
})();
</script>
