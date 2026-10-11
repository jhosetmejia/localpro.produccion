<?php

namespace App\Models\Conjunto;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

class Unidad extends Model
{
    use PerteneceATienda;

    protected $table = 'unidades';
    protected $fillable = ['torre', 'numero', 'tipo', 'coeficiente', 'cuota', 'propietario', 'propietario_tel', 'propietario_email', 'propietario_doc', 'residente', 'residente_tel', 'notas', 'activa'];
    protected $casts = ['activa' => 'boolean', 'cuota' => 'float', 'coeficiente' => 'float'];

    public const TIPOS = ['apartamento' => 'Apartamento', 'casa' => 'Casa', 'local' => 'Local', 'parqueadero' => 'Parqueadero', 'otro' => 'Otro'];

    public function cargos() { return $this->hasMany(Cargo::class); }
    public function pagos() { return $this->hasMany(PagoConjunto::class); }

    public function getNombreAttribute(): string
    {
        return trim(($this->torre ? $this->torre . ' · ' : '') . (self::TIPOS[$this->tipo] ?? 'Unidad') . ' ' . $this->numero);
    }

    public function getContactoAttribute(): ?string
    {
        return $this->residente_tel ?: $this->propietario_tel;
    }

    public function getPersonaAttribute(): ?string
    {
        return $this->residente ?: $this->propietario;
    }

    // Saldo pendiente (cargos no pagados)
    public function saldo(): float
    {
        return (float) $this->cargos()->whereIn('estado', ['pendiente', 'parcial'])->selectRaw('COALESCE(SUM(valor - pagado),0) s')->value('s');
    }
}
