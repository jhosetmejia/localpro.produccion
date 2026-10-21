{{-- Guía: cómo poner el obituario en el televisor de la sala --}}
<details class="ob-tv-ayuda {{ $clase ?? '' }}">
    <summary><i class="fa-solid fa-circle-question me-1"></i>¿Cómo poner el obituario en el TV de la sala?</summary>
    <div class="mt-2 small">
        <p class="mb-2">La pantalla de cada sala es una página web. Lo mejor es que <b>el televisor la abra solo</b> y quede ahí, sin depender del computador de recepción. Cuando cambie algo en LocalProo, el TV se actualiza solo.</p>

        <p class="mb-1"><b><span class="badge bg-success">Recomendado</span> 1. Que el TV la abra solo</b></p>
        <ul class="mb-2 ps-3">
            <li><b>Si es Smart TV:</b> abra el navegador del televisor y escriba la <b>dirección corta</b> de la sala, por ejemplo <b>{{ preg_replace('#^https?://(www\.)?#', '', url('/tv')) }}/1234</b> (el código de cada sala aparece en la lista de salas). También puede escribir solo <b>{{ preg_replace('#^https?://(www\.)?#', '', url('/tv')) }}</b> y digitar el código con el control. Luego toque la pantalla (o dé OK) para verla completa.</li>
            <li><b>Si no es Smart TV o su navegador es malo:</b> conéctele por HDMI un <b>Fire TV Stick</b> o un <b>Google TV / Chromecast con control</b> (unos $150.000–$250.000). Instale un navegador (por ejemplo, Silk o Chrome) y abra la dirección.</li>
            <li>Cada sala tiene <b>su propio código</b>. Si cambia la dirección de una sala, también cambia el código.</li>
            <li>Guárdela como favorito en el navegador del TV para abrirla rápido si se apaga.</li>
        </ul>

        <p class="mb-1"><b>2. Con cable desde el computador de recepción</b></p>
        <ul class="mb-2 ps-3">
            <li><b>Hasta 10–15 m:</b> cable HDMI largo. Si pasa de 10 m, que sea <b>HDMI activo o de fibra</b>.</li>
            <li><b>Más lejos u otro piso:</b> un <b>extensor HDMI por cable de red (HDBaseT)</b>.</li>
            <li>En el computador: <b>Windows + P → Extender</b>, arrastre la ventana de la pantalla al TV y oprima <b>F11</b>.</li>
            <li>Ojo: si alguien cierra o mueve esa ventana en recepción, el TV queda en negro. Sirve para una sola sala por cada salida de video.</li>
        </ul>

        <p class="mb-1"><b>3. Inalámbrico desde el computador (Windows + K)</b></p>
        <ul class="mb-2 ps-3">
            <li>Funciona con TV compatibles con Miracast, pero se puede cortar y depende de que el computador esté prendido. Úselo solo para salir del paso.</li>
        </ul>

        <p class="mb-0 text-muted"><i class="fa-solid fa-lightbulb me-1"></i>Si el TV muestra solo el logo y el nombre de la sala, no hay obituario vigente en esa sala: entre al obituario y dé <b>«Ver ya en la pantalla»</b>.</p>
    </div>
</details>
<style>
.ob-tv-ayuda{border:1.5px dashed #cfe3d4;border-radius:14px;padding:.6rem .8rem;background:#f7fbf8}
.ob-tv-ayuda summary{cursor:pointer;font-weight:600;color:#11702f}
.ob-tv-ayuda ul li{margin-bottom:.2rem}
</style>
