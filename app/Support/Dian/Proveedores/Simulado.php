<?php

namespace App\Support\Dian\Proveedores;

use App\Models\DocumentoElectronico;
use App\Support\Dian\Cufe;
use App\Support\Dian\Validador;

// Modo de práctica: arma el documento, lo valida como lo haría la DIAN y calcula el CUFE/CUDE,
// pero NO lo envía. Sirve para probar todo el flujo mientras se contrata el proveedor.
class Simulado implements Proveedor
{
    public function nombre(): string
    {
        return 'simulado';
    }

    public function enviar(DocumentoElectronico $doc, array $payload): array
    {
        $errores = Validador::errores($payload);
        if ($errores) {
            return ['estado' => 'rechazado', 'mensaje' => implode(' · ', $errores), 'respuesta' => ['errores' => $errores]];
        }
        $cufe = Cufe::calcular([
            'numero' => $payload['numero'], 'fecha' => $payload['fecha'], 'hora' => $payload['hora'],
            'subtotal' => $payload['totales']['base'], 'iva' => $payload['totales']['iva'], 'inc' => $payload['totales']['inc'],
            'total' => $payload['totales']['total'], 'nit_emisor' => $payload['emisor']['nit'],
            'doc_adquiriente' => $payload['adquiriente']['numero'], 'clave' => $payload['clave'] ?? 'SIMULADO', 'ambiente' => 'pruebas',
        ]);
        return [
            'estado' => 'simulado', 'cufe' => $cufe, 'qr' => Cufe::urlConsulta($cufe, 'pruebas'),
            'mensaje' => 'Prueba interna correcta: el documento cumple la estructura. No fue enviado a la DIAN.',
            'respuesta' => ['simulado' => true, 'fecha' => now()->toIso8601String()],
        ];
    }

    public function consultar(DocumentoElectronico $doc): ?array
    {
        return null;
    }
}
