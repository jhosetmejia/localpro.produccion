<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * KIT ASISTENTE IA (Jhoset Mejia - DiCreWeb, 2026-11-13). IA GRATIS (Groq) que charla, conoce a quien escribe,
 * guarda las conversaciones y APRENDE de lo que el admin le ensena. Personalizar SOLO las 4 secciones marcadas con 👉. Sin llave o si falla, la
 * pagina sigue con las respuestas por botones / palabras clave y el WhatsApp.
 */
class AsistenteController extends Controller
{
    // 👉 1) NOMBRE Y WHATSAPP DEL NEGOCIO
    private const NEGOCIO = 'LocalProo';
    private const WHATSAPP = ''; // LocalProo: se toma de Admin → Planes y cobros (ajuste soporte_whatsapp), ver wa()

    // 👉 2) COMO LLEVAR LA CHARLA (meta: que la gente use el sistema)
    private const GUIA = <<<'TXT'
CÓMO LLEVAR LA CHARLA (muy importante):
- Eres amable y vendedor: tu meta es que el negocio cree su cuenta y empiece la prueba gratis, y que quien ya la tiene use LocalProo y pase a un plan pago. Haz UNA pregunta al final de cada respuesta para seguir la conversación.
- Si no sabes quién es, pregúntale qué tipo de negocio tiene (tienda, restaurante, consultorio, funeraria, eventos, contador...) y muéstrale lo que LocalProo hace para ese negocio, con el link exacto.
- Si no ha iniciado sesión: invítala a crear su cuenta gratis (prueba de 1 mes) o a iniciar sesión.
- Si ya tiene cuenta y pregunta cómo hacer algo en el sistema: explícale el paso a paso corto con lo que sabes; si no sabes dónde está, mándala a los cursos de Aprende o al WhatsApp de soporte.
- No presentes LocalProo como sistema para "pequeños negocios": es para todo tipo de negocios que piensan en grande.
- Nunca reveles datos de otras personas. Nunca digas que "ves la base de datos"; habla natural.
TXT;

    // 👉 3) QUIEN ESCRIBE (datos de SU cuenta si inicio sesion: pedidos, reservas, estado...). Solo datos propios.
    private function usuarioContexto(Request $request): string
    {
        $u = $request->user();
        if (! $u) {
            return 'PERSONA QUE ESCRIBE: no ha iniciado sesión (puede ser nueva). Invítala a crear su cuenta.';
        }
        $nombre = trim(explode(' ', trim((string) ($u->name ?? '')))[0] ?? '');

        $l = ['PERSONA QUE ESCRIBE (datos de SU cuenta): se llama '.($nombre ?: 'sin nombre').'.'];
        try {
            if ($u->es_superadmin ?? false) {
                $l[] = '- Es el administrador de LocalProo.';
            }
            $t = \App\Support\TiendaActual::tienda();
            if ($t) {
                $gratis = ! $t->plan || (float) $t->plan->precio <= 0;
                $l[] = '- Su negocio en LocalProo: '.$t->nombre.'. Plan: '.($t->plan->nombre ?? 'prueba gratis').($gratis ? ' (prueba gratis)' : '').'.';
                if ($t->plan_vence) {
                    $d = $t->dias_para_vencer;
                    $l[] = '- Su '.($gratis ? 'prueba' : 'plan').' vence el '.$t->plan_vence->format('d/m/Y').($d !== null ? ' (le quedan '.max(0, $d).' días)' : '').'. Pagar o ver su plan: '.route('plan.index');
                }
                $l[] = '- Su panel: '.route('panel').' · Cursos para aprender a usar su sistema: '.route('aprende.index');
            } elseif ($u->asesor ?? null) {
                $l[] = '- Es asesor de la red comercial de LocalProo. Su curso: '.route('curso.index');
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return implode("\n", $l);
    }

    // 👉 4) LO QUE SABE DEL NEGOCIO (datos reales + links exactos con route()). Lo que no este aqui, NO lo inventa.
    private function contexto(): string
    {
        $wa = 'https://wa.me/'.self::wa();

        return implode("\n", array_filter([
            'Te llamas Mía y eres la asistente virtual de '.self::NEGOCIO.'. Hablas en español de Colombia, cálida y cercana como una persona del equipo, con uno o dos emojis. Respuestas CORTAS: máximo 3 o 4 frases. Si te preguntan si eres una persona o un robot, di con naturalidad que eres la asistente virtual de LocalProo y que si quieren hablar con una persona del equipo, los atienden por WhatsApp.',
            'Escribe siempre con buena ortografía, aunque la persona escriba con errores: entiende lo que quiso decir.',
            'Responde todo lo que puedas con la información de abajo. Si de verdad no está aquí, di que lo confirma una persona por WhatsApp: '.$wa,
            'MUY IMPORTANTE: NO inventes precios, fechas, reglas ni políticas. No supongas.',
            'Si saludan, saluda de vuelta. Da los links completos en texto plano, sin corchetes.',
            '',
            'DATOS DEL NEGOCIO:',
            '- LocalProo ('.url('/').') es un sistema en la nube para gestionar todo tipo de negocios en Colombia (y Venezuela, en dólares): caja POS, inventario, ventas, compras, reportes, facturación electrónica DIAN, nómina, tienda en línea, y módulos para cada tipo de negocio. Funciona en el celular (se instala como app, sin tiendas de aplicaciones), la tableta o el computador.',
            '- Crear cuenta (prueba gratis de 1 mes): '.route('registro').' · Iniciar sesión: '.route('login').' · Olvidé mi clave: '.route('password.request'),
            '- Todas las funciones explicadas: '.route('funciones').' · Blog con guías: '.route('blog.index'),
            self::planesTexto(),
            '- Prueba gratis: 1 mes con todo, limitada a 100 productos. Cuando faltan 15 días se avisa en el panel; para seguir usándolo al terminar, se paga un plan. Condiciones: '.route('legal.terminos'),
            '- Pagos del plan: desde el panel en "Mi plan" (en línea con Wompi). Si quiere pagar de otra forma, por WhatsApp.',
            '',
            'FUNCIONES (todas incluidas; cada negocio esconde lo que no usa en "Personalizar menú"):',
            '- Ventas: caja POS rápida con lector o con la cámara del celular, corregir ventas, separados y abonos, cotizaciones en PDF por WhatsApp, venta por metro/kilo/litro, vendedores con metas.',
            '- Inventario: stock al instante, conteo con el celular, etiquetas con código de barras, importar productos/clientes/proveedores desde Excel o Google, compras y proveedores.',
            '- Vender en internet: tienda en línea que aparece en Google con botón de WhatsApp, Marketplace LocalProo sin comisión ('.route('marketplace').'), carta virtual para restaurantes con QR, "Mis enlaces" tipo Linktree, editor de videos gratis LocalProCut ('.route('cut').'), chats de Facebook e Instagram en un solo lugar ("Mis redes").',
            '- Clientes con historial, WhatsApp con todo el equipo sin repetir clientes, "Comparta y gane" (descuentos por recomendar LocalProo).',
            '- Administración: reportes y ganancias (exporta a Excel), egresos, roles y permisos por empleado, nómina interna o electrónica DIAN, factura electrónica DIAN (POS electrónico, CUFE y QR).',
            '- Por tipo de negocio: restaurantes (mesero → cocina → caja), tiquetera de almuerzos, servicios sin inventario (peluquerías, talleres), alquileres con disponibilidad por fecha, plataforma 360, consultorios con historia clínica, consultorios odontológicos (odontograma, presupuestos con abonos, ortodoncia, citas en línea; registro: '.route('registro.odontologos').'), institutos y academias, funerarias con obituarios digitales para los televisores, pantallas para televisores, eventos con venta de entradas y QR, conjuntos residenciales, y contadores (cruce DIAN, IVA, retenciones, formulario 350).',
            '- Asesores: LocalProo tiene una red comercial de asesores que venden el sistema y ganan comisión; para unirse, por WhatsApp.',
            '',
            'SI NO SABES: cuando la respuesta no esté en esta información, empieza tu respuesta EXACTAMENTE con [SIN_DATO] y luego di amablemente que lo confirma el equipo por WhatsApp ('.$wa.').',
            self::conocimientoTexto(),
        ]));
    }

    // 👉 (opcional) quien puede ver /admin/asistente
    private function esAdmin(Request $request): bool
    {
        $u = $request->user();

        return $u && (bool) ($u->es_superadmin ?? false);
    }

    /** LocalProo: WhatsApp de soporte (Admin → Planes y cobros). */
    private static function wa(): string
    {
        try {
            $n = preg_replace('/\D/', '', (string) \App\Models\Ajuste::valor('soporte_whatsapp'));
        } catch (\Throwable $e) {
            $n = '';
        }

        return $n ?: self::WHATSAPP;
    }

    /** LocalProo: planes activos con su precio real (los toma de Admin → Planes). */
    private static function planesTexto(): string
    {
        try {
            $planes = \App\Models\Plan::where('activo', true)->orderBy('orden')->get();
        } catch (\Throwable $e) {
            return '';
        }
        if ($planes->isEmpty()) {
            return '';
        }

        return '- PLANES (precios en pesos colombianos):'."\n".$planes->map(function ($p) {
            $precio = (float) $p->precio <= 0 ? 'gratis' : \App\Support\Moneda::formato($p->precio, 'COP');
            $antes = (float) ($p->precio_antes ?? 0) > (float) $p->precio ? ' (antes '.\App\Support\Moneda::formato($p->precio_antes, 'COP').')' : '';

            return '  · '.$p->nombre.': '.$precio.$antes.($p->dias ? ' por '.$p->dias.' días' : '').($p->max_productos ? ', hasta '.$p->max_productos.' productos' : '').($p->max_usuarios ? ', hasta '.$p->max_usuarios.' usuarios' : '').($p->descripcion ? '. '.trim(strip_tags($p->descripcion)) : '');
        })->implode("\n");
    }

    /** 2026-11-13: lo que el equipo ya respondio en el admin (la IA lo usa tal cual). */
    public static function conocimientoTexto(): string
    {
        try {
            $filas = \Illuminate\Support\Facades\DB::table('asistente_conocimiento')->where('activo', true)
                ->orderByDesc('id')->limit(80)->get(['pregunta', 'respuesta']);
        } catch (\Throwable $e) {
            return '';
        }
        if ($filas->isEmpty()) {
            return '';
        }

        return "\nPREGUNTAS YA RESPONDIDAS POR EL EQUIPO (son correctas, úsalas como fuente principal):\n"
            .$filas->map(fn ($f) => '- P: '.trim($f->pregunta).' → R: '.trim($f->respuesta))->implode("\n");
    }

    public function responder(Request $request): JsonResponse
    {
        $d = $request->validate([
            'mensaje' => ['required', 'string', 'max:300'],
            'historial' => ['nullable', 'array', 'max:8'],
            'historial.*.rol' => ['required_with:historial', 'in:yo,bot'],
            'historial.*.texto' => ['required_with:historial', 'string', 'max:800'],
            'sesion' => ['nullable', 'string', 'max:40'],
        ]);
        $log = function (?string $respuesta, bool $sin) use ($d, $request) {
            try {
                \Illuminate\Support\Facades\DB::table('asistente_mensajes')->insert([
                    'sesion' => $d['sesion'] ?? null, 'pregunta' => $d['mensaje'], 'respuesta' => $respuesta,
                    'sin_respuesta' => $sin, 'pagina' => mb_substr((string) $request->headers->get('referer'), 0, 255),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        };
        $key = (string) config('services.groq.key');
        $hoy = 'asistente_ia:'.now('America/Bogota')->toDateString();
        if ($key === '' || (int) Cache::get($hoy, 0) >= config('services.groq.max_dia')) {
            $log(null, true);

            return response()->json(['ok' => false]);
        }
        Cache::put($hoy, (int) Cache::get($hoy, 0) + 1, now()->addDays(2));

        $msgs = [['role' => 'system', 'content' => $this->contexto()."\n\n".$this->usuarioContexto($request)."\n\n".self::GUIA]];
        foreach ((array) ($d['historial'] ?? []) as $h) {
            $msgs[] = ['role' => $h['rol'] === 'yo' ? 'user' : 'assistant', 'content' => mb_substr($h['texto'], 0, 800)];
        }
        $msgs[] = ['role' => 'user', 'content' => $d['mensaje']];

        foreach ([config('services.groq.model'), config('services.groq.model_respaldo')] as $modelo) {
            try {
                $r = Http::timeout(20)->withToken($key)->acceptJson()
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => $modelo, 'messages' => $msgs, 'temperature' => 0.4, 'max_tokens' => 900,
                        // gpt-oss razona antes de responder: poco razonamiento = respuesta rapida y sin gastar tokens
                        'reasoning_effort' => 'low',
                    ]);
                $txt = trim((string) $r->json('choices.0.message.content'));
                if ($r->successful() && $txt !== '') {
                    // 2026-11-13: tambien cuenta como "no supo" si la respuesta lo dice con palabras.
                    $sin = str_contains($txt, '[SIN_DATO]') || (bool) preg_match('/no tengo (el|ese|esa|la) (dato|informaci[oó]n)|no tengo informaci[oó]n|no cuento con (esa|ese|la|el)|te lo confirma (el equipo|una persona)|lo confirma el equipo|no s[eé] (exactamente|la respuesta)/iu', $txt);
                    $txt = trim(str_replace('[SIN_DATO]', '', $txt));
                    $log($txt, $sin);

                    return response()->json(['ok' => true, 'texto' => mb_substr($txt, 0, 1500)]);
                }
                Log::warning('Asistente IA ('.$modelo.'): http '.$r->status().' '.mb_substr($r->body(), 0, 300));
            } catch (\Throwable $e) {
                Log::warning('Asistente IA ('.$modelo.'): '.$e->getMessage());
            }
        }

        $log(null, true);

        return response()->json(['ok' => false]);
    }

    // ---------------------------------------------------------------- admin
    public function admin(Request $request)
    {
        abort_unless($this->esAdmin($request), 403);
        $tab = $request->query('tab', 'pendientes');
        $DB = \Illuminate\Support\Facades\DB::class;
        $pendientes = $DB::table('asistente_mensajes')->where('sin_respuesta', true)->where('resuelta', false)->orderByDesc('id')->limit(200)->get();
        $todas = $DB::table('asistente_mensajes')->orderByDesc('id')->limit(200)->get();
        $conocimiento = $DB::table('asistente_conocimiento')->orderByDesc('id')->get();
        $stats = [
            'total' => $DB::table('asistente_mensajes')->count(),
            'hoy' => $DB::table('asistente_mensajes')->where('created_at', '>=', now('America/Bogota')->startOfDay()->utc())->count(),
            'bien' => $DB::table('asistente_mensajes')->where('calificacion', 'bien')->count(),
            'mal' => $DB::table('asistente_mensajes')->where('calificacion', 'mal')->count(),
        ];

        return view('admin.asistente', compact('tab', 'pendientes', 'todas', 'conocimiento', 'stats'));
    }

    /** Responder una pregunta (pendiente o mal respondida): queda como conocimiento para la IA. */
    public function ensenar(Request $request): JsonResponse
    {
        abort_unless($this->esAdmin($request), 403);
        $d = $request->validate([
            'mensaje_id' => ['nullable', 'integer'],
            'pregunta' => ['required', 'string', 'max:1000'],
            'respuesta' => ['required', 'string', 'max:2000'],
        ]);
        \Illuminate\Support\Facades\DB::table('asistente_conocimiento')->insert([
            'pregunta' => trim($d['pregunta']), 'respuesta' => trim($d['respuesta']), 'activo' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        if (! empty($d['mensaje_id'])) {
            \Illuminate\Support\Facades\DB::table('asistente_mensajes')->where('id', $d['mensaje_id'])->update(['resuelta' => true, 'updated_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }

    public function calificar(Request $request): JsonResponse
    {
        abort_unless($this->esAdmin($request), 403);
        $d = $request->validate(['mensaje_id' => ['required', 'integer'], 'calificacion' => ['nullable', 'in:bien,mal'], 'ignorar' => ['nullable', 'boolean']]);
        $upd = ['updated_at' => now()];
        if (array_key_exists('calificacion', $d)) {
            $upd['calificacion'] = $d['calificacion'];
        }
        if (! empty($d['ignorar'])) {
            $upd['resuelta'] = true;
        }
        \Illuminate\Support\Facades\DB::table('asistente_mensajes')->where('id', $d['mensaje_id'])->update($upd);

        return response()->json(['ok' => true]);
    }

    public function conocimiento(Request $request): JsonResponse
    {
        abort_unless($this->esAdmin($request), 403);
        $d = $request->validate([
            'id' => ['required', 'integer'],
            'pregunta' => ['nullable', 'string', 'max:1000'],
            'respuesta' => ['nullable', 'string', 'max:2000'],
            'borrar' => ['nullable', 'boolean'],
        ]);
        $q = \Illuminate\Support\Facades\DB::table('asistente_conocimiento')->where('id', $d['id']);
        if (! empty($d['borrar'])) {
            $q->delete();
        } else {
            $q->update(array_filter(['pregunta' => $d['pregunta'] ?? null, 'respuesta' => $d['respuesta'] ?? null]) + ['updated_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }
}
