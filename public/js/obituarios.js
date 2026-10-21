/* Obituarios LocalProo: escala el lienzo, ajusta el texto, anima el fondo,
   actualiza la vista previa en vivo y descarga la imagen en la medida de la pantalla. */
(function () {
    'use strict';
    var Obi = window.Obi = window.Obi || {};
    var anims = new WeakMap();

    function todos(raiz) { return [].slice.call((raiz || document).querySelectorAll('[data-obi]')); }
    function azar(a, b) { return a + Math.random() * (b - a); }
    function esc(t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; }

    // ===== Escala: el lienzo se diseña en tamaño fijo y se ajusta al contenedor =====
    Obi.escalar = function (el) {
        var marco = el.parentElement;
        if (!marco || !marco.hasAttribute('data-obi-marco')) return;
        var w = +el.dataset.w, h = +el.dataset.h, s;
        if (marco.getAttribute('data-obi-marco') === 'pantalla') {
            s = Math.min(marco.clientWidth / w, marco.clientHeight / h);
            el.style.left = ((marco.clientWidth - w * s) / 2) + 'px';
            el.style.top = ((marco.clientHeight - h * s) / 2) + 'px';
        } else {
            s = marco.clientWidth / w;
            marco.style.height = (h * s) + 'px';
            el.style.left = '0'; el.style.top = '0';
        }
        el.style.transform = 'scale(' + s + ')';
    };

    // ===== Texto: si no cabe (muchos familiares), se reduce un poco hasta que quepa =====
    Obi.ajustar = function (el) {
        var c = el.querySelector(el.classList.contains('lay-tarjeta') ? '.ot-main' : '.obi-centro');
        if (!c) return;
        var g = el.querySelector('.obi-grupos');
        if (g) g.classList.toggle('dos', g.children.length > 6);
        var k = 1;
        el.style.setProperty('--k', '1');
        for (var i = 0; i < 18 && c.scrollHeight > c.clientHeight + 2; i++) {
            k -= 0.035;
            el.style.setProperty('--k', k.toFixed(3));
        }
    };

    // ===== Animaciones de fondo =====
    var PALOMA = '<svg viewBox="0 0 150 110"><path fill="currentColor" d="M8 66c18-6 34-8 50-8 14 0 26-4 38-12 8-6 14-14 22-16 7-2 13 2 12 8-4-1-8 0-11 3 4 10 2 22-8 30-12 10-32 14-52 12-10-1-18-4-26-8L8 90l10-14-12-6z"/><path class="ala" fill="currentColor" d="M64 56C58 34 66 14 96 4c-6 12-6 24-4 34-8 10-16 16-28 18z"/></svg>';

    Obi.animar = function (el) {
        var capa = el.querySelector('[data-anim-capa]');
        if (!capa) return;
        var prev = anims.get(el);
        if (prev) prev();
        capa.innerHTML = '';
        var tipo = el.dataset.anim || 'ninguna';
        // Pétalos, destellos, estrellas y palomas pasan por encima de todo; nubes y rayos van detrás del texto
        capa.classList.toggle('encima', ['petalos', 'destellos', 'estrellas', 'palomas'].indexOf(tipo) !== -1);
        var w = +el.dataset.w, h = +el.dataset.h;
        // Cantidad, tamaño y velocidad (100 = normal)
        var op = { cant: (+el.dataset.animCant || 100) / 100, tam: (+el.dataset.animTam || 100) / 100, vel: (+el.dataset.animVel || 100) / 100 };
        var parar = function () {};

        if (tipo === 'luz') {
            capa.innerHTML = '<div class="obi-rayos" style="animation-duration:' + (90 / op.vel) + 's;opacity:' + Math.min(1, .5 + op.cant / 2) + '"></div>';
        } else if (tipo === 'nubes') {
            for (var i = 0; i < Math.round(6 * op.cant); i++) {
                var n = document.createElement('div'), dur = azar(80, 150) / op.vel;
                n.className = 'obi-nube';
                n.style.cssText = 'width:' + azar(w * .25, w * .45) * op.tam + 'px;height:' + azar(h * .12, h * .22) * op.tam + 'px;top:' + azar(0, h * .8) + 'px;animation-duration:' + dur + 's;animation-delay:-' + azar(0, dur) + 's';
                capa.appendChild(n);
            }
        } else if (tipo === 'palomas') {
            for (var j = 0; j < Math.max(1, Math.round(3 * op.cant)); j++) {
                var p = document.createElement('div'), d2 = azar(14, 22) / op.vel;
                p.className = 'obi-paloma';
                p.innerHTML = PALOMA;
                p.style.cssText = 'top:' + azar(h * .06, h * .45) + 'px;animation-duration:' + d2 + 's;animation-delay:-' + azar(0, d2) + 's;transform:scale(' + (azar(.6, 1.1) * op.tam).toFixed(2) + ')';
                p.querySelector('.ala').style.animationDelay = '-' + azar(0, 1).toFixed(2) + 's';
                capa.appendChild(p);
            }
        } else if (tipo === 'petalos' || tipo === 'destellos' || tipo === 'estrellas') {
            parar = particulas(capa, tipo, w, h, getComputedStyle(el).getPropertyValue('--acento').trim() || '#d4af37', op);
        }
        anims.set(el, parar);
    };

    function particulas(capa, tipo, w, h, acento, op) {
        op = op || { cant: 1, tam: 1, vel: 1 };
        var cv = document.createElement('canvas'), f = .5; // media resolución: suficiente y liviano para el televisor
        cv.width = Math.round(w * f); cv.height = Math.round(h * f);
        capa.appendChild(cv);
        var ctx = cv.getContext('2d'), W = cv.width, H = cv.height, ps = [], vivo = true, t0 = 0;
        var cant = Math.round((tipo === 'petalos' ? 45 : tipo === 'destellos' ? 70 : 110) * op.cant), T = op.tam, V = op.vel;
        var rosas = ['#f8c9d4', '#ffffff', '#f3a7ba', '#fde2e7'];
        for (var i = 0; i < cant; i++) ps.push(nueva(true));

        function nueva(inicio) {
            if (tipo === 'petalos') return { x: azar(0, W), y: inicio ? azar(-H * .15, H) : azar(-60, -10), s: azar(6, 12) * T, vy: azar(.35, .8), fase: azar(0, 6.3), r: azar(0, 6.3), vr: azar(-.02, .02), c: rosas[(Math.random() * rosas.length) | 0] };
            if (tipo === 'destellos') return { x: azar(0, W), y: inicio ? azar(0, H) : H + 10, s: azar(1, 3.2) * T, vy: -azar(.12, .4), fase: azar(0, 6.3), v: azar(.01, .03) };
            return { x: azar(0, W), y: azar(0, H), s: azar(.6, 2.2) * T, fase: azar(0, 6.3), v: azar(.008, .03), brillo: Math.random() < .12 };
        }

        function petalo(p) {
            ctx.save();
            ctx.translate(p.x, p.y); ctx.rotate(p.r);
            ctx.fillStyle = p.c; ctx.globalAlpha = .9;
            ctx.beginPath();
            ctx.moveTo(0, -p.s);
            ctx.bezierCurveTo(p.s, -p.s, p.s, p.s * .6, 0, p.s);
            ctx.bezierCurveTo(-p.s, p.s * .6, -p.s, -p.s, 0, -p.s);
            ctx.fill();
            ctx.restore();
        }

        function cuadro(t) {
            if (!vivo) return;
            if (!document.hidden) {
                var dt = (t0 ? Math.min((t - t0) / 16.7, 3) : 1) * V;
                t0 = t;
                ctx.clearRect(0, 0, W, H);
                for (var i = 0; i < ps.length; i++) {
                    var p = ps[i];
                    if (tipo === 'petalos') {
                        p.fase += .012 * dt; p.y += p.vy * dt; p.x += Math.sin(p.fase) * .5 * dt; p.r += p.vr * dt;
                        if (p.y > H + 20) ps[i] = nueva(false);
                        petalo(p);
                    } else if (tipo === 'destellos') {
                        p.fase += p.v * dt; p.y += p.vy * dt; p.x += Math.sin(p.fase) * .15 * dt;
                        if (p.y < -10) ps[i] = nueva(false);
                        ctx.globalAlpha = .35 + .65 * Math.abs(Math.sin(p.fase));
                        ctx.shadowColor = acento; ctx.shadowBlur = 8;
                        ctx.fillStyle = acento;
                        ctx.beginPath(); ctx.arc(p.x, p.y, p.s, 0, 6.283); ctx.fill();
                    } else {
                        p.fase += p.v * dt;
                        var a = .2 + .8 * Math.abs(Math.sin(p.fase));
                        ctx.globalAlpha = a; ctx.shadowBlur = 0; ctx.fillStyle = '#ffffff';
                        ctx.beginPath(); ctx.arc(p.x, p.y, p.s, 0, 6.283); ctx.fill();
                        if (p.brillo) {
                            ctx.strokeStyle = 'rgba(255,255,255,' + (a * .8) + ')'; ctx.lineWidth = .8;
                            ctx.beginPath(); ctx.moveTo(p.x - p.s * 5, p.y); ctx.lineTo(p.x + p.s * 5, p.y); ctx.moveTo(p.x, p.y - p.s * 5); ctx.lineTo(p.x, p.y + p.s * 5); ctx.stroke();
                        }
                    }
                }
                ctx.globalAlpha = 1; ctx.shadowBlur = 0;
            }
            requestAnimationFrame(cuadro);
        }
        requestAnimationFrame(cuadro);
        return function () { vivo = false; };
    }

    // ===== Textos con formato =====
    var DIAS = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    var MESES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    Obi.fecha = function (iso) {
        if (!iso) return '';
        var p = iso.split('-');
        var d = new Date(+p[0], +p[1] - 1, +p[2]);
        var t = DIAS[d.getDay()] + ' ' + d.getDate() + ' de ' + MESES[d.getMonth()] + ' de ' + d.getFullYear();
        return t.charAt(0).toUpperCase() + t.slice(1);
    };
    Obi.hora = function (hhmm) {
        if (!hhmm) return '';
        var p = hhmm.split(':'), h = +p[0], m = +p[1];
        var h12 = h % 12 || 12;
        return h12 + (m ? ':' + (m < 10 ? '0' : '') + m : '') + (h < 12 ? ' a. m.' : ' p. m.');
    };
    Obi.grupos = function (texto) {
        return String(texto || '').split(/\r?\n/).map(function (l) { return l.trim(); }).filter(Boolean).map(function (l) {
            var i = l.indexOf(':');
            return i === -1 ? [l, ''] : [l.slice(0, i).trim(), l.slice(i + 1).trim()];
        });
    };

    // ===== Vista previa en vivo: cambia los datos del fallecido =====
    Obi.datos = function (el, d) {
        function poner(clave, valor) {
            el.querySelectorAll('[data-o="' + clave + '"]').forEach(function (n) { n.textContent = valor || ''; });
            el.querySelectorAll('[data-fila="' + clave + '"]').forEach(function (n) { n.hidden = !valor; });
        }
        ['nombre', 'anios', 'frase', 'sala', 'fecha', 'hora', 'lugar', 'destino', 'titulo', 'pie', 'cita'].forEach(function (k) {
            if (k in d) poner(k, d[k]);
        });
        if ('invitan' in d) {
            var gs = Obi.grupos(d.invitan);
            el.querySelectorAll('[data-o="grupos"]').forEach(function (cont) { cont.innerHTML = gs.map(function (g) { return '<div class="obi-grupo"><b>' + esc(g[0]) + '</b>' + (g[1] ? '<span>' + esc(g[1]) + '</span>' : '') + '</div>'; }).join('');
            });
            el.querySelectorAll('[data-fila="invitan"]').forEach(function (n) { n.hidden = !gs.length; });
        }
        if ('foto' in d) el.querySelectorAll('.obi-foto').forEach(function (f) {
            {
                var img = f.querySelector('img'), sil = f.querySelector('svg');
                if (d.foto) {
                    if (!img) { img = document.createElement('img'); img.alt = ''; f.appendChild(img); }
                    img.src = d.foto; if (sil) sil.style.display = 'none';
                } else { if (img) img.remove(); if (sil) sil.style.display = ''; }
            }
        });
        if ('foto_fondo' in d || 'foto_forma' in d) {
            var fondo = d.foto_fondo || 'suave', forma = d.foto_forma || 'circulo';
            el.querySelectorAll('.obi-foto').forEach(function (fb) {
                fb.className = 'obi-foto f-' + fondo + ' forma-' + forma;
                fb.style.background = fondo === 'color' && d.foto_color ? d.foto_color : '';
            });
            // Recorte libre: también se quita el aro redondo de las tarjetas
            el.querySelectorAll('.ot-foto-marco').forEach(function (m) { m.classList.toggle('marco-libre', forma === 'libre'); });
        }
        // Ajuste fino de la foto: {r: grados, x: %, y: %, z: %}
        if (d.foto_ajuste) {
            var a = d.foto_ajuste;
            el.querySelectorAll('.obi-foto').forEach(function (fb) {
                fb.style.setProperty('--pr', (+a.r || 0) + 'deg');
                fb.style.setProperty('--px', (+a.x || 0) + '%');
                fb.style.setProperty('--py', (+a.y || 0) + '%');
                fb.style.setProperty('--pz', ((+a.z || 100) / 100) + '');
            });
        }
        Obi.ajustar(el);
    };

    // ===== Cambiar el logo en la vista previa =====
    Obi.logo = function (el, url, mostrar) {
        el.querySelectorAll('.obi-logo, .ot-logo').forEach(function (img) {
            img.src = url;
            if (img.hasAttribute('data-logo-vacio')) { img.removeAttribute('data-logo-vacio'); img.setAttribute('data-logo', ''); }
            img.hidden = !mostrar;
        });
        el.querySelectorAll('[data-empresa]').forEach(function (d) {
            d.removeAttribute('data-logo'); d.setAttribute('data-sin-logo', ''); d.hidden = mostrar;
        });
    };

    // ===== Quitar el fondo de un logo (blanco o de un color parejo) =====
    // Toma el color de las esquinas y borra desde los bordes todo lo que se le parezca; deja lo de adentro.
    Obi.quitarFondo = function (file) {
        return new Promise(function (ok, mal) {
            var im = new Image();
            im.onload = function () {
                var max = 1600, s = Math.min(1, max / Math.max(im.width, im.height));
                var W = Math.round(im.width * s), H = Math.round(im.height * s);
                var c = document.createElement('canvas'); c.width = W; c.height = H;
                var x = c.getContext('2d'); x.drawImage(im, 0, 0, W, H);
                var d = x.getImageData(0, 0, W, H), px = d.data;
                // color de fondo = promedio de las 4 esquinas
                var r = 0, g = 0, b = 0, esq = [0, W - 1, (H - 1) * W, H * W - 1];
                esq.forEach(function (i) { r += px[i * 4]; g += px[i * 4 + 1]; b += px[i * 4 + 2]; });
                r /= 4; g /= 4; b /= 4;
                var tol = 48, suave = 90;
                function dist(i) { var dr = px[i * 4] - r, dg = px[i * 4 + 1] - g, db = px[i * 4 + 2] - b; return Math.sqrt(dr * dr + dg * dg + db * db); }
                var visto = new Uint8Array(W * H), pila = [];
                for (var i = 0; i < W; i++) { pila.push(i, (H - 1) * W + i); }
                for (var j = 0; j < H; j++) { pila.push(j * W, j * W + W - 1); }
                while (pila.length) {
                    var k = pila.pop();
                    if (visto[k]) continue;
                    visto[k] = 1;
                    var dd = dist(k);
                    if (dd > suave) continue;
                    // cerca del color de fondo: transparente; en el borde del dibujo: semitransparente
                    px[k * 4 + 3] = dd <= tol ? 0 : Math.min(px[k * 4 + 3], Math.round(255 * (dd - tol) / (suave - tol)));
                    if (dd > tol) continue;
                    var xx = k % W, yy = (k - xx) / W;
                    if (xx > 0) pila.push(k - 1);
                    if (xx < W - 1) pila.push(k + 1);
                    if (yy > 0) pila.push(k - W);
                    if (yy < H - 1) pila.push(k + W);
                }
                x.putImageData(d, 0, 0);
                c.toBlob(function (bl) { bl ? ok(bl) : mal(new Error('sin imagen')); }, 'image/png');
            };
            im.onerror = mal;
            im.src = URL.createObjectURL(file);
        });
    };

    // ===== ¿La imagen tiene partes transparentes (PNG sin fondo)? =====
    Obi.tieneTransparencia = function (url) {
        return new Promise(function (ok) {
            var im = new Image();
            im.onload = function () {
                try {
                    var c = document.createElement('canvas'), n = 64;
                    c.width = n; c.height = n;
                    var x = c.getContext('2d'); x.drawImage(im, 0, 0, n, n);
                    var px = x.getImageData(0, 0, n, n).data, vacios = 0;
                    for (var i = 3; i < px.length; i += 4) if (px[i] < 200) vacios++;
                    ok(vacios > n * n * 0.03);
                } catch (e) { ok(false); }
            };
            im.onerror = function () { ok(false); };
            im.src = url;
        });
    };

    // ===== Vista previa en vivo: cambia el diseño de la plantilla =====
    Obi.estilo = function (el, c) {
        el.className = 'obi d-' + c.diseno + ' o-' + c.o + ' adornos-' + (c.adornos || 'muchos') + (c.tarjeta ? ' lay-tarjeta' : '') + ' flor-' + ((c.flores && c.flores.pos) || 'abajo') + ' esq-' + ((c.flores && c.flores.esq) || 'dos') + (c.fondo_tipo !== 'diseno' ? ' fondo-propio' : '');
        el.dataset.w = c.w; el.dataset.h = c.h; el.dataset.escala = c.escala;
        el.style.width = c.w + 'px'; el.style.height = c.h + 'px';
        el.style.setProperty('--acento', c.acento);
        el.style.setProperty('--texto', c.texto);
        ['--t-acento', '--t-texto', '--t-titulo'].forEach(function (v) { el.style.removeProperty(v); });
        if (c.propios) {
            el.style.setProperty('--t-acento', c.acento); el.style.setProperty('--t-texto', c.texto); el.style.setProperty('--t-titulo', c.texto);
        }
        if (c.flores) {
            el.style.setProperty('--fx', (+c.flores.x || 0) + 'px');
            el.style.setProperty('--fy', (+c.flores.y || 0) + 'px');
            el.style.setProperty('--ft', ((+c.flores.tam || 100) / 100).toString());
        }
        var fondo = el.querySelector('.obi-fondo');
        fondo.style.background = '';
        var v = fondo.querySelector('video');
        if (c.fondo_tipo === 'color' && c.color1) {
            fondo.style.background = c.color2 ? 'linear-gradient(160deg,' + c.color1 + ',' + c.color2 + ')' : c.color1;
        } else if (c.fondo_tipo === 'imagen' && c.imagen) {
            fondo.style.background = "url('" + c.imagen + "') center/cover no-repeat";
        }
        if (c.fondo_tipo === 'video' && c.video) {
            if (!v) { v = document.createElement('video'); v.muted = true; v.loop = true; v.autoplay = true; v.playsInline = true; v.setAttribute('muted', ''); fondo.appendChild(v); }
            if (v.getAttribute('src') !== c.video) { v.src = c.video; v.play().catch(function () {}); }
        } else if (v) { v.remove(); }
        el.querySelector('.obi-velo').style.opacity = (c.fondo_tipo === 'imagen' || c.fondo_tipo === 'video') ? (c.oscurecer / 100) : 0;
        el.querySelectorAll('[data-logo]').forEach(function (n) { n.hidden = !c.logo; });
        el.querySelectorAll('[data-sin-logo]').forEach(function (n) { n.hidden = c.logo; });
        Obi.datos(el, { titulo: c.titulo, frase: c.frase, pie: c.pie, cita: c.cita ? '“' + c.cita.replace(/^[“"]+|[”"]+$/g, '') + '”' : '' });
        var clave = [c.anim, c.anim_cant, c.anim_tam, c.anim_vel].join('|');
        if (el.dataset.animClave !== clave || c.reanimar) {
            el.dataset.animClave = clave; el.dataset.anim = c.anim;
            el.dataset.animCant = c.anim_cant || 100; el.dataset.animTam = c.anim_tam || 100; el.dataset.animVel = c.anim_vel || 100;
            Obi.animar(el);
        }
        Obi.escalar(el);
        Obi.ajustar(el);
    };

    // ===== Descargar la imagen en la medida exacta de la pantalla (4K, Full HD…) =====
    function cargarLib() {
        if (window.htmlToImage) return Promise.resolve();
        return new Promise(function (ok, mal) {
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/html-to-image@1.11.11/dist/html-to-image.js';
            s.onload = ok; s.onerror = mal;
            document.head.appendChild(s);
        });
    }
    var GIF = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    function bajar(url, nombre) {
        var a = document.createElement('a');
        a.href = url; a.download = nombre;
        document.body.appendChild(a); a.click(); a.remove();
        if (url.indexOf('blob:') === 0) setTimeout(function () { URL.revokeObjectURL(url); }, 60000);
    }
    function opciones(el, escala, extra) {
        var o = {
            width: +el.dataset.w, height: +el.dataset.h, pixelRatio: escala, cacheBust: true, imagePlaceholder: GIF,
            style: { transform: 'none', left: '0', top: '0', margin: '0' }
        };
        for (var k in extra) o[k] = extra[k];
        return o;
    }

    // ===== Descargar imagen (PNG o JPG) en la medida exacta de la pantalla (4K, Full HD…) =====
    Obi.exportar = function (el, nombre, formato) {
        formato = formato === 'jpg' ? 'jpg' : 'png';
        return Obi.imagen(el, formato).then(function (url) { bajar(url, (nombre || 'obituario') + '.' + formato); });
    };

    // ===== Crear la imagen (devuelve el enlace data:) sin descargarla; la usa también "Enviar por WhatsApp" =====
    Obi.imagen = function (el, formato, escalaFija) {
        var escala = escalaFija || +el.dataset.escala || 1, cambios = [];
        formato = formato === 'jpg' ? 'jpg' : 'png';
        return cargarLib().then(function () {
            // El video no sale en la foto: se pone el cuadro que se está viendo
            el.querySelectorAll('video').forEach(function (v) {
                var c = document.createElement('canvas');
                c.width = v.videoWidth || 1280; c.height = v.videoHeight || 720;
                try { c.getContext('2d').drawImage(v, 0, 0, c.width, c.height); } catch (e) {}
                c.style.cssText = 'width:100%;height:100%;object-fit:cover;display:block';
                v.parentNode.insertBefore(c, v); v.style.display = 'none';
                cambios.push([v, c]);
            });
            return document.fonts ? document.fonts.ready : null;
        }).then(function () {
            return formato === 'jpg'
                ? window.htmlToImage.toJpeg(el, opciones(el, escala, { quality: 0.93, backgroundColor: '#ffffff' }))
                : window.htmlToImage.toPng(el, opciones(el, escala, {}));
        }).finally(function () {
            cambios.forEach(function (x) { x[1].remove(); x[0].style.display = ''; });
        });
    };

    // ===== Enviar por WhatsApp CON la imagen =====
    // Celular (y Chrome/Edge en Windows): abre el menú de compartir con la imagen adjunta -> elegir WhatsApp.
    // Si el navegador no deja compartir archivos: descarga la imagen, copia el texto y abre WhatsApp para adjuntarla.
    // La imagen se prepara antes (al abrir la página), porque el navegador solo deja compartir justo después del toque.
    Obi.whatsapp = function (el, boton, op) {
        var archivo = null, preparando = null;
        var nombre = (op.nombre || 'obituario') + '.jpg';
        var ancho = +el.dataset.w || 1920;
        function preparar() {
            if (archivo) return Promise.resolve(archivo);
            if (preparando) return preparando;
            // Full HD basta para WhatsApp (lo comprime igual) y pesa poco
            preparando = Obi.imagen(el, 'jpg', Math.min(1, 1920 / ancho) || 1)
                .then(function (url) { return fetch(url); }).then(function (r) { return r.blob(); })
                .then(function (b) { archivo = new File([b], nombre, { type: 'image/jpeg' }); return archivo; })
                .finally(function () { preparando = null; });
            return preparando;
        }
        function puedeCompartir(f) {
            try { return !!(navigator.canShare && navigator.share && navigator.canShare({ files: [f] })); } catch (e) { return false; }
        }
        function respaldo(f) {
            bajar(URL.createObjectURL(f), nombre);
            try { navigator.clipboard && navigator.clipboard.writeText(op.texto); } catch (e) {}
            window.open('https://wa.me/?text=' + encodeURIComponent(op.texto), '_blank');
            if (window.LP && LP.toast) LP.toast('La imagen se descargó. En WhatsApp toque el clip y adjúntela (el texto ya va escrito).', 'info');
        }
        function compartir(f) {
            if (!puedeCompartir(f)) { respaldo(f); return; }
            navigator.share({ files: [f], text: op.texto }).catch(function (e) {
                if (e && e.name === 'AbortError') return; // el usuario cerró el menú
                // se pasó el tiempo del toque mientras se creaba la imagen: pedir otro toque
                boton.innerHTML = '<i class="fa-brands fa-whatsapp me-1"></i>Listo: toque de nuevo para enviar';
            });
        }
        var txt = boton.innerHTML;
        boton.addEventListener('click', function () {
            if (archivo) { boton.innerHTML = txt; compartir(archivo); return; }
            boton.disabled = true; boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Preparando imagen…';
            preparar().then(function (f) { boton.innerHTML = txt; compartir(f); })
                .catch(function () {
                    boton.innerHTML = txt;
                    window.open('https://wa.me/?text=' + encodeURIComponent(op.texto), '_blank');
                    if (window.LP && LP.toast) LP.toast('No se pudo crear la imagen; se envía solo el texto. Descargue la imagen y adjúntela.', 'error');
                })
                .finally(function () { boton.disabled = false; });
        });
        // Prepararla apenas cargue la página para que el envío sea inmediato
        var listo = function () { setTimeout(function () { preparar().catch(function () {}); }, 1500); };
        if (document.fonts) document.fonts.ready.then(listo); else listo();
    };

    // ===== Formatos de video que este navegador puede grabar =====
    Obi.formatosVideo = function () {
        var lista = [
            ['mp4', 'MP4 (WhatsApp, celulares, televisores)', ['video/mp4;codecs=avc1.42E01F', 'video/mp4;codecs=avc1', 'video/mp4']],
            ['webm', 'WebM (navegadores, YouTube)', ['video/webm;codecs=vp9', 'video/webm;codecs=vp8', 'video/webm']]
        ], out = [];
        if (!window.MediaRecorder || !HTMLCanvasElement.prototype.captureStream) return out;
        lista.forEach(function (f) {
            for (var i = 0; i < f[2].length; i++) {
                if (MediaRecorder.isTypeSupported(f[2][i])) { out.push({ ext: f[0], nombre: f[1], mime: f[2][i] }); break; }
            }
        });
        return out;
    };

    // ===== Descargar video: graba el obituario con su animación y su video de fondo =====
    // El dibujo fijo se toma una vez; en cada cuadro se pinta el video de fondo, el dibujo y la animación encima.
    Obi.video = function (el, op) {
        op = op || {};
        var fmt = op.formato, seg = op.segundos || 10, fps = 30;
        var escala = +el.dataset.escala || 1;
        // Para que el navegador no se ahogue, el video sale máximo en Full HD
        var W = Math.round(+el.dataset.w * escala), H = Math.round(+el.dataset.h * escala);
        if (Math.max(W, H) > 1920) { var r = 1920 / Math.max(W, H); escala *= r; W = Math.round(W * r); H = Math.round(H * r); }
        W -= W % 2; H -= H % 2;
        var capa = el.querySelector('[data-anim-capa]'), vid = el.querySelector('.obi-fondo video'), fondoAntes = el.style.background;
        var estatico;
        return cargarLib().then(function () {
            return document.fonts ? document.fonts.ready : null;
        }).then(function () {
            if (capa) capa.style.visibility = 'hidden';
            if (vid) { vid.style.visibility = 'hidden'; el.style.background = 'transparent'; }
            return window.htmlToImage.toCanvas(el, opciones(el, escala, { canvasWidth: W, canvasHeight: H, pixelRatio: 1 }));
        }).then(function (c) {
            estatico = c;
        }).finally(function () {
            if (capa) capa.style.visibility = '';
            if (vid) vid.style.visibility = '';
            el.style.background = fondoAntes;
        }).then(function () {
            return new Promise(function (ok, mal) {
                var out = document.createElement('canvas'); out.width = W; out.height = H;
                var ctx = out.getContext('2d');
                var particulas = capa && capa.querySelector('canvas');
                var stream = out.captureStream(fps), trozos = [];
                var rec;
                try { rec = new MediaRecorder(stream, { mimeType: fmt.mime, videoBitsPerSecond: 8000000 }); } catch (e) { mal(e); return; }
                rec.ondataavailable = function (e) { if (e.data && e.data.size) trozos.push(e.data); };
                rec.onstop = function () { ok(new Blob(trozos, { type: fmt.mime.split(';')[0] })); };
                if (vid) { try { vid.currentTime = 0; vid.play(); } catch (e) {} }
                var t0 = null, fin = false;
                function cuadro(t) {
                    if (t0 === null) t0 = t;
                    var pas = (t - t0) / 1000;
                    ctx.clearRect(0, 0, W, H);
                    if (vid && vid.readyState >= 2) {
                        // video de fondo cubriendo todo
                        var vw = vid.videoWidth, vh = vid.videoHeight, s = Math.max(W / vw, H / vh);
                        ctx.drawImage(vid, (W - vw * s) / 2, (H - vh * s) / 2, vw * s, vh * s);
                    } else if (vid) { ctx.fillStyle = '#000'; ctx.fillRect(0, 0, W, H); }
                    ctx.drawImage(estatico, 0, 0, W, H);
                    if (particulas) ctx.drawImage(particulas, 0, 0, W, H);
                    if (op.progreso) op.progreso(Math.min(pas / seg, 1));
                    // medio segundo de más: algunos navegadores pierden el primer trozo y el video quedaba corto (9 s en vez de 10)
                    if (pas >= seg + 0.5) { if (!fin) { fin = true; try { rec.requestData(); } catch (e) {} rec.stop(); } return; }
                    requestAnimationFrame(cuadro);
                }
                rec.onstart = function () { requestAnimationFrame(cuadro); };
                rec.start(500);
            });
        }).then(function (blob) {
            bajar(URL.createObjectURL(blob), (op.nombre || 'obituario') + '.' + fmt.ext);
        });
    };

    // ===== Pantalla del televisor: rota obituarios, se actualiza sola y no deja apagar la pantalla =====
    Obi.pantalla = function (op) {
        var slides = [].slice.call(document.querySelectorAll('[data-obi-slide]')), i = 0;
        function mostrar(n) {
            slides.forEach(function (s, k) { s.classList.toggle('on', k === n); });
            var el = slides[n] && slides[n].querySelector('[data-obi]');
            if (el) { Obi.escalar(el); Obi.ajustar(el); var v = el.querySelector('video'); if (v) v.play().catch(function () {}); }
        }
        if (slides.length) mostrar(0);
        if (slides.length > 1) setInterval(function () { i = (i + 1) % slides.length; mostrar(i); }, (op.rotar || 12) * 1000);

        if (op.version) {
            setInterval(function () {
                fetch(op.version, { cache: 'no-store' }).then(function (r) { return r.ok ? r.json() : null; }).then(function (j) {
                    if (j && j.v && j.v !== op.actual) location.reload();
                }).catch(function () {});
            }, 30000);
        }
        // Que el televisor no se duerma
        var lock = null;
        function pedir() { if (navigator.wakeLock && document.visibilityState === 'visible') navigator.wakeLock.request('screen').then(function (l) { lock = l; }).catch(function () {}); }
        pedir();
        document.addEventListener('visibilitychange', pedir);
        setTimeout(function () { document.body.classList.add('sin-cursor'); }, 8000);
        // Ocultar el cursor
        var tm;
        document.addEventListener('mousemove', function () { document.body.classList.remove('sin-cursor'); clearTimeout(tm); tm = setTimeout(function () { document.body.classList.add('sin-cursor'); }, 2500); });
        // Pantalla completa con un clic
        document.addEventListener('click', function () {
            var d = document.documentElement;
            if (!document.fullscreenElement && d.requestFullscreen) d.requestFullscreen().catch(function () {});
        });
        // Por si se cae el internet o algo falla: recargar cada 6 horas
        setTimeout(function () { location.reload(); }, 6 * 3600 * 1000);
    };

    // ===== Arranque =====
    function iniciar() {
        todos().forEach(function (el) {
            Obi.escalar(el);
            Obi.animar(el);
            Obi.ajustar(el);
            if (window.ResizeObserver && el.parentElement) new ResizeObserver(function () { Obi.escalar(el); }).observe(el.parentElement);
        });
        if (document.fonts) document.fonts.ready.then(function () { todos().forEach(Obi.ajustar); });
        window.addEventListener('resize', function () { todos().forEach(Obi.escalar); });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', iniciar); else iniciar();
})();
