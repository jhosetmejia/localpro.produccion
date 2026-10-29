<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

// Plantilla de obituario: diseño, medida de pantalla, fondo (color, imagen o video) y animación.
// Se crea una vez y se reutiliza: para cada fallecido solo se cambian los datos.
class ObituarioPlantilla extends Model
{
    use PerteneceATienda;

    protected $table = 'obituario_plantillas';

    protected $fillable = ['nombre', 'diseno', 'medida', 'fondo_tipo', 'fondo_color', 'fondo_color2', 'oscurecer', 'animacion', 'color_acento', 'color_texto', 'mostrar_logo', 'logo_fondo', 'velacion_pos', 'velacion_x', 'velacion_y', 'mostrar_memoria', 'libre', 'titulo', 'frase', 'pie', 'pie_mostrar', 'pie_nombre', 'pie_modo', 'pie_eslogan', 'adornos', 'cita', 'base', 'flores', 'anim_op', 'mover'];

    protected $casts = ['libre' => 'array', 'mostrar_logo' => 'boolean', 'mostrar_memoria' => 'boolean', 'pie_mostrar' => 'boolean', 'oscurecer' => 'integer', 'flores' => 'array', 'anim_op' => 'array', 'mover' => 'array'];

    // Diseños: [nombre, descripción, acento, texto, fondo para la miniatura]
    public const DISENOS = [
        // Sin diseño: solo la imagen o el video que suba la funeraria, con los datos encima (sin flores, marcos ni adornos)
        'ninguno' => ['Ninguna (solo mi imagen)', 'Sin diseño de LocalProo: se ve solo la imagen o el video que usted suba, con los datos encima.', '#b08d57', '#1d2422', 'repeating-linear-gradient(45deg,#ffffff 0 12px,#f1f3f2 12px 24px)'],
        'clasico' => ['Clásico mármol', 'Blanco con dorado, foto a un lado y datos al centro.', '#b08d57', '#3b3127', 'linear-gradient(135deg,#ffffff,#ece8e1)'],
        'dorado' => ['Negro y dorado', 'Elegante, fondo oscuro con letras doradas.', '#d4af37', '#f6eedb', 'linear-gradient(135deg,#1b1712,#050505)'],
        'cielo' => ['Cielo sereno', 'Azul suave con nubes, transmite paz.', '#ffffff', '#ffffff', 'linear-gradient(180deg,#6fa8dc,#cfe5f7)'],
        'floral' => ['Floral', 'Tonos crema y rosa con flores en las esquinas.', '#b5677d', '#4a2b33', 'linear-gradient(135deg,#fff7f2,#f6dde2)'],
        'vela' => ['Luz de vela', 'Cálido y oscuro, con brillo de vela.', '#f0b45a', '#fff3e0', 'radial-gradient(circle at 50% 70%,#5b3216,#140b06 70%)'],
        'sencillo' => ['Sencillo', 'Limpio y claro, fácil de leer de lejos.', '#2f5d50', '#1d2422', 'linear-gradient(135deg,#f7f8f6,#e8ece9)'],
        // Tarjetas prediseñadas (otra distribución: logo, frase, "Obituarios", nombre y datos con íconos)
        'cielo_cruz' => ['Cielo y cruz', 'Cielo con nubes, cruz dorada y lirios blancos.', '#a8844a', '#233547', 'linear-gradient(180deg,#86b3df,#efeae0)'],
        'noche_vela' => ['Noche y vela', 'Negro con dorado, vela encendida y lirio.', '#d4af5f', '#efe6d2', 'radial-gradient(circle at 70% 60%,#5b4420,#050505 60%)'],
        'acuarela' => ['Acuarela azul', 'Blanco con manchas de acuarela y hojas azules.', '#3f73b8', '#2b3f5c', 'radial-gradient(circle at 30% 40%,#8fb6ea,#fbfcfe 65%)'],
        'amanecer' => ['Amanecer en la montaña', 'Montañas al atardecer con una cruz y rayos de sol.', '#f6c27a', '#ffffff', 'linear-gradient(180deg,#22406f,#f3a65c 65%,#25344f)'],
        'botanico' => ['Botánico', 'Crema con eucalipto, foto tipo polaroid y lirio.', '#b08d57', '#3d342a', 'linear-gradient(135deg,#fdfaf4,#e4ddcc)'],
    ];

    // Diseños con la distribución de tarjeta
    public const TARJETAS = ['cielo_cruz', 'noche_vela', 'acuarela', 'amanecer', 'botanico'];

    // Plantillas que trae cada funeraria lista para usar: solo cambia la foto y los datos
    public const PREDISENADAS = [
        'cielo_cruz' => ['nombre' => 'Cielo y cruz', 'animacion' => 'ninguna', 'cita' => 'Tu recuerdo vivirá por siempre en nuestros corazones', 'pie' => 'Siempre en nuestros corazones'],
        'noche_vela' => ['nombre' => 'Noche y vela', 'animacion' => 'destellos', 'cita' => 'El amor no se va, solo cambia de forma…', 'pie' => 'Siempre contigo'],
        'acuarela' => ['nombre' => 'Acuarela azul', 'animacion' => 'ninguna', 'cita' => 'La vida nos regaló tu presencia, la eternidad nos regala tu recuerdo', 'pie' => 'Eternamente en nuestros corazones'],
        'amanecer' => ['nombre' => 'Amanecer en la montaña', 'animacion' => 'ninguna', 'cita' => 'Hoy partiste, pero tu luz sigue iluminando nuestro camino', 'pie' => 'Un homenaje de amor'],
        'botanico' => ['nombre' => 'Botánico', 'animacion' => 'ninguna', 'cita' => 'Siempre te recordaremos con amor', 'pie' => 'Siempre en nuestros corazones'],
    ];

    // Medidas de pantalla: [nombre, ancho, alto, orientación h · v · c]
    public const MEDIDAS = [
        'fhd' => ['Full HD 1920 × 1080 (la mayoría de televisores)', 1920, 1080, 'h'],
        '4k' => ['4K UHD 3840 × 2160 (televisor 4K)', 3840, 2160, 'h'],
        'hd' => ['HD 1366 × 768 (televisores pequeños)', 1366, 768, 'h'],
        'v1080' => ['Vertical 1080 × 1920 (tótem o TV parado)', 1080, 1920, 'v'],
        'v4k' => ['Vertical 4K 2160 × 3840', 2160, 3840, 'v'],
        'cuadrado' => ['Cuadrado 1080 × 1080 (WhatsApp y Facebook)', 1080, 1080, 'c'],
    ];

    // Tamaño de diseño de cada orientación (la imagen final se escala a la medida elegida)
    public const BASE = ['h' => [1920, 1080], 'v' => [1080, 1920], 'c' => [1080, 1080]];

    public const FONDOS = ['diseno' => 'El del diseño', 'color' => 'Color o degradado', 'imagen' => 'Imagen propia', 'video' => 'Video propio'];

    public const ANIMACIONES = [
        'ninguna' => 'Sin animación',
        'petalos' => 'Pétalos cayendo',
        'destellos' => 'Destellos dorados',
        'estrellas' => 'Estrellas',
        'luz' => 'Rayos de luz',
        'nubes' => 'Nubes',
        'palomas' => 'Palomas',
    ];

    public const ADORNOS = ['muchos' => 'Muchas flores', 'pocos' => 'Pocas (solo en la foto)', 'ninguno' => 'Sin flores'];

    // Dónde van las flores junto a la foto
    public const FLOR_POS = ['abajo' => 'Abajo, a los dos lados', 'abajo_izq' => 'Abajo a la izquierda', 'abajo_der' => 'Abajo a la derecha', 'diagonal' => 'Arriba y abajo (en diagonal)', 'ninguna' => 'Sin flores en la foto'];

    // Flores de las esquinas de la pantalla (diseños clásicos)
    public const FLOR_ESQ = ['dos' => 'Arriba izquierda y abajo derecha', 'cuatro' => 'Las 4 esquinas', 'arriba' => 'Solo arriba', 'abajo' => 'Solo abajo', 'izquierda' => 'Solo a la izquierda', 'derecha' => 'Solo a la derecha', 'ninguna' => 'Sin flores en las esquinas'];

    public const FRASE = 'Descansó en la paz del Señor';

    // Posición de las flores de la foto: pos, x y y (px) y tamaño (%)
    public function getFloresDatosAttribute(): array
    {
        $f = (array) ($this->flores ?? []);
        return [
            'pos' => isset(self::FLOR_POS[$f['pos'] ?? '']) ? $f['pos'] : 'abajo',
            'x' => (int) ($f['x'] ?? 0),
            'y' => (int) ($f['y'] ?? 0),
            'tam' => (int) ($f['tam'] ?? 100) ?: 100,
            'esq' => isset(self::FLOR_ESQ[$f['esq'] ?? '']) ? $f['esq'] : 'dos',
        ];
    }

    // Animación: cantidad, tamaño y velocidad en % (100 = normal)
    public function getAnimDatosAttribute(): array
    {
        $a = (array) ($this->anim_op ?? []);
        $n = fn ($k) => max(10, min(500, (int) ($a[$k] ?? 100) ?: 100));
        return ['cant' => $n('cant'), 'tam' => $n('tam'), 'vel' => $n('vel')];
    }

    // Logo: sin fondo (como lo subieron) o sobre un recuadro blanco
    public const LOGO_FONDOS = ['sin' => 'Sin fondo (como lo subió)', 'blanco' => 'Sobre un recuadro blanco'];

    // Diseño "Ninguna": bloques que se ubican a mano sobre la imagen. [nombre, por orientación: [x %, y %, ancho %, tamaño %, se ve]]
    // x / y es el centro del bloque, en % del ancho y alto de la pantalla.
    public const LIBRE_BLOQUES = [
        'foto' => ['Foto', 'h' => [16, 42, 28, 100, 1], 'v' => [50, 18, 60, 100, 1], 'c' => [22, 45, 38, 80, 1]],
        'velacion' => ['Lugar de velación (debajo de la foto)', 'h' => [16, 78, 28, 100, 1], 'v' => [50, 33, 80, 100, 1], 'c' => [22, 82, 38, 90, 1]],
        'memoria' => ['"En memoria de"', 'h' => [16, 86, 28, 100, 0], 'v' => [50, 36, 80, 100, 0], 'c' => [22, 90, 38, 90, 0]],
        'cab' => ['Logo', 'h' => [50, 10, 40, 100, 1], 'v' => [50, 4, 60, 100, 1], 'c' => [66, 8, 50, 80, 1]],
        'titulo' => ['Título ("Obituario")', 'h' => [50, 22, 44, 100, 1], 'v' => [50, 37, 86, 100, 1], 'c' => [66, 18, 60, 90, 1]],
        'frase' => ['Frase', 'h' => [50, 29, 44, 100, 1], 'v' => [50, 41, 86, 100, 1], 'c' => [66, 24, 60, 90, 1]],
        'nombre' => ['Nombre', 'h' => [50, 38, 46, 100, 1], 'v' => [50, 46, 90, 100, 1], 'c' => [66, 33, 62, 90, 1]],
        'anios' => ['Fechas (nació / falleció)', 'h' => [50, 45, 44, 100, 1], 'v' => [50, 50, 86, 100, 1], 'c' => [66, 40, 60, 90, 1]],
        'datos' => ['Datos (fecha, hora, lugar…)', 'h' => [50, 60, 46, 100, 1], 'v' => [50, 62, 88, 100, 1], 'c' => [66, 57, 62, 90, 1]],
        'invitan' => ['Invitan', 'h' => [50, 84, 46, 100, 1], 'v' => [50, 81, 88, 100, 1], 'c' => [66, 83, 62, 85, 1]],
        'logo2' => ['Logo a la derecha', 'h' => [88, 30, 20, 100, 0], 'v' => [80, 95, 30, 100, 0], 'c' => [88, 8, 20, 80, 0]],
        'pie' => ['Pie (frase / eslogan)', 'h' => [88, 78, 20, 100, 1], 'v' => [50, 95, 80, 100, 1], 'c' => [66, 95, 60, 80, 0]],
    ];

    // Posiciones de los bloques para una orientación (h · v · c): lo guardado o, si no hay, lo de fábrica
    public function libreDatos(string $o): array
    {
        $guardado = (array) (($this->libre ?? [])[$o] ?? []);
        $out = [];
        foreach (self::LIBRE_BLOQUES as $k => $b) {
            [$x, $y, $w, $s, $v] = $b[$o] ?? $b['h'];
            $g = (array) ($guardado[$k] ?? []);
            $out[$k] = [
                'x' => max(-20, min(120, (float) ($g['x'] ?? $x))), 'y' => max(-20, min(120, (float) ($g['y'] ?? $y))),
                'w' => max(5, min(100, (float) ($g['w'] ?? $w))), 's' => max(20, min(400, (int) ($g['s'] ?? $s))),
                'v' => (bool) ($g['v'] ?? $v),
            ];
        }
        return $out;
    }

    // Lo de fábrica de todas las orientaciones (para el botón "Acomodar como al principio")
    public static function libreFabrica(): array
    {
        $out = [];
        foreach (['h', 'v', 'c'] as $o) {
            foreach (self::LIBRE_BLOQUES as $k => $b) {
                [$x, $y, $w, $s, $v] = $b[$o];
                $out[$o][$k] = ['x' => $x, 'y' => $y, 'w' => $w, 's' => $s, 'v' => (bool) $v];
            }
        }
        return $out;
    }

    // Limpia lo que llega del formulario: solo bloques y orientaciones conocidas, con números dentro del rango
    public static function libreLimpio($json): ?array
    {
        $d = is_array($json) ? $json : json_decode((string) $json, true);
        if (!is_array($d)) return null;
        $out = [];
        foreach (['h', 'v', 'c'] as $o) {
            if (!isset($d[$o]) || !is_array($d[$o])) continue;
            foreach (self::LIBRE_BLOQUES as $k => $b) {
                $g = $d[$o][$k] ?? null;
                if (!is_array($g)) continue;
                $out[$o][$k] = [
                    'x' => round(max(-20, min(120, (float) ($g['x'] ?? 50))), 2), 'y' => round(max(-20, min(120, (float) ($g['y'] ?? 50))), 2),
                    'w' => round(max(5, min(100, (float) ($g['w'] ?? 40))), 2), 's' => max(20, min(400, (int) ($g['s'] ?? 100))),
                    'v' => !empty($g['v']),
                ];
            }
        }
        return $out ?: null;
    }

    // ===== Todos los diseños (menos "Ninguna"): mover y agrandar a mano cada elemento =====
    // x, y: cuánto se corre en px del lienzo; s: tamaño en % (100 = normal). Por orientación (h, v, c).
    public const MOVER_BLOQUES = [
        'cab' => 'Logo',
        'cita' => 'Frase de arriba a la derecha',
        'foto' => 'Foto (con sus flores)',
        'memoria' => '"En memoria de"',
        'titulo' => 'Título ("Obituario")',
        'frase' => 'Frase',
        'nombre' => 'Nombre',
        'anios' => 'Fechas (nació / falleció)',
        'datos' => 'Datos (fecha, hora, lugar…)',
        'datos2' => 'Destino final e Invitan',
        'invitan' => 'Invitan',
        'logo2' => 'Logo a la derecha',
        'pie' => 'Pie (nombre / eslogan)',
    ];

    public function moverDatos(string $o): array
    {
        $out = [];
        foreach ((array) (($this->mover ?? [])[$o] ?? []) as $k => $g) {
            if (!isset(self::MOVER_BLOQUES[$k]) || !is_array($g)) continue;
            $out[$k] = ['x' => max(-1500, min(1500, (int) ($g['x'] ?? 0))), 'y' => max(-1500, min(1500, (int) ($g['y'] ?? 0))), 's' => max(30, min(300, (int) ($g['s'] ?? 100)))];
        }
        return $out;
    }

    // Lo que llega del formulario: solo bloques y orientaciones conocidas; lo que quedó en su lugar no se guarda
    public static function moverLimpio($json): ?array
    {
        $d = is_array($json) ? $json : json_decode((string) $json, true);
        if (!is_array($d)) return null;
        $out = [];
        foreach (['h', 'v', 'c'] as $o) {
            foreach ((array) ($d[$o] ?? []) as $k => $g) {
                if (!isset(self::MOVER_BLOQUES[$k]) || !is_array($g)) continue;
                $b = ['x' => max(-1500, min(1500, (int) round((float) ($g['x'] ?? 0)))), 'y' => max(-1500, min(1500, (int) round((float) ($g['y'] ?? 0)))), 's' => max(30, min(300, (int) ($g['s'] ?? 100)))];
                if ($b['x'] || $b['y'] || $b['s'] !== 100) $out[$o][$k] = $b;
            }
        }
        return $out ?: null;
    }

    // Dónde sale el lugar de velación en la tarjeta
    public const VELACION_POS = ['datos' => 'Debajo de las exequias (fecha, hora, lugar)', 'foto' => 'Sobre la foto (etiqueta)', 'debajo' => 'Debajo de la foto', 'oculto' => 'No mostrarlo'];

    // Lo que se copia a las demás plantillas al marcar "usar en todas": logo y textos, no el diseño
    public const COMPARTIDOS = ['mostrar_logo', 'mostrar_memoria', 'logo_fondo', 'velacion_pos', 'velacion_x', 'velacion_y', 'titulo', 'pie_modo', 'pie_mostrar', 'pie', 'pie_nombre', 'pie_eslogan'];

    // Pie de la tarjeta: qué se muestra debajo del nombre
    public const PIE_MODOS = ['frase' => 'Solo la frase', 'eslogan' => 'Solo el eslogan', 'ambos' => 'Frase y eslogan', 'ninguno' => 'Quitar el pie'];

    // Pie de la tarjeta: nombre (o el del negocio), frase y/o eslogan; null si lo quitaron
    public function pieDatos($tienda): ?array
    {
        $modo = isset(self::PIE_MODOS[$this->pie_modo]) ? $this->pie_modo : 'frase';
        if ($modo === 'ninguno') return null;
        $nombre = trim((string) $this->pie_nombre) !== '' ? $this->pie_nombre : $tienda?->nombre;
        $frase = in_array($modo, ['frase', 'ambos'], true) ? (trim((string) $this->pie) ?: null) : null;
        $eslogan = in_array($modo, ['eslogan', 'ambos'], true) ? (trim((string) $this->pie_eslogan) ?: null) : null;
        // Si la frase y el eslogan dicen lo mismo, sale una sola vez
        $igual = fn ($t) => mb_strtolower(preg_replace('/\W+/u', '', (string) $t));
        if ($frase && $eslogan && $igual($frase) === $igual($eslogan)) $frase = null;
        return ['nombre' => $nombre, 'frase' => $frase, 'eslogan' => $eslogan];
    }

    public function getEsTarjetaAttribute(): bool
    {
        return in_array($this->diseno, self::TARJETAS, true);
    }

    // Crea las plantillas prediseñadas que el negocio aún no tenga (una sola vez por negocio)
    public static function crearPredisenadas(): void
    {
        $tienda = \App\Support\TiendaActual::tienda();
        if (!$tienda || $tienda->obituarios_predisenadas) return;
        foreach (self::PREDISENADAS as $diseno => $x) {
            if (static::where('base', $diseno)->exists()) continue;
            static::create($x + [
                'base' => $diseno, 'diseno' => $diseno, 'medida' => 'fhd', 'fondo_tipo' => 'diseno', 'oscurecer' => 30,
                'mostrar_logo' => true, 'titulo' => 'Obituarios', 'frase' => self::FRASE, 'adornos' => 'muchos',
            ]);
        }
        $tienda->forceFill(['obituarios_predisenadas' => true])->save();
    }

    public function obituarios()
    {
        return $this->hasMany(Obituario::class, 'plantilla_id');
    }

    public function getMedidaDatosAttribute(): array
    {
        $m = self::MEDIDAS[$this->medida] ?? self::MEDIDAS['fhd'];
        [$bw, $bh] = self::BASE[$m[3]];
        return ['nombre' => $m[0], 'ancho' => $m[1], 'alto' => $m[2], 'orientacion' => $m[3], 'base_ancho' => $bw, 'base_alto' => $bh, 'escala' => $m[1] / $bw];
    }

    public function getFondoUrlAttribute(): ?string
    {
        return $this->fondo_path ? Storage::disk('public')->url($this->fondo_path) : null;
    }

    public function getVideoUrlAttribute(): ?string
    {
        return $this->video_path ? Storage::disk('public')->url($this->video_path) : null;
    }

    public function getAcentoAttribute(): string
    {
        return $this->color_acento ?: (self::DISENOS[$this->diseno][2] ?? '#b08d57');
    }

    public function getTextoColorAttribute(): string
    {
        return $this->color_texto ?: (self::DISENOS[$this->diseno][3] ?? '#3b3127');
    }

    // La primera vez el negocio ya tiene una plantilla lista para usar
    public static function porDefecto(): self
    {
        return static::firstOrCreate(['nombre' => 'Clásica'], [
            'diseno' => 'clasico', 'medida' => 'fhd', 'fondo_tipo' => 'diseno', 'animacion' => 'destellos',
            'mostrar_logo' => true, 'titulo' => 'Obituario', 'frase' => self::FRASE, 'adornos' => 'muchos',
        ]);
    }
}
