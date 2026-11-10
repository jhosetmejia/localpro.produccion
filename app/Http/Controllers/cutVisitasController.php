<?php

namespace App\Http\Controllers;

use App\Models\CutEvento;
use App\Models\CutVisita;
use App\Support\AgenteUsuario;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Contador de LocalProCut: cada pestaña abierta es una visita; el navegador avisa lo que hace la persona
 * (agregar videos, exportar, compartir, instalar la app, registrarse) y un "latido" cada minuto mientras la ve.
 * No se guarda ningún video ni nombre de archivo. La IP se borra apenas se ubica la ciudad.
 */
class cutVisitasController extends Controller
{
    private const EVENTOS = ['agrego', 'exportar', 'exporto', 'exporto_falla', 'subtitulos', 'compartio_wa', 'compartio', 'compartio_video', 'descargo_video',
        'clic_registro', 'clic_app', 'instalo', 'clic_funciones', 'muro_registro', 'opino', 'clic_programar'];

    public function visita(Request $request)
    {
        $d = $request->validate([
            'visitante' => 'required|string|regex:/^[a-z0-9]{8,32}$/',
            'nuevo' => 'nullable|boolean',
            'app' => 'nullable|boolean',
            'pantalla' => 'nullable|string|max:40',
            'idioma' => 'nullable|string|max:20',
            'referencia' => 'nullable|string|max:500',
            'origen' => 'nullable|string|max:60',
            'invito' => 'nullable|string|max:32',
        ]);
        $ua = (string) $request->userAgent();
        $ag = AgenteUsuario::analizar($ua);
        if ($ag['es_bot']) return response()->json(['ok' => true, 'id' => null]);

        $ref = $d['referencia'] ?? null;
        $host = $ref ? parse_url($ref, PHP_URL_HOST) : null;
        // Las visitas que vienen de otra página de LocalProo no son "referencia externa"
        $host = $host && !str_ends_with($host, (string) parse_url(config('app.url'), PHP_URL_HOST)) ? Str::limit($host, 110, '') : ($host ? 'localproo' : null);

        $v = CutVisita::create([
            'visitante' => $d['visitante'],
            'nuevo' => (bool) ($d['nuevo'] ?? true),
            'user_id' => $request->user()?->id,
            'ip' => $request->ip(),
            'ip_hash' => hash('sha256', $request->ip() . config('app.key')),
            'dispositivo' => $ag['dispositivo'], 'sistema' => $ag['sistema'], 'navegador' => $ag['navegador'],
            'app_origen' => $ag['app'], 'marca' => $ag['marca_equipo'],
            'es_app' => (bool) ($d['app'] ?? false),
            'pantalla' => $d['pantalla'] ?? null,
            'idioma' => $d['idioma'] ?? null,
            'referencia' => $host,
            'origen' => isset($d['origen']) ? (Str::slug($d['origen']) ?: null) : null,
            'invito' => isset($d['invito']) && preg_match('/^[a-z0-9]{8,32}$/', $d['invito']) && $d['invito'] !== $d['visitante'] ? $d['invito'] : null,
            'ultimo_evento' => 'abrio',
            'ultima_vez' => now(),
        ]);
        CutEvento::create(['visita_id' => $v->id, 'tipo' => 'abrio', 'created_at' => now()]);
        return response()->json(['ok' => true, 'id' => $v->id, 'firma' => $this->firma($v->id)]);
    }

    public function evento(Request $request)
    {
        $d = $request->validate([
            'id' => 'required|integer',
            'firma' => 'required|string|max:64',
            'tipo' => 'required|string|in:' . implode(',', array_merge(self::EVENTOS, ['latido'])),
            'datos' => 'nullable|array',
            'segundos' => 'nullable|integer|min:0|max:600',
        ]);
        // La firma evita que alguien sume eventos a visitas ajenas cambiando el número
        if (!hash_equals($this->firma((int) $d['id']), $d['firma'])) return response()->json(['ok' => false], 403);
        $v = CutVisita::find($d['id']);
        if (!$v) return response()->json(['ok' => false], 404);

        $cambios = ['ultima_vez' => now()];
        if (!empty($d['segundos'])) $cambios['segundos'] = min(86400, $v->segundos + (int) $d['segundos']);
        if (!$v->user_id && $request->user()) $cambios['user_id'] = $request->user()->id;
        $t = $d['tipo'];
        if ($t !== 'latido') {
            $cambios['ultimo_evento'] = $t;
            $n = (int) ($d['datos']['n'] ?? 1);
            match ($t) {
                'agrego' => $cambios['videos'] = min(60000, $v->videos + max(1, min(50, $n))),
                'exporto' => $cambios['exportados'] = min(60000, $v->exportados + 1),
                'compartio_wa', 'compartio', 'compartio_video' => $cambios['compartidos'] = min(60000, $v->compartidos + 1),
                'clic_registro', 'clic_programar' => $cambios['clic_registro'] = true,
                'clic_app' => $cambios['clic_app'] = true,
                'instalo' => $cambios['instalo'] = true,
                default => null,
            };
            // Solo datos simples y cortos (resolución, fps, duración…): nada de textos largos
            $datos = collect($d['datos'] ?? [])->filter(fn ($x) => is_scalar($x) || $x === null)->map(fn ($x) => is_string($x) ? Str::limit($x, 80, '') : $x)->take(12)->all();
            CutEvento::create(['visita_id' => $v->id, 'tipo' => $t, 'datos' => $datos ?: null, 'created_at' => now()]);
        }
        $v->forceFill($cambios)->save();
        return response()->json(['ok' => true]);
    }

    private function firma(int $id): string
    {
        return substr(hash_hmac('sha256', 'cut-visita:' . $id, (string) config('app.key')), 0, 24);
    }
}
