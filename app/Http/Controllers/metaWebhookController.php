<?php

namespace App\Http\Controllers;

use App\Support\Meta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// Avisos en tiempo real de Facebook e Instagram (mensajes nuevos)
class metaWebhookController extends Controller
{
    // Meta verifica la URL una sola vez al configurar el webhook
    public function verificar(Request $request)
    {
        $token = (string) config('services.meta.verify_token');
        if ($request->query('hub_mode') === 'subscribe' && $token !== '' && hash_equals($token, (string) $request->query('hub_verify_token'))) {
            return response((string) $request->query('hub_challenge'), 200)->header('Content-Type', 'text/plain');
        }
        return response('No autorizado', 403);
    }

    public function evento(Request $request)
    {
        $cuerpo = $request->getContent();
        if (!Meta::firmaValida($cuerpo, $request->header('X-Hub-Signature-256'))) {
            Log::warning('Meta webhook: firma inválida');
            return response('Firma inválida', 403);
        }
        try {
            Meta::procesarEvento(json_decode($cuerpo, true) ?: []);
        } catch (\Throwable $e) {
            report($e);
        }
        return response('EVENT_RECEIVED', 200);
    }
}
