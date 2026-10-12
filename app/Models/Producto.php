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
        'precio_venta',
        'impuesto',
        'precio_costo',
        'controla_inventario',
        'descripcion',
        'fecha_vencimiento',
        'marca_id',
        'presentacione_id',
        'img_path',
        'unidad'
    ];

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

    protected $casts = ['controla_inventario' => 'boolean'];

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

    public function handleUploadImage($image)
    {
        $file = $image;
        $name = time() . $file->getClientOriginalName();
        //$file->move(public_path() . '/img/productos/', $name);
        Storage::putFileAs('/public/productos/',$file,$name,'public');

        return $name;
    }
}
