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

    protected $fillable = ['nombre', 'diseno', 'medida', 'fondo_tipo', 'fondo_color', 'fondo_color2', 'oscurecer', 'animacion', 'color_acento', 'color_texto', 'mostrar_logo', 'titulo', 'frase', 'pie', 'adornos', 'cita', 'base', 'flores', 'anim_op'];

    protected $casts = ['mostrar_logo' => 'boolean', 'oscurecer' => 'integer', 'flores' => 'array', 'anim_op' => 'array'];

    // Diseños: [nombre, descripción, acento, texto, fondo para la miniatura]
    public const DISENOS = [
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
        ];
    }

    // Animación: cantidad, tamaño y velocidad en % (100 = normal)
    public function getAnimDatosAttribute(): array
    {
        $a = (array) ($this->anim_op ?? []);
        $n = fn ($k) => max(10, min(500, (int) ($a[$k] ?? 100) ?: 100));
        return ['cant' => $n('cant'), 'tam' => $n('tam'), 'vel' => $n('vel')];
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
