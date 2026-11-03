<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Plan de tratamiento (presupuesto) del paciente: procedimientos por pieza, aprobación y abonos.
class TratamientoPlan extends Model
{
    use PerteneceATienda;

    protected $table = 'tratamiento_planes';

    public const ESTADOS = [
        'propuesto' => ['Presupuesto', '#f59e0b'],
        'aprobado' => ['En tratamiento', '#0891b2'],
        'terminado' => ['Terminado', '#16a34a'],
        'cancelado' => ['Cancelado', '#94a3b8'],
    ];

    protected $fillable = ['paciente_id', 'numero', 'nombre', 'estado', 'descuento', 'abonado', 'aprobado_at', 'aprobado_nota', 'notas', 'profesional_id', 'user_id'];

    protected $casts = ['descuento' => 'float', 'abonado' => 'float', 'aprobado_at' => 'datetime'];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class);
    }

    public function items()
    {
        return $this->hasMany(TratamientoItem::class, 'plan_id')->orderBy('orden')->orderBy('id');
    }

    public function abonos()
    {
        return $this->hasMany(TratamientoAbono::class, 'plan_id')->orderByDesc('id');
    }

    public function profesional()
    {
        return $this->belongsTo(User::class, 'profesional_id');
    }

    public function getSubtotalAttribute(): float
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        return round((float) $items->sum(fn ($i) => $i->total), 2);
    }

    public function getTotalAttribute(): float
    {
        return max(0, round($this->subtotal - (float) $this->descuento, 2));
    }

    public function getSaldoAttribute(): float
    {
        return max(0, round($this->total - (float) $this->abonado, 2));
    }

    public function getAvanceAttribute(): int
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->get();
        if ($items->isEmpty()) return 0;
        return (int) round($items->where('estado', 'hecho')->count() * 100 / $items->count());
    }

    public static function siguienteNumero(): string
    {
        $n = (int) static::query()->selectRaw("MAX(CAST(SUBSTRING(numero, 4) AS UNSIGNED)) m")->value('m');
        return 'PT-' . str_pad((string) ($n + 1), 5, '0', STR_PAD_LEFT);
    }
}
