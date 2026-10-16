/*
 * WhatsApp en computador (y algunos celulares) muestra los emojis de los mensajes armados
 * con wa.me como "�". Aquí se quitan los emojis del texto de cualquier enlace o ventana de
 * WhatsApp antes de abrirlo, en todo el sistema. Las letras con tilde, ñ, ¡ ¿ y $ quedan igual.
 */
(function () {
    if (window.__lpWaLimpio) return; window.__lpWaLimpio = true;
    var EMOJI = /(?:[☀-➿⬀-⯿←-⇿⌀-⏿✀-➿]|\uD83C[\uDC00-\uDFFF]|\uD83D[\uDC00-\uDFFF]|\uD83E[\uDC00-\uDFFF]|[️‍⃣])/g;
    function esWa(u) { return /^(https?:)?\/\/(wa\.me|api\.whatsapp\.com|web\.whatsapp\.com)\//i.test(u) || /^whatsapp:/i.test(u); }
    function limpiarTexto(t) { return t.replace(EMOJI, '').replace(/[ \t]{2,}/g, ' ').replace(/ +\n/g, '\n').replace(/\n +/g, '\n'); }
    function limpiar(u) {
        if (!u || !esWa(String(u))) return u;
        try {
            var url = new URL(String(u), location.href), t = url.searchParams.get('text');
            if (t === null) return u;
            url.searchParams.set('text', limpiarTexto(t));
            return url.toString().replace(/\+/g, '%20');
        } catch (e) { return u; }
    }
    document.addEventListener('click', function (e) {
        var a = e.target.closest && e.target.closest('a[href]');
        if (a && esWa(a.getAttribute('href'))) a.href = limpiar(a.href);
    }, true);
    var abrir = window.open;
    window.open = function (u) { var args = Array.prototype.slice.call(arguments); args[0] = limpiar(u); return abrir.apply(window, args); };
    window.LPWaLimpio = { texto: limpiarTexto, url: limpiar };
})();
