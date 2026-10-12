<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Persona de la red comercial: asesor, líder de ciudad o líder de departamento
class Asesor extends Model
{
    protected $table = 'asesores';

    protected $fillable = ['user_id', 'cargo', 'padre_id', 'pais', 'departamento', 'ciudad', 'zona', 'codigo', 'documento', 'banco', 'activo', 'estado', 'reclutado_por', 'aprobado_at', 'curso_modulos', 'curso_nota', 'curso_aprobado_at', 'certificado'];

    protected $casts = ['activo' => 'boolean', 'aprobado_at' => 'datetime', 'curso_modulos' => 'array', 'curso_aprobado_at' => 'datetime'];

    public const CARGOS = [
        'lider_pais' => ['Líder de país', 'fa-earth-americas', 5],
        'lider_departamento' => ['Líder de departamento', 'fa-flag', 4],
        'lider_ciudad' => ['Líder de ciudad', 'fa-city', 3],
        'lider_zona' => ['Líder de zona', 'fa-map-pin', 2],
        'asesor' => ['Asesor', 'fa-user-tie', 1],
    ];

    // Un líder de zona (comuna) maneja máximo esta cantidad de asesores
    public const MAX_ASESORES_ZONA = 10;

    public const CLAVES = [
        'lider_zona' => 'zona', 'lider_ciudad' => 'ciudad', 'lider_departamento' => 'departamento', 'lider_pais' => 'pais',
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

    public function reclutador()
    {
        return $this->belongsTo(Asesor::class, 'reclutado_por');
    }

    public function reclutados()
    {
        return $this->hasMany(Asesor::class, 'reclutado_por');
    }

    public function getEnlaceReclutarAttribute(): string
    {
        return url('/unete/' . $this->codigo);
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
        $mapa = self::CLAVES;
        $r = array_fill_keys(array_values($mapa), null);
        foreach ($mapa as $cargo => $clave) {
            if ($this->nivel >= self::CARGOS[$cargo][2]) $r[$clave] = $this;
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

    // Código consecutivo de asesor: LCP-00001, LCP-00002…
    public static function nuevoCodigo(?string $nombre = null): string
    {
        $max = (int) static::where('codigo', 'like', 'LCP-%')->selectRaw("MAX(CAST(SUBSTRING(codigo, 5) AS UNSIGNED)) m")->value('m');
        do {
            $c = 'LCP-' . str_pad((string) ++$max, 5, '0', STR_PAD_LEFT);
        } while (static::where('codigo', $c)->exists());
        return $c;
    }

    /** Busca por código nuevo (LCP-00012, lcp12, 12) o por el código viejo (ELIANA723) */
    public function scopePorCodigo($q, ?string $codigo)
    {
        $c = strtoupper(trim((string) $codigo));
        if ($c === '') return $q->whereRaw('1 = 0');
        if (preg_match('/^(?:LCP)?-?0*(\d{1,7})$/', $c, $m)) {
            $c = 'LCP-' . str_pad($m[1], 5, '0', STR_PAD_LEFT);
        }
        return $q->where(fn ($w) => $w->where('codigo', $c)->orWhere('codigo_anterior', $c));
    }
}
