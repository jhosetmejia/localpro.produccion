<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, maximum-scale=1">
<meta name="theme-color" content="#008069" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#202c33" media="(prefers-color-scheme: dark)">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="LP Chat, la red social de LocalProo: chatea, crea grupos y conoce gente. Gratis.">
<meta property="og:title" content="LP Chat · Chatea y conoce gente">
<meta property="og:description" content="La red social de LocalProo: chats, grupos y conocer gente. Entra con tu correo o tu celular.">
<meta property="og:image" content="{{ url('/icons/lpchat-512.png') }}">
<link rel="icon" href="/icons/lpchat-192.png" type="image/png">
<link rel="manifest" href="/lpchat.webmanifest">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="LP Chat">
<link rel="apple-touch-icon" href="/icons/lpchat-apple-touch.png">
<style>
:root{--verde:#00a884;--verde2:#008069;--fondo:#f0f2f5;--panel:#fff;--panel2:#f0f2f5;--borde:#e9edef;--txt:#111b21;--txt2:#667781;--burbuja-yo:#d9fdd3;--burbuja-el:#fff;--wall:#efeae2;--azul:#53bdeb;--sel:#f0f2f5;--hover:#f5f6f6;--chip:#e7fce3;--chipt:#008069}
@media (prefers-color-scheme: dark){:root{--fondo:#0c1317;--panel:#111b21;--panel2:#202c33;--borde:#222d34;--txt:#e9edef;--txt2:#8696a0;--burbuja-yo:#005c4b;--burbuja-el:#202c33;--wall:#0b141a;--sel:#2a3942;--hover:#202c33;--chip:#103529;--chipt:#00a884}}
*{box-sizing:border-box;-webkit-tap-highlight-color:transparent}
html,body{margin:0;height:100%;background:var(--fondo);color:var(--txt);font-family:"Segoe UI",Helvetica,"Helvetica Neue",Arial,system-ui,sans-serif;font-size:15px}
button,input,textarea{font:inherit;color:inherit}
button{cursor:pointer}
a{color:var(--verde2)}
[hidden]{display:none!important}
</style>
