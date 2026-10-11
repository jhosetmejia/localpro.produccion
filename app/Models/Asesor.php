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

    // Quién cobra la parte de líder de ciudad y de departamento cuando este asesor vende
    public function lideres(): array
    {
        if ($this->cargo === 'lider_departamento') {
            return ['ciudad' => $this, 'departamento' => $this]; // vende el líder de depto: cobra las tres partes
        }
        $ciudad = $this->cargo === 'lider_ciudad' ? $this : null;
        $depto = null;
        $n = $this->padre;
        $vistos = [$this->id => true];
        while ($n && !isset($vistos[$n->id])) {
            $vistos[$n->id] = true;
            if ($n->cargo === 'lider_departamento') {
                $depto = $n->activo ? $n : null;
                break; // arriba del líder de departamento ya no hay más cargos
            }
            if (!$ciudad && $n->cargo === 'lider_ciudad' && $n->activo) $ciudad = $n;
            $n = $n->padre;
        }
        return ['ciudad' => $ciudad, 'departamento' => $depto];
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
