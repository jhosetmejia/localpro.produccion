<?php

namespace App\Http\Controllers;

use App\Models\AsesorContacto;
use App\Models\RedContacto;
use App\Support\BaseLider;
use App\Support\RedContactos;
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
        // "Escritos hoy" son solo los que escribió él (no los que escribió su gente con la base compartida)
        $propios = fn ($x) => $x->where(fn ($y) => $y->whereNull('contactado_por')->orWhere('contactado_por', $asesorId));
        return ['total' => (clone $q)->count(), 'contactados' => (clone $q)->whereNotNull('contactado_at')->count(),
            'hoy' => $propios((clone $q)->whereDate('contactado_at', now()->toDateString()))->count()];
    }

    public function index(Request $request)
    {
        $a = $this->yo();
        $r = self::resumen($a->id);
        $faltanHoy = max(0, self::DIARIOS - $r['hoy']);
        $hoy = now()->toDateString();
        // Los suyos: sin escribir y que no estén asignados hoy a alguien de su red (si compartió su base)
        $lista = AsesorContacto::where('asesor_id', $a->id)->whereNull('contactado_at')
            ->where(fn ($q) => $q->whereNull('asignado_fecha')->orWhere('asignado_fecha', '<', $hoy)->orWhere('asignado_a', $a->id))
            ->orderBy('id')->limit($faltanHoy)->get();
        $hechosHoy = AsesorContacto::where('asesor_id', $a->id)->whereDate('contactado_at', $hoy)
            ->where(fn ($y) => $y->whereNull('contactado_por')->orWhere('contactado_por', $a->id))->orderByDesc('contactado_at')->get();

        // Gente de su red (para la casilla "Compartir mi base de datos con mis asesores")
        $miRed = count($a->arbolVisible()) - 1;
        $escritosPorRed = AsesorContacto::where('asesor_id', $a->id)->whereNotNull('contactado_por')->where('contactado_por', '<>', $a->id)->count();

        // Bases que le comparten sus líderes (solo dentro de la red de LocalPro)
        $deLideres = collect();
        foreach (BaseLider::lideresPara($a) as $l) {
            BaseLider::asignarHoy($l->id, $a->id, self::DIARIOS);
            $items = AsesorContacto::where('asesor_id', $l->id)->where('asignado_a', $a->id)->where('asignado_fecha', $hoy)
                ->orderByRaw('contactado_at IS NOT NULL')->orderBy('id')->get();
            if ($items->isNotEmpty()) $deLideres->push(['lider' => $l, 'lista' => $items]);
        }

        // Contactos de LocalPro (base compartida que sube el administrador): solo los que le salieron hoy a este asesor
        $redLista = collect();
        if ($a->curso_aprobado_at) {
            RedContactos::asignarHoy($a->id);
            $redLista = RedContacto::where('asignado_a', $a->id)->where('asignado_fecha', now()->toDateString())
                ->orderByRaw('contactado_at IS NOT NULL')->orderBy('id')->get();
        }
        return view('asesor.contactos', [
            'yo' => $a, 'r' => $r, 'lista' => $lista, 'hechosHoy' => $hechosHoy, 'redLista' => $redLista,
            'miRed' => $miRed, 'escritosPorRed' => $escritosPorRed, 'deLideres' => $deLideres,
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
        $contacto->update(['contactado_at' => now(), 'estado' => $estado, 'contactado_por' => $contacto->asesor_id]);
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

    // El líder comparte (o deja de compartir) su base de datos con los asesores de su red
    public function compartir(Request $request)
    {
        $a = $this->yo();
        $a->compartir_contactos = $request->boolean('compartir_contactos');
        $a->save();
        if (!$a->compartir_contactos) {
            // Lo que su gente no alcanzó a escribir hoy vuelve a su base
            AsesorContacto::where('asesor_id', $a->id)->whereNull('contactado_at')->whereNotNull('asignado_a')->where('asignado_a', '<>', $a->id)
                ->update(['asignado_a' => null, 'asignado_fecha' => null]);
        }
        return back()->with('success', $a->compartir_contactos
            ? '👥 Listo: los asesores de su red ya reciben contactos de su base. A cada número se le escribe una sola vez.'
            : 'Su base de datos ya no se comparte con su red.');
    }

    // Marcar un contacto de la base que le compartió su líder. Una sola vez.
    public function marcarLider(Request $request, AsesorContacto $contacto)
    {
        $a = $this->yo();
        abort_unless((int) $contacto->asignado_a === (int) $a->id && BaseLider::puede($a, (int) $contacto->asesor_id), 403);
        $estado = $request->input('estado');
        abort_unless(in_array($estado, ['enviado', 'numero_malo', 'no_interesa'], true), 422);
        $hecho = AsesorContacto::whereKey($contacto->id)->whereNull('contactado_at')
            ->update(['contactado_at' => now(), 'contactado_por' => $a->id, 'estado' => $estado]);
        if (!$hecho && (int) $contacto->contactado_por !== (int) $a->id) {
            return response()->json(['ok' => false, 'mensaje' => 'A este contacto ya le escribió otra persona. No le escriba otra vez.'], 409);
        }
        RedContactos::marcarDesdePropia($contacto->telefono, $a->id, $estado);
        return response()->json(['ok' => true]);
    }
}
