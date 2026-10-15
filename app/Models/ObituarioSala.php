<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Sala de velación de una funeraria. Cada sala tiene su propia pantalla (televisor) con una dirección fija.
class ObituarioSala extends Model
{
    use PerteneceATienda;

    protected $table = 'obituario_salas';

    protected $fillable = ['nombre', 'activa', 'orden'];

    protected $casts = ['activa' => 'boolean'];

    protected static function booted(): void
    {
        static::creating(function (ObituarioSala $s) {
            if (empty($s->token)) $s->token = self::nuevoToken();
        });
    }

    public static function nuevoToken(): string
    {
        do {
            $t = Str::lower(Str::random(24));
        } while (static::withoutGlobalScopes()->where('token', $t)->exists());
        return $t;
    }

    public function obituarios()
    {
        return $this->hasMany(Obituario::class, 'sala_id');
    }

    // Lo que se ve ahora en el televisor de la sala
    public function obituarioActual(): ?Obituario
    {
        return Obituario::withoutGlobalScopes()->where('sala_id', $this->id)->enPantalla()->latest('id')->first();
    }

    public function getUrlPantallaAttribute(): string
    {
        return route('obituarios.pantalla', $this->token);
    }
}
