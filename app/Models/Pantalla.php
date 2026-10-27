<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Pantalla del negocio (un televisor): muestra en orden las imágenes y videos que se le suben.
// Se abre en el TV con un código corto (localproo.com/tv/1234) o con su dirección secreta.
class Pantalla extends Model
{
    use PerteneceATienda;

    protected $table = 'pantallas';

    protected $fillable = ['nombre', 'activa', 'ajuste', 'orden'];

    protected $casts = ['activa' => 'boolean'];

    public const AJUSTES = ['completa' => 'Completa (se ve todo, puede quedar con bordes)', 'llenar' => 'Llenar la pantalla (puede recortar los bordes)'];

    protected static function booted(): void
    {
        static::creating(function (Pantalla $p) {
            if (empty($p->token)) $p->token = self::nuevoToken();
            if (empty($p->codigo)) $p->codigo = ObituarioSala::nuevoCodigo();
        });
    }

    public static function nuevoToken(): string
    {
        do {
            $t = Str::lower(Str::random(24));
        } while (static::withoutGlobalScopes()->where('token', $t)->exists());
        return $t;
    }

    public function items()
    {
        return $this->hasMany(PantallaItem::class)->orderBy('orden')->orderBy('id');
    }

    public function getUrlPantallaAttribute(): string
    {
        return route('pantallas.ver', $this->token);
    }

    public function getUrlCortaAttribute(): ?string
    {
        if (!$this->codigo) return null;
        return rtrim(preg_replace('#^https?://(www\.)?#', '', url('/tv')), '/') . '/' . $this->codigo;
    }
}
