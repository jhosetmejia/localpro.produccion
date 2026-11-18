<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1">
<meta name="theme-color" content="#0b0d0c">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="LP Chat, la red social de LocalProo: chatea, crea grupos y conoce gente. Gratis.">
<meta property="og:title" content="LP Chat · Chatea y conoce gente">
<meta property="og:description" content="La red social de LocalProo: chats, grupos y conocer gente. Entra con tu correo o tu celular.">
<meta property="og:image" content="{{ url('/icons/lpchat-512.png') }}">
<link rel="icon" href="/icons/lpchat-192.png" type="image/png">
<link rel="manifest" href="/lpchat.webmanifest">
{{-- 2026-11-18 (Jhoset): el instalador de Android se atrapa aquí arriba, apenas carga la página (antes se escuchaba al final del
     script del chat y si el aviso del navegador llegaba antes, o algo fallaba antes, el menú "Instalar app" solo mostraba pasos). --}}
<script>
window.__lpInst = null;
window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); window.__lpInst = e; try { document.dispatchEvent(new Event('lp-instalable')); } catch (x) {} });
window.addEventListener('appinstalled', function () { window.__lpInst = null; try { localStorage.setItem('lpchat_instalada', '1'); } catch (x) {} });
</script>
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="LP Chat">
<link rel="apple-touch-icon" href="/icons/lpchat-apple-touch.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
<style>
/* 2026-11-17: mismos colores de LocalProo (negro con el verde del sistema), siempre oscuro */
:root{color-scheme:dark;--verde:#a6f34a;--verde2:#16b84e;--sobre-verde:#0b0d0c;--fondo:#070908;--panel:#0b0d0c;--panel2:#151a17;--panel3:#1f2622;--borde:#232b26;--txt:#f3f7f4;--txt2:#9aa6a0;--burbuja-yo:#a6f34a;--burbuja-el:#1a201c;--wall:#0d100e;--azul:#0b6b2c;--sel:#1a201c;--hover:#131815;--chip:rgba(166,243,74,.12);--chipt:#a6f34a;--grad:linear-gradient(135deg,#a6f34a,#16b84e)}
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
html,body{margin:0;height:100%;background:var(--fondo);color:var(--txt);font-family:Figtree,system-ui,-apple-system,"Segoe UI",sans-serif;font-size:15px}
button,input,textarea{font:inherit;color:inherit}
input::placeholder,textarea::placeholder{color:#6f7b75}
button{cursor:pointer}
a{color:var(--verde)}
[hidden]{display:none!important}
::-webkit-scrollbar{width:8px;height:8px}::-webkit-scrollbar-thumb{background:#2b332e;border-radius:8px}::-webkit-scrollbar-track{background:transparent}
.marca-lp{font-family:Montserrat,Figtree,sans-serif;font-weight:900;letter-spacing:-.02em}
.marca-lp b{color:var(--verde)}
</style>
