/* Odontograma de LocalProo (notación FDI). Rojo = por tratar, azul = hecho / existente.
   Odonto.montar(contenedor, { piezas, denticion, hallazgos, soloVer, alCambiar, alElegir }) */
(function () {
  var NS = 'http://www.w3.org/2000/svg';
  var PERM = { sup: ['18','17','16','15','14','13','12','11','21','22','23','24','25','26','27','28'], inf: ['48','47','46','45','44','43','42','41','31','32','33','34','35','36','37','38'] };
  var TEMP = { sup: ['55','54','53','52','51','61','62','63','64','65'], inf: ['85','84','83','82','81','71','72','73','74','75'] };
  var T = 40, G = 7, MITAD = 16; // tamaño de la pieza, espacio entre piezas y espacio en la línea media

  function el(tag, attrs, padre) {
    var e = document.createElementNS(NS, tag);
    for (var k in attrs) e.setAttribute(k, attrs[k]);
    if (padre) padre.appendChild(e);
    return e;
  }

  // Qué cara queda arriba, abajo, a la izquierda y a la derecha según la pieza
  function caras(num, arriba) {
    var cuad = +num.charAt(0);
    var derechaPaciente = [1, 4, 5, 8].indexOf(cuad) !== -1; // se dibuja a la izquierda: mesial queda a la derecha
    return {
      top: arriba ? 'V' : 'L', bottom: arriba ? 'L' : 'V',
      left: derechaPaciente ? 'D' : 'M', right: derechaPaciente ? 'M' : 'D', center: 'O'
    };
  }

  var POL = {
    top: '0,0 40,0 28,12 12,12', bottom: '12,28 28,28 40,40 0,40',
    left: '0,0 12,12 12,28 0,40', right: '40,0 40,40 28,28 28,12'
  };

  function montar(cont, op) {
    var H = op.hallazgos || {};
    var datos = JSON.parse(JSON.stringify(op.piezas || {}));
    if (Array.isArray(datos)) datos = {};
    var herramienta = null, elegida = null, denticion = op.denticion || 'permanente';

    function filas() {
      var f = [];
      if (denticion !== 'temporal') f.push({ piezas: PERM.sup, arriba: true, num: 'arriba' });
      if (denticion !== 'permanente') { f.push({ piezas: TEMP.sup, arriba: true, num: 'arriba' }); f.push({ piezas: TEMP.inf, arriba: false, num: 'abajo' }); }
      if (denticion !== 'temporal') f.push({ piezas: PERM.inf, arriba: false, num: 'abajo' });
      return f;
    }

    function dibujar() {
      cont.innerHTML = '';
      var ancho = 16 * (T + G) - G + MITAD, alto = 0, fs = filas();
      var svg = el('svg', { viewBox: '0 0 ' + (ancho + 20) + ' ' + (fs.length * 92 + (fs.length > 2 ? 18 : 0)), class: 'odo-svg', role: 'img', 'aria-label': 'Odontograma' }, cont);
      var y = 8;
      fs.forEach(function (fila, i) {
        if (i === 2 && fs.length > 2) y += 18; // separación entre temporales y permanentes inferiores
        var n = fila.piezas.length, total = n * (T + G) - G + MITAD;
        var x0 = 10 + (ancho - total) / 2;
        fila.piezas.forEach(function (num, j) {
          var x = x0 + j * (T + G) + (j >= n / 2 ? MITAD : 0);
          var yy = y + (fila.num === 'arriba' ? 20 : 0);
          pieza(svg, num, x, yy, fila.arriba);
          el('text', { x: x + T / 2, y: fila.num === 'arriba' ? y + 12 : y + T + 34, 'text-anchor': 'middle', class: 'odo-num' + (elegida === num ? ' on' : '') }, svg).textContent = num;
        });
        // línea media
        el('line', { x1: 10 + ancho / 2, y1: y + 14, x2: 10 + ancho / 2, y2: y + T + 26, class: 'odo-medio' }, svg);
        y += 92;
      });
      leyenda();
    }

    function pieza(svg, num, x, y, arriba) {
      var d = datos[num] || {}, cs = caras(num, arriba), hp = d.pieza ? H[d.pieza] : null;
      var g = el('g', { transform: 'translate(' + x + ',' + y + ')', class: 'odo-pieza' + (elegida === num ? ' elegida' : '') + (hp && (d.pieza === 'ausente' || d.pieza === 'sin_erupcionar') ? ' tenue' : ''), 'data-pieza': num }, svg);
      ['top', 'bottom', 'left', 'right'].forEach(function (pos) {
        var cara = cs[pos], h = d.caras && d.caras[cara];
        var p = el('polygon', { points: POL[pos], class: 'odo-cara', 'data-cara': cara, fill: h && H[h] ? H[h][1] : '#fff' }, g);
        if (h && H[h]) p.setAttribute('fill-opacity', '.9');
        el('title', {}, p).textContent = num + ' · ' + (CARA_NOMBRE[cara] || cara) + (h && H[h] ? ': ' + H[h][0] : '');
      });
      var hc = d.caras && d.caras.O;
      var c = el('rect', { x: 12, y: 12, width: 16, height: 16, class: 'odo-cara', 'data-cara': 'O', fill: hc && H[hc] ? H[hc][1] : '#fff' }, g);
      el('title', {}, c).textContent = num + ' · Oclusal / incisal' + (hc && H[hc] ? ': ' + H[hc][0] : '');
      if (hp) {
        var col = hp[1], sim = hp[4];
        if (sim === 'X') {
          el('line', { x1: -3, y1: -3, x2: 43, y2: 43, stroke: col, 'stroke-width': 4, 'stroke-linecap': 'round', 'pointer-events': 'none' }, g);
          el('line', { x1: 43, y1: -3, x2: -3, y2: 43, stroke: col, 'stroke-width': 4, 'stroke-linecap': 'round', 'pointer-events': 'none' }, g);
        } else if (sim === 'C') {
          el('circle', { cx: 20, cy: 20, r: 25, fill: 'none', stroke: col, 'stroke-width': 3.5, 'pointer-events': 'none' }, g);
        } else if (sim) {
          var by = 46;
          el('rect', { x: 8, y: by, width: 24, height: 14, rx: 7, fill: col, 'pointer-events': 'none' }, g);
          el('text', { x: 20, y: by + 10.5, 'text-anchor': 'middle', class: 'odo-sim', 'pointer-events': 'none' }, g).textContent = sim;
        }
      }
      if (d.nota) el('circle', { cx: 40, cy: 0, r: 4.5, fill: '#f59e0b', stroke: '#fff', 'stroke-width': 1.5, 'pointer-events': 'none' }, g);
      if (!op.soloVer) g.addEventListener('click', function (e) { tocar(num, e.target.getAttribute('data-cara')); });
    }

    var CARA_NOMBRE = { V: 'Vestibular', L: 'Lingual / palatina', M: 'Mesial', D: 'Distal', O: 'Oclusal / incisal' };

    function tocar(num, cara) {
      elegida = num;
      if (herramienta) {
        var d = datos[num] || {};
        if (herramienta === 'borrar') {
          if (cara && d.caras && d.caras[cara]) delete d.caras[cara]; else { delete d.pieza; if (!cara) d.caras = {}; }
        } else if (H[herramienta] && H[herramienta][2] === 'pieza') {
          d.pieza = d.pieza === herramienta ? undefined : herramienta;
          if (!d.pieza) delete d.pieza;
        } else if (H[herramienta] && cara) {
          d.caras = d.caras || {};
          if (d.caras[cara] === herramienta) delete d.caras[cara]; else d.caras[cara] = herramienta;
        }
        if (d.caras && !Object.keys(d.caras).length) delete d.caras;
        if (Object.keys(d).length) datos[num] = d; else delete datos[num];
        cambio();
      }
      dibujar();
      if (op.alElegir) op.alElegir(num, datos[num] || {});
    }

    function cambio() { if (op.alCambiar) op.alCambiar(datos, resumen()); }

    function resumen() {
      var out = [];
      Object.keys(datos).sort(function (a, b) { return a - b; }).forEach(function (num) {
        var d = datos[num], partes = [];
        if (d.pieza && H[d.pieza]) partes.push({ t: H[d.pieza][0], c: H[d.pieza][1] });
        var grupos = {};
        for (var k in (d.caras || {})) (grupos[d.caras[k]] = grupos[d.caras[k]] || []).push(k);
        for (var h in grupos) if (H[h]) partes.push({ t: H[h][0] + ' ' + grupos[h].join(', '), c: H[h][1] });
        if (d.nota) partes.push({ t: '“' + d.nota + '”', c: '#92400e' });
        if (partes.length) out.push({ pieza: num, partes: partes });
      });
      return out;
    }

    function leyenda() {
      if (!op.leyenda) return;
      var l = op.leyenda, r = resumen();
      l.innerHTML = r.length ? '' : '<div class="odo-vacio">Sin hallazgos. Todas las piezas sanas.</div>';
      r.forEach(function (x) {
        var div = document.createElement('div'); div.className = 'odo-res';
        var b = document.createElement('b'); b.textContent = x.pieza; div.appendChild(b);
        var s = document.createElement('span');
        x.partes.forEach(function (p) { var i = document.createElement('i'); i.style.background = p.c; i.textContent = p.t; s.appendChild(i); });
        div.appendChild(s); l.appendChild(div);
      });
    }

    dibujar();
    return {
      herramienta: function (h) { herramienta = h; },
      denticion: function (d) { denticion = d; dibujar(); },
      nota: function (num, txt) {
        var d = datos[num] || {};
        txt = (txt || '').trim().slice(0, 160);
        if (txt) d.nota = txt; else delete d.nota;
        if (Object.keys(d).length) datos[num] = d; else delete datos[num];
        cambio(); dibujar();
      },
      limpiarPieza: function (num) { delete datos[num]; cambio(); dibujar(); },
      datos: function () { return datos; },
      resumen: resumen
    };
  }

  window.Odonto = { montar: montar };
})();
