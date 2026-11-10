{{-- Video que viene de LocalProCut ("Programar en redes sociales"): en Publicar se carga solo; en las otras pantallas se avisa --}}
@php $tC2 = \App\Support\TiendaActual::tienda(); @endphp
<div class="lpvc" id="lpvc" hidden>
    <span class="lpvc-ic">🎬</span>
    <div class="lpvc-tx"><b>Su video de LocalProCut está listo para publicar</b><small data-lpvc-sub>Conecte Facebook, Instagram o YouTube y luego toque <b>Publicar</b>.</small></div>
    <button type="button" class="btn btn-sm lpvc-btn" data-lpvc-usar hidden>Usar este video</button>
    <a class="btn btn-sm lpvc-btn" data-lpvc-ir href="{{ route('redes.publicar') }}" hidden>Publicar o programar</a>
    <button type="button" class="lpvc-x" data-lpvc-quitar title="Descartar">✕</button>
</div>
@if ($tC2 && $tC2->tipo_negocio === 'creador')
<form method="post" action="{{ route('creador.emprendimiento') }}" class="lpvc lpvc-emp">@csrf
    <span class="lpvc-ic">🚀</span>
    <div class="lpvc-tx"><b>¿Tiene un emprendimiento?</b><small>Actívelo gratis aquí mismo: caja, inventario, tienda en línea con WhatsApp, clientes y más.</small></div>
    <button class="btn btn-sm lpvc-btn">Activar mi negocio</button>
</form>
@endif
<style>
.lpvc{display:flex;align-items:center;gap:.8rem;margin:0 0 14px;padding:.8rem 1rem;border-radius:18px;background:linear-gradient(120deg,#1b0f33,#3a1550);color:#fff;flex-wrap:wrap}
.lpvc[hidden]{display:none}
.lpvc-emp{background:linear-gradient(120deg,#0b0d0c,#13261a)}
.lpvc-ic{font-size:1.6rem}
.lpvc-tx{flex:1 1 220px;display:flex;flex-direction:column}
.lpvc-tx small{color:#d9d2e8;font-weight:600}
.lpvc-btn{background:linear-gradient(90deg,#a6f34a,#16b84e);color:#0b0d0c;font-weight:800;border-radius:999px;border:0;padding:.45rem 1rem}
.lpvc-x{background:none;border:0;color:#cbbfe0;font-size:1rem}
</style>
<script>
// Espera a que la página termine (el campo del video de Publicar se arma al final)
(function (correr) { if (document.readyState === 'complete') correr(); else window.addEventListener('load', correr); })(function () {
    var caja = document.getElementById('lpvc'); if (!caja || !window.indexedDB) return;
    function abrir(cb) { try { var r = indexedDB.open('localprocut-publicar', 1); r.onupgradeneeded = function () { r.result.createObjectStore('v'); }; r.onsuccess = function () { cb(r.result); }; } catch (e) {} }
    function borrar() { abrir(function (db) { try { db.transaction('v', 'readwrite').objectStore('v').delete('video'); } catch (e) {} }); }
    abrir(function (db) {
        var g; try { g = db.transaction('v').objectStore('v').get('video'); } catch (e) { return; }
        g.onsuccess = function () {
            var x = g.result;
            if (!x || !x.file) return;
            if (Date.now() - (x.t || 0) > 2 * 86400000) { borrar(); return; }
            caja.hidden = false;
            var input = document.getElementById('vArchivo');
            caja.querySelector('[data-lpvc-quitar]').onclick = function () { borrar(); caja.hidden = true; };
            if (input) {
                // En Publicar: se carga solo en el campo del video (así arranca la subida) y se borra del equipo
                caja.querySelector('[data-lpvc-sub]').innerHTML = 'Lo cargamos en el campo <b>Video</b>: escriba el texto y toque <b>Publicar ahora</b> o <b>Programar</b>.';
                var usar = caja.querySelector('[data-lpvc-usar]'); usar.hidden = false;
                var poner = function () {
                    try {
                        var tipoVideo = document.querySelector('input[name=tipo][value=video], input[name=tipo][value=reel]');
                        if (tipoVideo && !tipoVideo.checked) { tipoVideo.checked = true; tipoVideo.dispatchEvent(new Event('change', { bubbles: true })); }
                        var dt = new DataTransfer(); dt.items.add(new File([x.file], x.nombre || 'video.mp4', { type: 'video/mp4' }));
                        input.files = dt.files; input.dispatchEvent(new Event('change', { bubbles: true }));
                        usar.hidden = true; borrar();
                        caja.querySelector('[data-lpvc-sub]').innerHTML = '✓ Video cargado. Escriba el texto y toque <b>Publicar ahora</b> o <b>Programar</b>.';
                        (document.getElementById('vZona') || input).scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } catch (e) { caja.querySelector('[data-lpvc-sub]').textContent = 'Toque el campo Video y escoja el archivo que descargó de LocalProCut.'; }
                };
                usar.onclick = poner;
                poner();
            } else {
                caja.querySelector('[data-lpvc-ir]').hidden = false;
            }
        };
    });
});
</script>
