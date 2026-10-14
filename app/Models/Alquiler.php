<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Servicio que el negocio pone en alquiler: qué se alquila, fecha, hora, lugar, responsable y valor
class Alquiler extends Model
{
    use PerteneceATienda;

    protected $table = 'alquileres';

    protected $fillable = ['numero', 'que_alquila', 'fecha', 'hora', 'hora_fin', 'lugar', 'responsable_telefono', 'encargado_nombre', 'encargado_telefono', 'encargado_valor', 'nota', 'valor', 'con_comision', 'comision', 'estado', 'user_id', 'pos_venta_id', 'cobrado_en', 'abonado', 'entrega', 'entregado_en', 'devuelto_en', 'devolucion_estado', 'devolucion_nota'];

    protected $casts = ['fecha' => 'date', 'valor' => 'float', 'comision' => 'float', 'con_comision' => 'boolean', 'cobrado_en' => 'datetime', 'abonado' => 'float', 'encargado_valor' => 'float', 'entregado_en' => 'datetime', 'devuelto_en' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function venta() { return $this->belongsTo(PosVenta::class, 'pos_venta_id'); }
    public function abonos() { return $this->hasMany(AlquilerAbono::class)->orderBy('id'); }

    // Lo que falta por pagar
    public function getSaldoAttribute(): float
    {
        return max(0, round($this->valor - $this->abonado, 2));
    }

    public function getPagadoAttribute(): bool
    {
        return $this->saldo <= 0.009;
    }

    // Estado de lo alquilado: por entregar, en manos del cliente o devuelto
    public function getEntregaTextoAttribute(): string
    {
        return match ($this->entrega) {
            'alquilado' => '🔑 Alquilado · lo tiene el cliente',
            'devuelto' => $this->devolucion_estado === 'con_novedad' ? '⚠️ Devuelto con novedad' : '✅ Devuelto en orden',
            default => '📦 Por entregar',
        };
    }

    // Lo que cuesta prestar el servicio: comisión (si hay) + turno del encargado
    public function getCostoServicioAttribute(): float
    {
        return round(($this->con_comision ? $this->comision : 0) + ($this->encargado_nombre ? (float) $this->encargado_valor : 0), 2);
    }

    // Lo que le queda al local: valor del alquiler menos la comisión y el turno del encargado
    public function getGananciaAttribute(): float
    {
        return round($this->valor - $this->costo_servicio, 2);
    }

    public function getEncargadoWaAttribute(): ?string
    {
        return $this->encargado_telefono ? numero_whatsapp($this->encargado_telefono) : null;
    }

    // Mensaje de WhatsApp para el encargado con los detalles del evento
    public function mensajeEncargado(string $negocio): string
    {
        $llegada = $this->hora ? \Carbon\Carbon::parse($this->hora)->subHour()->format('g:i a') : null;
        $l = ['Hola ' . $this->encargado_nombre . ' 👋 Le comparto los detalles del evento (' . $this->numero . '):', '',
            '📦 *Servicio:* ' . $this->que_alquila,
            '📅 *Fecha:* ' . $this->fecha?->format('d/m/Y'),
            '🕐 *Horario:* ' . $this->horario_texto,
            '📍 *Lugar:* ' . $this->lugar,
            '📞 *Cliente:* ' . $this->responsable_telefono];
        if ($this->encargado_valor > 0) $l[] = '💵 *Valor de su turno:* ' . dinero($this->encargado_valor);
        if ($this->nota) $l[] = '📝 *Nota:* ' . $this->nota;
        $l[] = '';
        $l[] = '⚠️ Por favor esté *1 hora antes*' . ($llegada ? ' (' . $llegada . ')' : '') . ' para evitar retrasos por el tráfico.';
        $l[] = '— ' . $negocio;
        return implode("\n", $l);
    }

    public function getCobradoAttribute(): bool
    {
        return (bool) $this->pos_venta_id;
    }

    public function getHoraTextoAttribute(): string
    {
        return $this->hora ? \Carbon\Carbon::parse($this->hora)->format('g:i a') : '';
    }

    public function getHoraFinTextoAttribute(): string
    {
        return $this->hora_fin ? \Carbon\Carbon::parse($this->hora_fin)->format('g:i a') : '';
    }

    // "1:00 pm a 6:00 pm" (o solo la de inicio si no hay hora de fin)
    public function getHorarioTextoAttribute(): string
    {
        return $this->hora_fin ? $this->hora_texto . ' a ' . $this->hora_fin_texto : $this->hora_texto;
    }

    // Detalle para la factura: fecha, horario y lugar del servicio
    public function getDetalleFacturaAttribute(): string
    {
        return 'Servicio el ' . $this->fecha?->format('d/m/Y') . ', ' . $this->horario_texto . ' · ' . $this->lugar
            . ($this->encargado_nombre ? ' · Encargado: ' . $this->encargado_nombre : '');
    }

    public function getVencidoAttribute(): bool
    {
        return $this->estado === 'programado' && $this->fecha && $this->fecha->lt(today());
    }

    public function getTelefonoWaAttribute(): ?string
    {
        return $this->responsable_telefono ? numero_whatsapp($this->responsable_telefono) : null;
    }

    // Siguiente número ALQ-000001 de la tienda actual (incluye los borrados para no repetir)
    public static function siguienteNumero(): string
    {
        $ultimo = (int) substr((string) static::query()->orderByDesc('id')->value('numero'), 4);
        return 'ALQ-' . str_pad((string) ($ultimo + 1), 6, '0', STR_PAD_LEFT);
    }
}
