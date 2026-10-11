<?php

namespace App\Http\Controllers;

use App\Models\PagoSuscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Aviso de Wompi cuando cambia el estado de un pago (se verifica la firma con el secreto de eventos)
class wompiController extends Controller
{
    public function evento(Request $request)
    {
        $secreto = config('services.wompi.events_secret');
        $firma = $request->input('signature', []);
        $data = $request->input('data', []);

        $cadena = collect($firma['properties'] ?? [])->map(fn ($p) => (string) Arr::get($data, $p))->implode('')
            . $request->input('timestamp') . $secreto;
        if (!$secreto || !hash_equals((string) ($firma['checksum'] ?? ''), hash('sha256', $cadena))) {
            Log::warning('Wompi: firma inválida', ['ip' => $request->ip()]);
            return response()->json(['ok' => false], 401);
        }

        $tx = $data['transaction'] ?? [];
        DB::transaction(function () use ($tx, $request) {
            $pago = PagoSuscripcion::where('referencia', $tx['reference'] ?? '')->lockForUpdate()->first();
            if (!$pago || $pago->estado === 'aprobado') {
                return;
            }
            // El monto debe coincidir con el plan
            if ((int) ($tx['amount_in_cents'] ?? 0) !== (int) round($pago->monto * 100)) {
                $pago->update(['estado' => 'rechazado', 'nota' => 'Monto no coincide', 'respuesta' => $request->all()]);
                return;
            }
            $pago->update(['respuesta' => $request->all()]);
            match ($tx['status'] ?? '') {
                'APPROVED' => $pago->aprobar(),
                'DECLINED', 'VOIDED', 'ERROR' => $pago->update(['estado' => 'rechazado']),
                default => null,
            };
        });

        return response()->json(['ok' => true]);
    }
}
