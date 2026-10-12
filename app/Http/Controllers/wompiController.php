<?php

namespace App\Http\Controllers;

use App\Support\Wompi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// Aviso de Wompi cuando cambia el estado de un pago (se verifica la firma con el secreto de eventos)
class wompiController extends Controller
{
    public function evento(Request $request)
    {
        $evento = $request->all();
        if (!Wompi::eventoValido($evento)) {
            Log::warning('Wompi: firma inválida', ['ip' => $request->ip()]);
            return response()->json(['ok' => false], 401);
        }
        if (data_get($evento, 'event') === 'transaction.updated' && is_array($tx = data_get($evento, 'data.transaction'))) {
            Wompi::procesar($tx);
        }
        return response()->json(['ok' => true]);
    }
}
