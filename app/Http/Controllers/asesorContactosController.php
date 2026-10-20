<?php

namespace App\Http\Controllers;

use App\Models\AsesorContacto;
use App\Models\RedContacto;
use App\Models\Cliente;
use App\Models\Tienda;
use App\Support\ContactarBase;
use App\Support\RedContactos;
use App\Support\TiendaActual;
use App\Support\HojaCalculo;
use App\Support\ImportarClientes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * "Mis contactos" del asesor: sube los contactos de su celular y cada día le salen 20 para
 * ofrecerles LocalPro por WhatsApp con su enlace de prueba gratis. Se usa también como tarea del curso.
 */
class asesorContactosController extends Controller
{
    public const DIARIOS = 20;
    // Mensaje por defecto: el mismo de "Empiece a vender LocalPro" ({nombre}, {enlace}; {asesor} se mantiene por compatibilidad)
    public const MENSAJE = "¡Hola {nombre}! 👋\n\n¿No sabes cómo organizar tu negocio? 🤔 Con *LocalPro* lo manejas todo desde el celular:\n\n✅ Ventas y caja (cada vendedor con su caja)\n✅ Inventario por talla y color\n✅ Cuánto ganas cada día\n✅ Tu propia *tienda en línea* que sale en Google\n✅ Facturas por WhatsApp\n\n🎁 Regístrate y pruébalo *3 meses gratis* aquí 👉 {enlace}\n\nSi quieres te lo muestro en 5 minutos. Soy {asesor}, asesor de LocalPro 💚";

    private function yo()
    {
        $a = auth()->user()?->asesor;
        abort_unless($a, 403, 'Esta herramienta es para los asesores de LocalPro.');
        return $a;
    }

    public static function resumen(int $asesorId): array
    {
        $q = AsesorContacto::where('asesor_id', $asesorId);
        return ['total' => (clone $q)->count(), 'contactados' => (clone $q)->whereNotNull('contactado_at')->count(),
            'hoy' => (clone $q)->whereDate('contactado_at', now()->toDateString())->count()];
    }

    public function index(Request $request)
    {
        $a = $this->yo();
        $r = self::resumen($a->id);
        $faltanHoy = max(0, self::DIARIOS - $r['hoy']);
        $lista = AsesorContacto::where('asesor_id', $a->id)->whereNull('contactado_at')->orderBy('id')->limit($faltanHoy)->get();
        $hechosHoy = AsesorContacto::where('asesor_id', $a->id)->whereDate('contactado_at', now()->toDateString())->orderByDesc('contactado_at')->get();

        // Contactos de LocalPro (base compartida que sube el administrador): solo los que le salieron hoy a este asesor
        $redLista = collect();
        if ($a->curso_aprobado_at) {
            RedContactos::asignarHoy($a->id);
            $redLista = RedContacto::where('asignado_a', $a->id)->where('asignado_fecha', now()->toDateString())
                ->orderByRaw('contactado_at IS NOT NULL')->orderBy('id')->get();
        }
        // Bases de datos que le comparten los negocios (el dueño marcó "Compartir mi base de datos con mis asesores")
        $compartidas = collect();
        $userId = (int) auth()->id();
        foreach (ContactarBase::tiendasPara($a) as $t) {
            $lista2 = TiendaActual::como($t->id, function () use ($t, $userId) {
                $hoy = now()->toDateString();
                $deHoy = Cliente::where('asignado_a', $userId)->where('asignado_fecha', $hoy)->count();
                $cuota = ContactarBase::diariosAsesor($t);
                if ($deHoy < $cuota) ContactarBase::asignar($userId, $cuota - $deHoy);
                return Cliente::with('persona:id,razon_social,telefono')->where('asignado_a', $userId)->where('asignado_fecha', $hoy)
                    ->orderByRaw('contactado_at IS NOT NULL')->orderBy('id')->get()
                    ->map(function ($c) {
                        $nombre = trim((string) $c->persona?->razon_social);
                        return ['id' => $c->id, 'nombre' => $nombre ?: 'Sin nombre', 'telefono' => $c->persona?->telefono,
                            'wa' => ContactarBase::numero($c->persona?->telefono),
                            'primer' => preg_match('/^contacto\s/i', $nombre) ? '' : (string) \Illuminate\Support\Str::of($nombre)->explode(' ')->first(),
                            'hecho' => (bool) $c->contactado_at, 'estado' => $c->contacto_estado,
                            'otro' => $c->contactado_at && (int) $c->contactado_por !== (int) auth()->id()];
                    })->filter(fn ($c) => $c['wa'] !== '')->values();
            });
            if ($lista2->isEmpty()) continue;
            $compartidas->push([
                'tienda' => $t, 'lista' => $lista2,
                'mensaje' => $t->mensaje_contacto ?: contactarController::MENSAJE,
                'catalogo' => $t->catalogo_activo && $t->slug ? $t->url_catalogo : '',
            ]);
        }

        return view('asesor.contactos', [
            'yo' => $a, 'r' => $r, 'lista' => $lista, 'hechosHoy' => $hechosHoy, 'redLista' => $redLista, 'compartidas' => $compartidas,
            'mensaje' => self::MENSAJE, 'volver' => $request->query('volver') === 'curso' ? route('curso.leccion', \App\Support\CursoAsesor::LECCION_CONTACTOS) : null,
        ]);
    }

    public function subir(Request $request)
    {
        $a = $this->yo();
        $request->validate(['archivo' => 'required|file|max:10240'], ['archivo.required' => 'Elija el archivo de contactos.', 'archivo.max' => 'El archivo pesa más de 10 MB.']);
        $f = $request->file('archivo');
        $ext = strtolower($f->getClientOriginalExtension() ?: $f->extension());
        if (!in_array($ext, ['vcf', 'vcard', 'csv', 'txt', 'xlsx'], true)) return back()->withErrors('Suba el archivo de contactos del celular (.vcf) o un .csv / .xlsx.');
        try {
            $filas = HojaCalculo::leer($f->getRealPath(), $ext);
        } catch (Throwable $e) {
            return back()->withErrors($e->getMessage());
        }
        $an = ImportarClientes::analizar($filas);
        $nuevos = 0;
        $ya = AsesorContacto::where('asesor_id', $a->id)->pluck('telefono')->flip()->all();
        $filasOk = [];
        foreach (ImportarClientes::filas($an['datos'], $an['mapa']) as $c) {
            if ($c['telefono'] === '' || strlen($c['telefono']) < 7 || isset($ya[$c['telefono']])) continue;
            $ya[$c['telefono']] = 1;
            $filasOk[] = ['asesor_id' => $a->id, 'nombre' => mb_substr($c['nombre'] ?: 'Contacto ' . $c['telefono'], 0, 80), 'telefono' => $c['telefono'], 'created_at' => now(), 'updated_at' => now()];
        }
        foreach (array_chunk($filasOk, 500) as $lote) { DB::table('asesor_contactos')->insertOrIgnore($lote); $nuevos += count($lote); }
        return back()->with('success', $nuevos ? "¡Listo! Se guardaron $nuevos contactos. Empiece con los " . self::DIARIOS . " de hoy." : 'No se encontraron contactos nuevos con celular en ese archivo.');
    }

    public function marcar(Request $request, AsesorContacto $contacto)
    {
        abort_unless((int) $contacto->asesor_id === (int) $this->yo()->id, 403);
        $estado = $request->input('estado');
        abort_unless(in_array($estado, ['enviado', 'numero_malo', 'no_interesa'], true), 422);
        $contacto->update(['contactado_at' => now(), 'estado' => $estado]);
        // Ese número ya no le sale a ningún asesor en la base de LocalPro
        RedContactos::marcarDesdePropia($contacto->telefono, $contacto->asesor_id, $estado);
        return response()->json(['ok' => true]);
    }

    // Marcar un contacto de la base de LocalPro. Una sola vez: si ya está marcado no se puede volver a escribir.
    public function marcarRed(Request $request, RedContacto $contacto)
    {
        $a = $this->yo();
        abort_unless((int) $contacto->asignado_a === (int) $a->id, 403);
        $estado = $request->input('estado');
        abort_unless(in_array($estado, ['enviado', 'numero_malo', 'no_interesa'], true), 422);
        $hecho = RedContacto::whereKey($contacto->id)->whereNull('contactado_at')
            ->update(['contactado_at' => now(), 'contactado_por' => $a->id, 'estado' => $estado]);
        if (!$hecho && (int) $contacto->contactado_por !== (int) $a->id) {
            return response()->json(['ok' => false, 'mensaje' => 'A este contacto ya le escribió otro asesor. No le escriba otra vez.'], 409);
        }
        return response()->json(['ok' => true]);
    }

    // Contacto de la base que le compartió un negocio. Una sola vez: si alguien ya le escribió, se avisa y no se repite.
    private function clienteCompartido(int $tienda, int $cliente): array
    {
        $a = $this->yo();
        abort_unless(ContactarBase::puede($a, $tienda), 403, 'Ese negocio ya no le comparte su base de datos.');
        $c = TiendaActual::como($tienda, fn () => Cliente::with('persona')->find($cliente));
        abort_unless($c && (int) $c->asignado_a === (int) auth()->id(), 403);
        return [$a, $c];
    }

    public function revisarTienda(int $tienda, int $cliente)
    {
        [, $c] = $this->clienteCompartido($tienda, $cliente);
        $mensaje = TiendaActual::como($tienda, fn () => ContactarBase::yaEscrito($c));
        return response()->json(['libre' => !$mensaje, 'mensaje' => $mensaje]);
    }

    public function marcarTienda(Request $request, int $tienda, int $cliente)
    {
        [, $c] = $this->clienteCompartido($tienda, $cliente);
        $estado = $request->input('estado');
        abort_unless(in_array($estado, ['enviado', 'numero_malo', 'no_interesa'], true), 422);
        if ($c->contactado_at && (int) $c->contactado_por === (int) auth()->id()) return response()->json(['ok' => true]);
        [$ok, $mensaje, $repetido] = TiendaActual::como($tienda, fn () => ContactarBase::marcar($c, (int) auth()->id(), $estado));
        return $ok ? response()->json(['ok' => true]) : response()->json(['ok' => false, 'repetido' => $repetido, 'mensaje' => $mensaje], 409);
    }
}
