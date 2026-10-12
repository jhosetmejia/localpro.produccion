<?php

namespace App\Support\Dian\Proveedores;

use App\Models\DocumentoElectronico;

/**
 * Proveedor tecnológico autorizado por la DIAN. Cada proveedor (el que se contrate) se conecta escribiendo
 * una clase que reciba el documento ya armado por LocalPro (payload) y lo convierta a su API.
 *
 * Debe devolver: ['estado' => aceptado|enviado|rechazado|error|simulado, 'cufe' => ?, 'qr' => ?, 'pdf_url' => ?,
 *                 'xml_url' => ?, 'mensaje' => ?, 'respuesta' => array]
 */
interface Proveedor
{
    public function nombre(): string;

    public function enviar(DocumentoElectronico $doc, array $payload): array;

    // Consulta el estado de un documento que quedó "enviado" (la DIAN a veces responde después)
    public function consultar(DocumentoElectronico $doc): ?array;
}
