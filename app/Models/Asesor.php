<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Persona de la red comercial: asesor, líder de ciudad o líder de departamento
class Asesor extends Model
{
    protected $table = 'asesores';

    protected $fillable = ['user_id', 'cargo', 'padre_id', 'pais', 'departamento', 'ciudad', 'codigo', 'documento', 'banco', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public const CARGOS = [
        'lider_pais' => ['Líder de país', 'fa-earth-americas', 4],
        'lider_departamento' => ['Líder de departamento', 'fa-flag', 3],
        'lider_ciudad' => ['Líder de ciudad', 'fa-city', 2],
        'asesor' => ['Asesor', 'fa-user-tie', 1],
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function padre()
    {
        return $this->belongsTo(Asesor::class, 'padre_id');
    }

    public function hijos()
    {
        return $this->hasMany(Asesor::class, 'padre_id');
    }

    public function tiendas()
    {
        return $this->hasMany(Tienda::class, 'asesor_id');
    }

    public function comisiones()
    {
        return $this->hasMany(Comision::class);
    }

    public function getNombreAttribute(): string
    {
        return $this->user->name ?? 'Sin nombre';
    }

    public function getCargoNombreAttribute(): string
    {
        return self::CARGOS[$this->cargo][0] ?? 'Asesor';
    }

    public function getCargoIconoAttribute(): string
    {
        return self::CARGOS[$this->cargo][1] ?? 'fa-user-tie';
    }

    public function getNivelAttribute(): int
    {
        return self::CARGOS[$this->cargo][2] ?? 1;
    }

    public function getEnlaceAttribute(): string
    {
        return url('/r/' . $this->codigo);
    }

    // Quién cobra cada parte cuando este asesor vende: el que vende cobra también las partes de su cargo y de los cargos de abajo
    public function lideres(): array
    {
        $orden = ['lider_ciudad' => 2, 'lider_departamento' => 3, 'lider_pais' => 4];
        $r = ['ciudad' => null, 'departamento' => null, 'pais' => null];
        $mapa = ['lider_ciudad' => 'ciudad', 'lider_departamento' => 'departamento', 'lider_pais' => 'pais'];
        foreach ($orden as $cargo => $nivel) {
            if ($this->nivel >= $nivel) $r[$mapa[$cargo]] = $this;
        }
        $n = $this->padre;
        $vistos = [$this->id => true];
        while ($n && !isset($vistos[$n->id])) {
            $vistos[$n->id] = true;
            $clave = $mapa[$n->cargo] ?? null;
            if ($clave && !$r[$clave] && $n->nivel > $this->nivel) {
                $r[$clave] = $n->activo ? $n : false; // inactivo: esa parte queda para LocalPro
            }
            $n = $n->padre;
        }
        return array_map(fn ($x) => $x ?: null, $r);
    }

    // Ids de él y de toda la gente que tiene debajo
    public function idsRed(): array
    {
        $ids = [$this->id];
        $nivel = [$this->id];
        for ($i = 0; $i < 6 && $nivel; $i++) {
            $nivel = static::whereIn('padre_id', $nivel)->pluck('id')->diff($ids)->all();
            $ids = array_merge($ids, $nivel);
        }
        return $ids;
    }

    public static function nuevoCodigo(string $nombre): string
    {
        $base = Str::upper(Str::limit(preg_replace('/[^A-Za-z]/', '', Str::ascii(strtok($nombre, ' ') ?: 'LP')), 6, ''));
        do {
            $c = ($base ?: 'LP') . random_int(100, 999);
        } while (static::where('codigo', $c)->exists());
        return $c;
    }
}
