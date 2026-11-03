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

    protected $fillable = ['paciente_id', 'numero', 'nombre', 'estado', 'descuento', 'abonado', 'aprobado_at', 'aprobado_nota', 'notas', 'profesional_id', 'user_id', 'tipo', 'cuota_inicial', 'cuota_mensual', 'cuotas', 'inicio'];

    protected $casts = ['descuento' => 'float', 'abonado' => 'float', 'aprobado_at' => 'datetime', 'cuota_inicial' => 'float', 'cuota_mensual' => 'float', 'inicio' => 'date'];

    public function controles()
    {
        return $this->hasMany(OrtodonciaControl::class, 'plan_id')->orderByDesc('fecha')->orderByDesc('id');
    }

    public function getEsOrtodonciaAttribute(): bool
    {
        return $this->tipo === 'ortodoncia';
    }

    // Ortodoncia: lo que ya debería haber pagado a hoy (cuota inicial + una cuota por cada mes que empezó desde el inicio)
    public function getDeberiaLlevarAttribute(): float
    {
        if (!$this->es_ortodoncia || !$this->inicio || $this->estado !== 'aprobado') return 0;
        $meses = $this->inicio->gt(today()) ? 0 : $this->inicio->diffInMonths(today()) + 1;
        if ($this->cuotas > 0) $meses = min($meses, (int) $this->cuotas);
        return min($this->total, round((float) $this->cuota_inicial + $meses * (float) $this->cuota_mensual, 2));
    }

    public function getAtrasoAttribute(): float
    {
        return max(0, round($this->deberia_llevar - (float) $this->abonado, 2));
    }

    // Cuotas sin pagar a hoy (la cuota inicial pendiente cuenta como una)
    public function getCuotasAtrasadasAttribute(): int
    {
        if ($this->atraso <= 0) return 0;
        $inicialPendiente = max(0, (float) $this->cuota_inicial - (float) $this->abonado);
        $mensual = max(0, $this->atraso - $inicialPendiente);
        return ($inicialPendiente > 0 ? 1 : 0) + ($this->cuota_mensual > 0 ? (int) ceil($mensual / $this->cuota_mensual - 0.001) : 0);
    }

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
