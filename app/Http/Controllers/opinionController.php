<?php

namespace App\Http\Controllers;

use App\Models\ErrorCliente;
use App\Models\Opinion;
use App\Support\TiendaActual;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Botón "Opinar / reportar falla" de todo LocalPro (panel y LocalProCut) y errores del navegador que se reportan solos.
 * Lo ve el súper administrador en Admin → Opiniones y fallas; cada persona ve las suyas (con nuestra respuesta) en "Mis reportes".
 */
class opinionController extends Controller
{
    public function guardar(Request $request)
    {
        // Trampa para robots: un campo que la gente no ve
        if (filled($request->input('web'))) return response()->json(['ok' => true]);

        $d = $request->validate([
            'tipo' => 'required|in:' . implode(',', array_keys(Opinion::TIPOS)),
            'mensaje' => 'required|string|min:4|max:3000',
            'modulo' => 'nullable|string|max:40',
            'pagina' => 'nullable|string|max:300',
            'contacto' => 'nullable|string|max:120',
            'datos' => 'nullable|string|max:30000',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:6144',
        ], [
            'mensaje.required' => 'Cuéntenos qué pasó.',
            'mensaje.min' => 'Escriba un poquito más para entenderle.',
            'mensaje.max' => 'El mensaje es muy largo (máximo 3.000 letras).',
            'foto.image' => 'La captura debe ser una imagen.',
            'foto.mimes' => 'La captura debe ser JPG, PNG o WebP.',
            'foto.max' => 'La captura pesa mucho (máximo 6 MB).',
        ]);

        $user = $request->user();
        $op = new Opinion([
            'tipo' => $d['tipo'],
            'mensaje' => trim($d['mensaje']),
            'modulo' => $this->modulo($d['modulo'] ?? null),
            'pagina' => $this->pagina($d['pagina'] ?? null),
            'user_id' => $user?->id,
            'tienda_id' => $user ? TiendaActual::id() : null,
            'contacto' => isset($d['contacto']) ? (trim(strip_tags($d['contacto'])) ?: null) : null,
            'datos' => $this->datos($d['datos'] ?? null, $request),
            'estado' => 'nuevo',
            'ip_hash' => hash('sha256', $request->ip() . config('app.key')),
        ]);
        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $op->foto = $request->file('foto')->store('opiniones/' . now()->format('Y-m'), 'local');
        }
        $op->save();

        return response()->json([
            'ok' => true,
            'id' => $op->id,
            'mensaje' => $op->tipo === 'falla'
                ? '¡Gracias! Ya nos llegó. Lo revisamos y lo arreglamos lo antes posible.'
                : '¡Gracias por la idea! Nos ayuda a mejorar LocalPro.',
        ]);
    }

    // Errores de JavaScript que se reportan solos: se agrupan por firma (mismo mensaje y lugar)
    public function error(Request $request)
    {
        $d = $request->validate([
            'mensaje' => 'required|string|max:2000',
            'donde' => 'nullable|string|max:300',
            'pila' => 'nullable|string|max:6000',
            'modulo' => 'nullable|string|max:40',
            'pagina' => 'nullable|string|max:300',
            'datos' => 'nullable|string|max:20000',
        ]);
        $mensaje = Str::limit(trim($d['mensaje']), 480, '…');
        $donde = $this->limpiarDonde($d['donde'] ?? null);
        $modulo = $this->modulo($d['modulo'] ?? null);
        // Los números cambiantes (ids, tiempos) no deben partir el mismo error en muchos
        $firma = sha1($modulo . '|' . preg_replace('/\d+(\.\d+)?/', '#', $mensaje) . '|' . preg_replace('/\?v=\d+/', '', (string) $donde));
        $ahora = now();
        $datos = $this->datos($d['datos'] ?? null, $request);
        $ua = Str::limit((string) $request->userAgent(), 290, '');

        $e = ErrorCliente::where('firma', $firma)->first();
        if ($e) {
            // Persona distinta (aprox.): misma IP + navegador cuenta una vez por día
            $nueva = Cache::add('errcli_' . $firma . '_' . md5($request->ip() . $ua), 1, now()->endOfDay());
            $e->forceFill([
                'veces' => min(4000000000, $e->veces + 1),
                'usuarios' => $e->usuarios + ($nueva ? 1 : 0),
                'ultima_vez' => $ahora,
                'pagina' => $this->pagina($d['pagina'] ?? null) ?: $e->pagina,
                'navegador' => $ua ?: $e->navegador,
                'datos' => $datos ?: $e->datos,
                // Si vuelve a pasar después de marcarlo resuelto, se reabre
                'resuelto' => false,
            ])->save();
        } else {
            Cache::add('errcli_' . $firma . '_' . md5($request->ip() . $ua), 1, now()->endOfDay());
            ErrorCliente::create([
                'firma' => $firma, 'modulo' => $modulo, 'mensaje' => $mensaje, 'donde' => $donde,
                'pila' => isset($d['pila']) ? Str::limit($d['pila'], 5800, '…') : null,
                'pagina' => $this->pagina($d['pagina'] ?? null), 'navegador' => $ua, 'datos' => $datos,
                'veces' => 1, 'usuarios' => 1, 'primera_vez' => $ahora, 'ultima_vez' => $ahora,
            ]);
        }
        return response()->json(['ok' => true]);
    }

    // "Mis reportes": lo que la persona nos ha escrito y lo que le contestamos
    public function mias(Request $request)
    {
        $lista = Opinion::where('user_id', $request->user()->id)->latest()->limit(15)
            ->get(['id', 'tipo', 'mensaje', 'estado', 'respuesta', 'respondido_at', 'created_at']);
        return response()->json($lista->map(fn ($o) => [
            'tipo' => Opinion::ICONOS[$o->tipo] ?? '💬',
            'mensaje' => Str::limit($o->mensaje, 160),
            'estado' => Opinion::ESTADOS[$o->estado] ?? $o->estado,
            'resuelto' => $o->estado === 'resuelto',
            'respuesta' => $o->respuesta,
            'fecha' => $o->created_at?->format('d/m/Y'),
        ]));
    }

    private function modulo(?string $m): string
    {
        $m = Str::slug((string) $m);
        return $m !== '' ? Str::limit($m, 40, '') : 'panel';
    }

    // Solo la ruta (sin el dominio ni datos de la consulta, que pueden traer tokens)
    private function pagina(?string $p): ?string
    {
        if (!$p) return null;
        $ruta = parse_url($p, PHP_URL_PATH) ?: $p;
        return Str::limit('/' . ltrim($ruta, '/'), 290, '');
    }

    private function limpiarDonde(?string $d): ?string
    {
        if (!$d) return null;
        $d = preg_replace('#^https?://[^/]+#', '', $d);
        return Str::limit($d, 290, '');
    }

    // Datos técnicos que manda el navegador (pantalla, errores recientes, estado del editor). Solo se guarda JSON válido.
    private function datos(?string $json, Request $request): ?array
    {
        $x = $json ? json_decode($json, true) : null;
        if (!is_array($x)) $x = [];
        $x['navegador'] = Str::limit((string) $request->userAgent(), 290, '');
        return $x;
    }
}
