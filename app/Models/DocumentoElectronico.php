<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Model;

// Factura electrónica, documento equivalente POS electrónico o nota crédito enviada a la DIAN por el proveedor
class DocumentoElectronico extends Model
{
    use PerteneceATienda;

    protected $table = 'documentos_electronicos';

    protected $fillable = [
        'tienda_id', 'tipo', 'pos_venta_id', 'devolucion_id', 'referencia_id', 'numero', 'prefijo', 'consecutivo', 'cufe', 'estado',
        'ambiente', 'proveedor', 'total', 'intentos', 'ultimo_error', 'qr', 'pdf_url', 'xml_url', 'payload', 'respuesta', 'enviado_en', 'validado_en',
    ];

    protected $casts = ['payload' => 'array', 'respuesta' => 'array', 'enviado_en' => 'datetime', 'validado_en' => 'datetime'];

    public const TIPOS = ['fe' => 'Factura electrónica', 'pos_e' => 'Documento equivalente POS electrónico', 'nc' => 'Nota crédito electrónica'];

    public const ESTADOS = [
        'pendiente' => ['Pendiente de envío', '#92600a', '#fef3c7'],
        'enviado' => ['Enviado, esperando DIAN', '#1d4ed8', '#dbeafe'],
        'aceptado' => ['Aceptado por la DIAN', '#11702f', '#e8f8ec'],
        'rechazado' => ['Rechazado por la DIAN', '#b42318', '#fee4e2'],
        'error' => ['Error al enviar (se reintenta)', '#b42318', '#fee4e2'],
        'simulado' => ['Prueba interna (sin validez DIAN)', '#5b665e', '#eef2ef'],
    ];

    public function venta()
    {
        return $this->belongsTo(PosVenta::class, 'pos_venta_id');
    }

    public function referencia()
    {
        return $this->belongsTo(self::class, 'referencia_id');
    }

    public function getNombreTipoAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function getEstadoInfoAttribute(): array
    {
        return self::ESTADOS[$this->estado] ?? [$this->estado, '#5b665e', '#eef2ef'];
    }

    // Ya salió hacia la DIAN (o quedó en firme): no se puede borrar ni anular la venta, solo corregir con nota crédito
    public function getEmitidoAttribute(): bool
    {
        return in_array($this->estado, ['enviado', 'aceptado', 'pendiente', 'error', 'simulado'], true);
    }
}
