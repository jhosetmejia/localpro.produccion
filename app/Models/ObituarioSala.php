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
            if (empty($s->codigo)) $s->codigo = self::nuevoCodigo();
        });
    }

    public static function nuevoToken(): string
    {
        do {
            $t = Str::lower(Str::random(24));
        } while (static::withoutGlobalScopes()->where('token', $t)->exists());
        return $t;
    }

    // Código corto (4 números; 5 o 6 si ya no quedan) para escribir en el TV: localproo.com/tv/1234.
    // Es único entre todas las salas y pantallas generales de todos los negocios.
    public static function nuevoCodigo(): string
    {
        foreach ([4, 4, 4, 4, 4, 5, 5, 5, 6, 6, 6, 6] as $n) {
            $c = (string) random_int(10 ** ($n - 1), 10 ** $n - 1);
            $usado = \Illuminate\Support\Facades\DB::table('obituario_salas')->where('codigo', $c)->exists()
                || \Illuminate\Support\Facades\DB::table('tiendas')->where('obituarios_codigo', $c)->exists()
                || (\Illuminate\Support\Facades\Schema::hasTable('pantallas') && \Illuminate\Support\Facades\DB::table('pantallas')->where('codigo', $c)->exists());
            if (!$usado) return $c;
        }
        return (string) random_int(10000000, 99999999);
    }

    public function getUrlCortaAttribute(): ?string
    {
        if (!$this->codigo) return null;
        return rtrim(preg_replace('#^https?://(www\.)?#', '', url('/tv')), '/') . '/' . $this->codigo;
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
