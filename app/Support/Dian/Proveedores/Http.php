<?php

namespace App\Support\Dian\Proveedores;

use App\Models\DocumentoElectronico;
use Illuminate\Support\Facades\Http as Cliente;

/**
 * Conector genérico por API: envía el documento de LocalPro en JSON a la dirección del proveedor (o a un puente)
 * con su token. Espera una respuesta JSON con: estado, cufe, qr, pdf_url, xml_url, mensaje.
 * Cuando se elija el proveedor definitivo se ajusta aquí el formato exacto de su API.
 */
class Http implements Proveedor
{
    public function __construct(private string $url, private string $token, private array $credenciales = [])
    {
    }

    public function nombre(): string
    {
        return 'api';
    }

    public function enviar(DocumentoElectronico $doc, array $payload): array
    {
        $r = Cliente::timeout(40)->acceptJson()->withToken($this->token)
            ->post(rtrim($this->url, '/') . '/documentos', ['credenciales' => $this->credenciales, 'documento' => $payload]);
        $j = $r->json() ?? [];
        if (!$r->successful()) {
            return ['estado' => $r->status() >= 500 ? 'error' : 'rechazado', 'mensaje' => $j['mensaje'] ?? $j['message'] ?? ('El proveedor respondió ' . $r->status()), 'respuesta' => $j];
        }
        return [
            'estado' => in_array($j['estado'] ?? '', ['aceptado', 'enviado', 'rechazado'], true) ? $j['estado'] : 'enviado',
            'cufe' => $j['cufe'] ?? $j['cude'] ?? null, 'qr' => $j['qr'] ?? null, 'pdf_url' => $j['pdf_url'] ?? null,
            'xml_url' => $j['xml_url'] ?? null, 'mensaje' => $j['mensaje'] ?? null, 'respuesta' => $j,
        ];
    }

    public function consultar(DocumentoElectronico $doc): ?array
    {
        if (!$doc->cufe) return null;
        $r = Cliente::timeout(20)->acceptJson()->withToken($this->token)->get(rtrim($this->url, '/') . '/documentos/' . $doc->cufe);
        return $r->successful() ? ($r->json() ?? null) : null;
    }
}
