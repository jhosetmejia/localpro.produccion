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

    protected $fillable = ['nombre', 'diseno', 'medida', 'fondo_tipo', 'fondo_color', 'fondo_color2', 'oscurecer', 'animacion', 'color_acento', 'color_texto', 'mostrar_logo', 'titulo', 'frase', 'pie'];

    protected $casts = ['mostrar_logo' => 'boolean', 'oscurecer' => 'integer'];

    // Diseños: [nombre, descripción, acento, texto, fondo para la miniatura]
    public const DISENOS = [
        'clasico' => ['Clásico mármol', 'Blanco con dorado, foto a un lado y datos al centro.', '#b08d57', '#3b3127', 'linear-gradient(135deg,#ffffff,#ece8e1)'],
        'dorado' => ['Negro y dorado', 'Elegante, fondo oscuro con letras doradas.', '#d4af37', '#f6eedb', 'linear-gradient(135deg,#1b1712,#050505)'],
        'cielo' => ['Cielo sereno', 'Azul suave con nubes, transmite paz.', '#ffffff', '#ffffff', 'linear-gradient(180deg,#6fa8dc,#cfe5f7)'],
        'floral' => ['Floral', 'Tonos crema y rosa con flores en las esquinas.', '#b5677d', '#4a2b33', 'linear-gradient(135deg,#fff7f2,#f6dde2)'],
        'vela' => ['Luz de vela', 'Cálido y oscuro, con brillo de vela.', '#f0b45a', '#fff3e0', 'radial-gradient(circle at 50% 70%,#5b3216,#140b06 70%)'],
        'sencillo' => ['Sencillo', 'Limpio y claro, fácil de leer de lejos.', '#2f5d50', '#1d2422', 'linear-gradient(135deg,#f7f8f6,#e8ece9)'],
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

    public const FRASE = 'Descansó en la paz del Señor';

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
            'mostrar_logo' => true, 'titulo' => 'Obituario', 'frase' => self::FRASE,
        ]);
    }
}
