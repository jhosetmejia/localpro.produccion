<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Negocio extends Model
{
    protected $table = 'negocio';

    protected $fillable = ['nombre', 'nit', 'direccion', 'ciudad', 'telefono', 'email', 'logo_path', 'mensaje_tiquete'];

    // Siempre hay un único registro con los datos del negocio
    public static function datos(): self
    {
        return static::query()->first() ?? static::create(['nombre' => 'Mi Tienda']);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null;
    }
}
