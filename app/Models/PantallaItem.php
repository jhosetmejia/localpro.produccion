<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

// Imagen o video que sale en una pantalla del negocio
class PantallaItem extends Model
{
    use PerteneceATienda;

    protected $table = 'pantalla_items';

    protected $fillable = ['pantalla_id', 'tipo', 'path', 'nombre', 'segundos', 'activo', 'orden'];

    protected $casts = ['activo' => 'boolean', 'segundos' => 'integer'];

    public function pantalla()
    {
        return $this->belongsTo(Pantalla::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
