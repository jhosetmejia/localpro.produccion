/* LocalProCut · motor de render (WebGL2): encuadre, filtro de belleza y transiciones.
   Cada clip activo se dibuja en su propio buffer (con belleza) y luego se mezclan con la transición. */

const VERT = `#version 300 es
in vec2 aPos;
out vec2 vUv;
void main(){ vUv = aPos * 0.5 + 0.5; gl_Position = vec4(aPos, 0.0, 1.0); }`;

// Pasada del clip: encuadre (llenar o encajar con fondo), belleza (suavizado bilateral solo en piel del rostro),
// ajustes de color, filtro y efecto del clip
const FRAG_CLIP = `#version 300 es
precision highp float;
in vec2 vUv;
uniform sampler2D uSrc;
uniform sampler2D uMask;
uniform vec2 uScale;
uniform vec2 uScaleCov;
uniform vec2 uPan;
uniform vec2 uStep;
uniform float uBeauty, uHasMask, uSuave, uAclara, uBrillo, uHasTono;
uniform vec3 uTono;
uniform float uFit;
uniform vec3 uBg;
uniform vec4 uAdj;
uniform float uVig;
uniform int uFil;
uniform float uFilK;
uniform int uEf;
uniform float uT;
uniform vec2 uRes;
out vec4 o;
float piel(vec3 c){
  float y = dot(c, vec3(0.299, 0.587, 0.114));
  float cb = (c.b - y) * 0.564 + 0.5;
  float cr = (c.r - y) * 0.713 + 0.5;
  return smoothstep(0.27, 0.32, cb) * (1.0 - smoothstep(0.50, 0.54, cb)) * smoothstep(0.50, 0.535, cr) * (1.0 - smoothstep(0.68, 0.72, cr));
}
float luz(vec3 c){ return dot(c, vec3(0.299, 0.587, 0.114)); }
vec3 sat(vec3 c, float s){ return mix(vec3(luz(c)), c, s); }
vec3 sepia(vec3 c){ return clamp(vec3(dot(c, vec3(0.393, 0.769, 0.189)), dot(c, vec3(0.349, 0.686, 0.168)), dot(c, vec3(0.272, 0.534, 0.131))), 0.0, 1.0); }
float hash(vec2 p){ return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453); }
void main(){
  vec2 su = vec2(vUv.x, 1.0 - vUv.y);
  // Efectos que mueven la imagen
  if (uEf == 6) { float g = step(0.55, fract(uT * 1.6)); su += vec2(sin(uT * 47.0), cos(uT * 39.0)) * 0.014 * g; }
  if (uEf == 7) { float b = pow(1.0 - fract(uT * 2.0), 3.0); su = 0.5 + (su - 0.5) / (1.0 + 0.09 * b); }
  if (uEf == 8) { su.x = su.x < 0.5 ? su.x : 1.0 - su.x; }
  vec2 uv = 0.5 + (su - 0.5) * uScale + uPan;
  vec3 c;
  bool fuera = uv.x < 0.0 || uv.x > 1.0 || uv.y < 0.0 || uv.y > 1.0;
  if (fuera) {
    if (uFit > 1.5) c = uBg;
    else {
      vec2 ub = 0.5 + (su - 0.5) * uScaleCov;
      vec3 s = vec3(0.0);
      for (int i = -2; i <= 2; i++) for (int j = -2; j <= 2; j++) s += texture(uSrc, ub + vec2(float(i), float(j)) * 0.014).rgb;
      c = s / 25.0 * 0.62;
    }
  } else {
    c = uEf == 9 ? vec3(texture(uSrc, uv + vec2(0.007, 0.0)).r, texture(uSrc, uv).g, texture(uSrc, uv - vec2(0.007, 0.0)).b) : texture(uSrc, uv).rgb;
    if (uBeauty > 0.5) {
      float m = uHasMask > 0.5 ? texture(uMask, uv).r : 0.85;
      float sk = piel(c);
      float w = uHasMask > 0.5 ? m * (0.35 + 0.65 * sk) : sk * 0.8;
      if (w > 0.01) {
        vec3 acc = vec3(0.0); float ws = 0.0;
        float sig = 0.010 + 0.035 * uSuave;
        for (int i = -3; i <= 3; i++) {
          for (int j = -3; j <= 3; j++) {
            vec3 s = texture(uSrc, uv + vec2(float(i), float(j)) * uStep).rgb;
            vec3 d = s - c;
            float k = exp(-float(i * i + j * j) / 9.0) * exp(-dot(d, d) / sig);
            acc += s * k; ws += k;
          }
        }
        vec3 sm = acc / ws;
        float yS = luz(sm);
        // Igualar el tono: lleva la piel hacia el color promedio de la propia persona (quita manchas y rojeces sin cambiarle el color)
        if (uHasTono > 0.5) {
          float yT = max(0.04, luz(uTono));
          vec3 igual = clamp(uTono * (yS / yT), 0.0, 1.0);
          sm = mix(sm, igual, 0.35 * uSuave);
        }
        c = mix(c, sm, clamp(w * (0.2 + 0.7 * uSuave), 0.0, 1.0) * step(0.001, uSuave));
        // Iluminar dentro del mismo tono (sube la luz sin mezclar con blanco)
        float y0 = max(0.02, luz(c));
        float y1 = y0 + (1.0 - y0) * 0.14 * uAclara;
        c = mix(c, clamp(c * (y1 / y0), 0.0, 1.0), w);
      }
      // Brillo general muy suave (un toque de luz, sin velo blanco)
      float yg = max(0.02, luz(c));
      c = clamp(c * (1.0 + 0.10 * uBrillo * (1.0 - yg)), 0.0, 1.0);
    }
  }
  // Ajustar: brillo, contraste, saturación y temperatura
  c = clamp(c + uAdj.x * 0.22, 0.0, 1.0);
  c = clamp((c - 0.5) * (1.0 + uAdj.y * 0.6) + 0.5, 0.0, 1.0);
  c = clamp(sat(c, 1.0 + uAdj.z), 0.0, 1.0);
  c = clamp(c + vec3(0.07, 0.015, -0.07) * uAdj.w, 0.0, 1.0);
  // Filtros de color
  if (uFil > 0) {
    vec3 f = c;
    float y = luz(c);
    if (uFil == 1) f = sat((c - 0.5) * 1.12 + 0.5, 1.4);
    else if (uFil == 2) f = c * vec3(1.08, 1.0, 0.86) + vec3(0.03, 0.012, 0.0);
    else if (uFil == 3) f = c * vec3(0.9, 1.0, 1.12) + vec3(0.0, 0.01, 0.02);
    else if (uFil == 4) { vec3 tono = mix(vec3(0.05, 0.35, 0.42), vec3(1.0, 0.62, 0.32), smoothstep(0.1, 0.9, y)); f = sat(mix(c, tono * (0.35 + y), 0.3), 0.92); f = (f - 0.5) * 1.12 + 0.5; }
    else if (uFil == 5) f = c * vec3(1.14, 0.97, 0.8) + vec3(0.04, 0.01, 0.0);
    else if (uFil == 6) f = sat(c, 0.68) * 0.88 + 0.1;
    else if (uFil == 7) f = vec3(clamp((y - 0.5) * 1.4 + 0.5, 0.0, 1.0));
    else if (uFil == 8) f = mix(c, sepia(c), 0.55) * 0.9 + 0.06;
    c = clamp(mix(c, f, uFilK), 0.0, 1.0);
  }
  // Efectos de color del clip
  if (uEf == 1) c = vec3(luz(c));
  else if (uEf == 2) c = sepia(c);
  else if (uEf == 3) {
    float g = hash(floor(vUv * uRes / 2.0) + floor(uT * 24.0)) - 0.5;
    c = mix(c, sepia(c), 0.35) * 0.92 + 0.05 + g * 0.11;
    c *= 0.94 + 0.06 * sin(vUv.y * uRes.y * 1.6);
  }
  else if (uEf == 5) c = mix(c, vec3(1.0), pow(1.0 - fract(uT * 1.5), 6.0) * 0.55);
  // Viñeta (ajuste general + efecto)
  float vig = clamp(uVig + (uEf == 4 ? 0.85 : 0.0) + (uEf == 3 ? 0.35 : 0.0), 0.0, 1.0);
  if (vig > 0.001) { vec2 d = vUv - 0.5; c *= mix(1.0, smoothstep(0.95, 0.2, length(d) * 1.25), vig); }
  o = vec4(clamp(c, 0.0, 1.0), 1.0);
}`;

// Pasada de mezcla: transiciones clásicas entre el clip A (sale) y B (entra)
const FRAG_MIX = `#version 300 es
precision highp float;
in vec2 vUv;
uniform sampler2D uA;
uniform sampler2D uB;
uniform float uP;
uniform int uT;
uniform float uAsp;
out vec4 o;
vec2 mir(vec2 u){ return 1.0 - abs(1.0 - mod(u, 2.0)); }
vec4 A(vec2 u){ return texture(uA, mir(u)); }
vec4 B(vec2 u){ return texture(uB, mir(u)); }
float ease(float p){ return p < 0.5 ? 4.0 * p * p * p : 1.0 - pow(-2.0 * p + 2.0, 3.0) / 2.0; }
vec4 blurA(vec2 u, float r){ vec4 s = vec4(0.0); for (int i = -2; i <= 2; i++) for (int j = -2; j <= 2; j++) s += A(u + vec2(float(i), float(j) * uAsp) * r); return s / 25.0; }
vec4 blurB(vec2 u, float r){ vec4 s = vec4(0.0); for (int i = -2; i <= 2; i++) for (int j = -2; j <= 2; j++) s += B(u + vec2(float(i), float(j) * uAsp) * r); return s / 25.0; }
void main(){
  vec2 u = vUv; float p = clamp(uP, 0.0, 1.0); float e = ease(p);
  vec4 c;
  if (uT == 1) { // Fundido a negro
    c = p < 0.5 ? mix(A(u), vec4(0, 0, 0, 1), p * 2.0) : mix(vec4(0, 0, 0, 1), B(u), (p - 0.5) * 2.0);
  } else if (uT == 2) { // Disolver
    c = mix(A(u), B(u), p);
  } else if (uT == 3) { // Deslizar a la izquierda
    c = u.x < 1.0 - e ? A(u + vec2(e, 0.0)) : B(u - vec2(1.0 - e, 0.0));
  } else if (uT == 4) { // Deslizar a la derecha
    c = u.x > e ? A(u - vec2(e, 0.0)) : B(u + vec2(1.0 - e, 0.0));
  } else if (uT == 5) { // Deslizar hacia arriba
    c = u.y >= e ? A(u - vec2(0.0, e)) : B(u + vec2(0.0, 1.0 - e));
  } else if (uT == 6) { // Zoom
    vec2 ca = (u - 0.5) / (1.0 + e * 0.8) + 0.5;
    vec2 cb = (u - 0.5) / (1.35 - 0.35 * e) + 0.5;
    c = mix(A(ca), B(cb), smoothstep(0.35, 0.65, p));
  } else if (uT == 7) { // Barrido
    float ed = p * 1.2 - 0.1;
    c = mix(B(u), A(u), smoothstep(ed - 0.06, ed + 0.06, u.x));
  } else if (uT == 8) { // Desenfoque
    float r = sin(p * 3.14159) * 0.012;
    c = mix(blurA(u, r), blurB(u, r), smoothstep(0.4, 0.6, p));
  } else if (uT == 9) { // Giro
    float a = (p < 0.5 ? p : p - 1.0) * 3.14159;
    float s = 1.0 + sin(p * 3.14159) * 0.6;
    vec2 d = (u - 0.5); d.y /= uAsp;
    d = mat2(cos(a), -sin(a), sin(a), cos(a)) * d / s; d.y *= uAsp;
    c = p < 0.5 ? A(d + 0.5) : B(d + 0.5);
  } else if (uT == 10) { // Círculo
    vec2 d = u - 0.5; d.y /= uAsp;
    float r = e * 0.9 / min(1.0, 1.0 / uAsp);
    c = mix(B(u), A(u), smoothstep(r - 0.02, r + 0.02, length(d)));
  } else if (uT == 11) { // Destello blanco
    float w = 1.0 - abs(p * 2.0 - 1.0);
    c = mix(p < 0.5 ? A(u) : B(u), vec4(1.0), smoothstep(0.0, 1.0, w) * 0.95);
  } else if (uT == 12) { // Empujar con zoom (tipo "zoom in" de CapCut)
    float z = p < 0.5 ? 1.0 + e * 2.0 : 3.0 - e * 2.0;
    vec2 q = (u - 0.5) / z + 0.5;
    c = p < 0.5 ? blurA(q, p * 0.01) : blurB(q, (1.0 - p) * 0.01);
  } else {
    c = A(u);
  }
  o = vec4(c.rgb, 1.0);
}`;

function compilar(gl, vs, fs) {
  const mk = (t, s) => { const sh = gl.createShader(t); gl.shaderSource(sh, s); gl.compileShader(sh); if (!gl.getShaderParameter(sh, gl.COMPILE_STATUS)) throw new Error(gl.getShaderInfoLog(sh)); return sh; };
  const p = gl.createProgram();
  gl.attachShader(p, mk(gl.VERTEX_SHADER, vs));
  gl.attachShader(p, mk(gl.FRAGMENT_SHADER, fs));
  gl.bindAttribLocation(p, 0, 'aPos');
  gl.linkProgram(p);
  if (!gl.getProgramParameter(p, gl.LINK_STATUS)) throw new Error(gl.getProgramInfoLog(p));
  const u = {};
  const n = gl.getProgramParameter(p, gl.ACTIVE_UNIFORMS);
  for (let i = 0; i < n; i++) { const nm = gl.getActiveUniform(p, i).name; u[nm] = gl.getUniformLocation(p, nm); }
  return { p, u };
}

export const TRANSICIONES = [
  { id: 'ninguna', n: 'Corte', t: 0 },
  { id: 'fundido', n: 'Fundido a negro', t: 1 },
  { id: 'disolver', n: 'Disolver', t: 2 },
  { id: 'izq', n: 'Deslizar ←', t: 3 },
  { id: 'der', n: 'Deslizar →', t: 4 },
  { id: 'arriba', n: 'Deslizar ↑', t: 5 },
  { id: 'zoom', n: 'Zoom', t: 6 },
  { id: 'zoomin', n: 'Zoom rápido', t: 12 },
  { id: 'barrido', n: 'Barrido', t: 7 },
  { id: 'desenfoque', n: 'Desenfoque', t: 8 },
  { id: 'giro', n: 'Giro', t: 9 },
  { id: 'circulo', n: 'Círculo', t: 10 },
  { id: 'destello', n: 'Destello', t: 11 },
];

// Efectos por clip (el número es el que entiende el sombreador)
export const EFECTOS = [
  { id: 'ninguno', n: 'Ninguno', e: '⦸', t: 0 },
  { id: 'bn', n: 'Blanco y negro', e: '◐', t: 1 },
  { id: 'sepia', n: 'Sepia', e: '🟫', t: 2 },
  { id: 'retro', n: 'Retro', e: '📼', t: 3 },
  { id: 'vineta', n: 'Viñeta', e: '◉', t: 4 },
  { id: 'destello', n: 'Destellos', e: '⚡', t: 5 },
  { id: 'sacudida', n: 'Sacudida', e: '〰️', t: 6 },
  { id: 'pulso', n: 'Zoom al ritmo', e: '💓', t: 7 },
  { id: 'espejo', n: 'Espejo', e: '🪞', t: 8 },
  { id: 'glitch', n: 'Glitch RGB', e: '🌈', t: 9 },
];
// Filtros de color (para todo el video)
export const FILTROS = [
  { id: 'ninguno', n: 'Original', t: 0, g: 'linear-gradient(135deg,#6b7a72,#c9d3cd)' },
  { id: 'vivido', n: 'Vívido', t: 1, g: 'linear-gradient(135deg,#ff3d6e,#ffd23d,#2fd1ff)' },
  { id: 'calido', n: 'Cálido', t: 2, g: 'linear-gradient(135deg,#ff9d4d,#ffe0a3)' },
  { id: 'frio', n: 'Frío', t: 3, g: 'linear-gradient(135deg,#4d8cff,#bfe3ff)' },
  { id: 'cine', n: 'Cine', t: 4, g: 'linear-gradient(135deg,#0f4c5c,#ff9a4d)' },
  { id: 'atardecer', n: 'Atardecer', t: 5, g: 'linear-gradient(135deg,#ff6b3d,#ffc46b,#7a3dff)' },
  { id: 'pastel', n: 'Pastel', t: 6, g: 'linear-gradient(135deg,#f9c6dc,#c6e2f9)' },
  { id: 'noir', n: 'Noir', t: 7, g: 'linear-gradient(135deg,#111,#eee)' },
  { id: 'vintage', n: 'Vintage', t: 8, g: 'linear-gradient(135deg,#8a6a3d,#e8d2a8)' },
];

export class Motor {
  constructor() {
    this.canvas = document.createElement('canvas');
    const gl = this.canvas.getContext('webgl2', { preserveDrawingBuffer: true, alpha: false, antialias: false, premultipliedAlpha: false });
    if (!gl) throw new Error('Este navegador no tiene WebGL2. Actualice Chrome o Safari.');
    this.gl = gl;
    // Si el celular se queda sin memoria le quita la parte gráfica al navegador: se pide que la devuelva y se rearma
    this.canvas.addEventListener('webglcontextlost', (e) => { e.preventDefault(); this.perdido = true; });
    this.canvas.addEventListener('webglcontextrestored', () => { const W = this.W, H = this.H; this.init(); this.perdido = false; this.tamano(W, H); });
    this.init();
  }
  init() {
    const gl = this.gl;
    this.pClip = compilar(gl, VERT, FRAG_CLIP);
    this.pMix = compilar(gl, VERT, FRAG_MIX);
    this.vao = gl.createVertexArray();
    gl.bindVertexArray(this.vao);
    const b = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, b);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 1, -1, -1, 1, 1, 1]), gl.STATIC_DRAW);
    gl.enableVertexAttribArray(0);
    gl.vertexAttribPointer(0, 2, gl.FLOAT, false, 0, 0);
    gl.pixelStorei(gl.UNPACK_FLIP_Y_WEBGL, false);
    gl.pixelStorei(gl.UNPACK_PREMULTIPLY_ALPHA_WEBGL, false);
    this.src = [this.tex(), this.tex()];
    this.mask = [this.tex(), this.tex()];
    this.fb = [null, null];
    this.fbt = [null, null];
    this.W = 0; this.H = 0;
  }
  // Espera a que vuelva la parte gráfica (máximo "ms"); devuelve true si volvió
  async recuperar(ms = 3000) {
    const t0 = performance.now();
    while (this.gl.isContextLost() && performance.now() - t0 < ms) await new Promise(r => setTimeout(r, 100));
    return !this.gl.isContextLost();
  }
  tex() {
    const gl = this.gl, t = gl.createTexture();
    gl.bindTexture(gl.TEXTURE_2D, t);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
    gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, 1, 1, 0, gl.RGBA, gl.UNSIGNED_BYTE, new Uint8Array([0, 0, 0, 255]));
    return t;
  }
  tamano(W, H) {
    if (W === this.W && H === this.H) return;
    const gl = this.gl;
    this.W = W; this.H = H;
    this.canvas.width = W; this.canvas.height = H;
    for (let i = 0; i < 2; i++) {
      if (this.fb[i]) { gl.deleteFramebuffer(this.fb[i]); gl.deleteTexture(this.fbt[i]); }
      const t = this.tex();
      gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, W, H, 0, gl.RGBA, gl.UNSIGNED_BYTE, null);
      const f = gl.createFramebuffer();
      gl.bindFramebuffer(gl.FRAMEBUFFER, f);
      gl.framebufferTexture2D(gl.FRAMEBUFFER, gl.COLOR_ATTACHMENT0, gl.TEXTURE_2D, t, 0);
      this.fb[i] = f; this.fbt[i] = t;
    }
    gl.bindFramebuffer(gl.FRAMEBUFFER, null);
  }
  // Dibuja un clip (video/imagen/canvas) en el buffer "slot": encuadre, belleza, color y efecto
  // op: { fit: 'llenar'|'desenfoque'|'color', bg: [r,g,b], adj: {brillo, contraste, saturacion, temperatura, vineta}, fil, filK, ef, t }
  clip(slot, fuente, sw, sh, mascara, belleza, enc, op = {}) {
    const gl = this.gl, P = this.pClip;
    gl.bindFramebuffer(gl.FRAMEBUFFER, this.fb[slot]);
    gl.viewport(0, 0, this.W, this.H);
    if (!fuente || !sw || !sh) { gl.clearColor(0, 0, 0, 1); gl.clear(gl.COLOR_BUFFER_BIT); return; }
    gl.useProgram(P.p);
    gl.activeTexture(gl.TEXTURE0);
    gl.bindTexture(gl.TEXTURE_2D, this.src[slot]);
    try { gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, fuente); } catch (e) { /* cuadro aún no listo */ }
    gl.uniform1i(P.u.uSrc, 0);
    gl.activeTexture(gl.TEXTURE1);
    gl.bindTexture(gl.TEXTURE_2D, this.mask[slot]);
    const conMascara = !!(mascara && belleza && belleza.on);
    if (conMascara) gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, mascara);
    gl.uniform1i(P.u.uMask, 1);
    const ao = this.W / this.H, as = sw / sh;
    const cov = as > ao ? [ao / as, 1] : [1, as / ao];
    // Encajar (con fondo): el video se ve completo y lo que sobra se rellena
    const fit = op.fit === 'desenfoque' ? 1 : op.fit === 'color' ? 2 : 0;
    const sc = fit ? (as > ao ? [1, as / ao] : [ao / as, 1]) : cov.slice();
    // Zoom y encuadre del clip (acercar y mover dentro del mismo cuadro, lo de afuera se recorta)
    const z = Math.max(1, Math.min(6, enc?.z || 1));
    sc[0] /= z; sc[1] /= z;
    gl.uniform2f(P.u.uScale, sc[0], sc[1]);
    gl.uniform2f(P.u.uScaleCov, cov[0], cov[1]);
    gl.uniform2f(P.u.uPan, Math.max(-1, Math.min(1, enc?.x || 0)) * Math.max(0, 1 - sc[0]) / 2, Math.max(-1, Math.min(1, enc?.y || 0)) * Math.max(0, 1 - sc[1]) / 2);
    gl.uniform1f(P.u.uFit, fit);
    const bg = op.bg || [0, 0, 0];
    gl.uniform3f(P.u.uBg, bg[0], bg[1], bg[2]);
    const a = op.adj || {};
    gl.uniform4f(P.u.uAdj, a.brillo || 0, a.contraste || 0, a.saturacion || 0, a.temperatura || 0);
    gl.uniform1f(P.u.uVig, a.vineta || 0);
    gl.uniform1i(P.u.uFil, op.fil || 0);
    gl.uniform1f(P.u.uFilK, op.filK ?? 1);
    gl.uniform1i(P.u.uEf, op.ef || 0);
    gl.uniform1f(P.u.uT, op.t || 0);
    gl.uniform2f(P.u.uRes, this.W, this.H);
    // Paso del suavizado relativo al tamaño del rostro en pantalla (igual en vista previa y exportado)
    const k = (belleza?.suave ?? 0);
    const paso = (0.0011 + 0.0016 * k) * Math.min(1, sc[1]);
    gl.uniform2f(P.u.uStep, paso * (sh / sw), paso);
    gl.uniform1f(P.u.uBeauty, belleza?.on ? 1 : 0);
    gl.uniform1f(P.u.uHasMask, conMascara ? 1 : 0);
    gl.uniform1f(P.u.uSuave, k);
    gl.uniform1f(P.u.uAclara, belleza?.aclara ?? 0);
    gl.uniform1f(P.u.uBrillo, belleza?.brillo ?? 0);
    const tono = conMascara && mascara._tono;
    gl.uniform1f(P.u.uHasTono, tono ? 1 : 0);
    gl.uniform3f(P.u.uTono, tono ? tono[0] : 0.6, tono ? tono[1] : 0.45, tono ? tono[2] : 0.4);
    gl.bindVertexArray(this.vao);
    gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
  }
  // Mezcla A y B en pantalla con la transición tipo t y avance p (0..1)
  mezclar(tipo, p) {
    const gl = this.gl, P = this.pMix;
    gl.bindFramebuffer(gl.FRAMEBUFFER, null);
    gl.viewport(0, 0, this.W, this.H);
    gl.useProgram(P.p);
    gl.activeTexture(gl.TEXTURE0); gl.bindTexture(gl.TEXTURE_2D, this.fbt[0]); gl.uniform1i(P.u.uA, 0);
    gl.activeTexture(gl.TEXTURE1); gl.bindTexture(gl.TEXTURE_2D, this.fbt[1]); gl.uniform1i(P.u.uB, 1);
    gl.uniform1f(P.u.uP, p);
    gl.uniform1i(P.u.uT, tipo);
    gl.uniform1f(P.u.uAsp, this.W / this.H);
    gl.bindVertexArray(this.vao);
    gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
  }
  negro() {
    const gl = this.gl;
    gl.bindFramebuffer(gl.FRAMEBUFFER, null);
    gl.viewport(0, 0, this.W, this.H);
    gl.clearColor(0, 0, 0, 1); gl.clear(gl.COLOR_BUFFER_BIT);
  }
}
