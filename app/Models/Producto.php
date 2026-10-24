<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Producto extends Model
{
    use PerteneceATienda;

    use HasFactory;

    protected $fillable = [
        'codigo',
        'nombre',
        'para',
        'precio_venta',
        'precio_antes',
        'impuesto',
        'precio_costo',
        'controla_inventario',
        'es_servicio',
        'descripcion',
        'fecha_vencimiento',
        'marca_id',
        'presentacione_id',
        'img_path',
        'unidad',
        'mostrar_sin_foto'
    ];

    /** ¿Tiene foto? (principal o alguna por color) */
    public function getTieneFotoAttribute(): bool
    {
        // Cuenta solo si el archivo está de verdad en el servidor (una foto perdida pide volver a subirla)
        if (self::fotoExiste($this->img_path)) {
            return true;
        }
        $fotos = $this->relationLoaded('fotos') ? $this->fotos : $this->fotos()->get();
        return $fotos->contains(fn ($f) => self::fotoExiste($f->path));
    }

    /** ¿Sale en el catálogo en línea? Sin foto no sale, salvo que el negocio pida mostrarlo igual */
    public function getEnCatalogoAttribute(): bool
    {
        return (int) $this->estado === 1 && ($this->tiene_foto || $this->mostrar_sin_foto);
    }

    /** Productos que se muestran en el catálogo en línea */
    public function scopeEnCatalogo($q)
    {
        return $q->where('productos.estado', 1)->where(fn ($w) => $w->where('productos.mostrar_sin_foto', true)
            ->orWhere(fn ($x) => $x->whereNotNull('productos.img_path')->where('productos.img_path', '<>', ''))
            ->orWhereHas('fotos'));
    }

    // Nombre siempre estándar: "CHAQUETA DE CUERO DAMA" → "Chaqueta de Cuero Dama" (en tienda, POS, etiquetas…)
    public function setNombreAttribute($valor): void
    {
        $this->attributes['nombre'] = \App\Support\Textos::titulo($valor);
    }

    // % de descuento si está en promoción (precio antes mayor que el de venta); 0 si no
    public function getDescuentoAttribute(): int
    {
        $antes = (float) $this->precio_antes;
        $venta = (float) $this->precio_venta;
        return $antes > $venta && $antes > 0 ? (int) round((1 - $venta / $antes) * 100) : 0;
    }

    // Para quién es (filtro Mujer / Hombre / Niños del catálogo). Vacío = para todos.
    public const PARA = ['mujer' => ['👩', 'Mujer'], 'hombre' => ['👨', 'Hombre'], 'ninos' => ['🧒', 'Niños']];

    // Cómo se vende: por unidad o por medida (metro, kilo, litro…). [nombre, abreviatura, plural]
    public const UNIDADES = [
        'und' => ['Unidad', 'und', 'unidades'],
        'm' => ['Metro', 'm', 'metros'],
        'cm' => ['Centímetro', 'cm', 'centímetros'],
        'kg' => ['Kilo', 'kg', 'kilos'],
        'g' => ['Gramo', 'g', 'gramos'],
        'lb' => ['Libra', 'lb', 'libras'],
        'l' => ['Litro', 'L', 'litros'],
        'ml' => ['Mililitro', 'ml', 'mililitros'],
    ];

    public function getEsFraccionAttribute(): bool
    {
        return ($this->unidad ?: 'und') !== 'und';
    }

    public function getUnidadCortaAttribute(): string
    {
        return self::UNIDADES[$this->unidad ?: 'und'][1] ?? 'und';
    }

    protected $casts = ['controla_inventario' => 'boolean', 'es_servicio' => 'boolean'];

    public function variantes()
    {
        return $this->hasMany(ProductoVariante::class);
    }

    public function compras()
    {
        return $this->belongsToMany(Compra::class)->withTimestamps()
            ->withPivot('cantidad', 'precio_compra', 'precio_venta');
    }

    public function ventas()
    {
        return $this->belongsToMany(Venta::class)->withTimestamps()
            ->withPivot('cantidad', 'precio_venta', 'descuento');
    }

    public function categorias()
    {
        return $this->belongsToMany(Categoria::class)->withTimestamps();
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class);
    }

    public function presentacione()
    {
        return $this->belongsTo(Presentacione::class);
    }

    // Fotos por color (al tocar el color en la tienda en línea cambia la foto)
    public function fotos()
    {
        return $this->hasMany(ProductoFoto::class)->orderBy('orden')->orderBy('id');
    }

    // Guarda la foto en storage/app/public/productos con un nombre limpio y único.
    // Si el disco no la deja guardar, avisa (antes quedaba el nombre en la base sin archivo y la foto salía rota).
    public function handleUploadImage($image)
    {
        $ext = strtolower($image->getClientOriginalExtension() ?: $image->extension() ?: 'jpg');
        $base = \Illuminate\Support\Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'foto';
        $name = time() . '-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6)) . '-' . mb_substr($base, 0, 60) . '.' . $ext;
        $ok = Storage::disk('public')->putFileAs('productos', $image, $name, 'public');
        if (!$ok || !self::fotoExiste($name, true)) {
            throw new \RuntimeException('No se pudo guardar la foto en el servidor. Intente de nuevo; si sigue pasando, avise a LocalPro.');
        }
        return $name;
    }

    // ¿El archivo de la foto existe en el servidor? (si no, la foto se trata como si no tuviera)
    public static function fotoExiste(?string $path, bool $sinCache = false): bool
    {
        static $cache = [];
        if (!$path) return false;
        if ($sinCache) unset($cache[$path]);
        return $cache[$path] ??= is_file(storage_path('app/public/productos/' . $path));
    }

    // Dirección pública de la foto principal, solo si el archivo existe
    public function getFotoUrlAttribute(): ?string
    {
        return self::fotoExiste($this->img_path) ? asset('storage/productos/' . rawurlencode($this->img_path)) : null;
    }
}
