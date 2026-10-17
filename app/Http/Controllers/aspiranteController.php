<?php

namespace App\Http\Controllers;

use App\Models\Asesor;
use App\Models\User;
use App\Support\RedComercial;
use App\Support\Ubicaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Enlace para sumar asesores: el interesado se inscribe, entra de una vez a un curso corto
// y al aprobar el examen queda como asesor activo (quien lo invitó puede desactivarlo después)
class aspiranteController extends Controller
{
    private function invitador(string $codigo): Asesor
    {
        // Enlace del súper administrador: invita LocalPro (queda sin líder hasta que el admin se lo asigne)
        if (Asesor::esCodigoLocalpro($codigo)) {
            $a = new Asesor(['codigo' => Asesor::CODIGO_LOCALPRO, 'cargo' => 'lider_pais', 'pais' => 'CO']);
            $a->setRelation('user', new User(['name' => 'LocalPro']));
            return $a;
        }
        $a = Asesor::with('user:id,name')->porCodigo($codigo)->where('activo', true)->where('estado', 'activo')->first();
        abort_unless($a, 404, 'Este enlace de invitación no es válido.');
        return $a;
    }

    public function form(string $codigo)
    {
        return view('asesores.unete', ['invita' => $this->invitador($codigo), 'enviado' => session('enviado')]);
    }

    public function store(Request $request, string $codigo)
    {
        $invita = $this->invitador($codigo);
        // Solo datos básicos: la cédula y dónde se le paga los completa después en "Mi cuenta"
        $d = $request->validate([
            'name' => 'required|string|max:80',
            'telefono' => 'required|string|max:20',
            'email' => 'required|email|max:120|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'pais' => 'required|string|size:2',
            'zona' => 'nullable|string|max:60',
            'experiencia' => 'required|in:' . implode(',', array_keys(Asesor::EXPERIENCIA)),
        ] + Ubicaciones::reglas($request->input('pais'), $request->input('departamento'), true), Ubicaciones::mensajes() + [
            'email.unique' => 'Ya existe una cuenta con ese correo.',
            'password.min' => 'La clave debe tener mínimo 8 caracteres.',
            'password.confirmed' => 'Las claves no coinciden.',
            'telefono.required' => 'Escriba su celular (WhatsApp).',
            'experiencia.required' => 'Cuéntenos si tiene experiencia en ventas.',
        ]);

        $u = DB::transaction(function () use ($d, $invita) {
            $u = User::create(['name' => $d['name'], 'email' => strtolower($d['email']), 'telefono' => $d['telefono'], 'password' => Hash::make($d['password'])]);
            $a = Asesor::create([
                'user_id' => $u->id, 'cargo' => 'asesor', 'padre_id' => null, 'reclutado_por' => $invita->id ?: null,
                'pais' => $d['pais'], 'departamento' => $d['departamento'] ?? null, 'ciudad' => $d['ciudad'] ?? null,
                'zona' => $d['zona'] ?? null, 'experiencia' => $d['experiencia'],
                'codigo' => Asesor::nuevoCodigo($d['name']), 'activo' => false, 'estado' => 'capacitacion',
            ]);
            RedComercial::historial($a, 'solicitud', 'Se inscribió con el enlace de ' . $invita->nombre . ' y empezó la prueba básica', $u->id);
            return $u;
        });

        Auth::login($u);
        $request->session()->regenerate();
        return redirect()->route('curso.index')->with('bienvenida', true);
    }

    // Quien invitó aprueba o rechaza a su aspirante
    public function decidir(Request $request, Asesor $aspirante)
    {
        $yo = $request->attributes->get('asesor');
        abort_unless((int) $aspirante->reclutado_por === (int) $yo->id && $aspirante->estado === 'pendiente', 403);

        if ($request->input('accion') === 'aprobar') {
            $aspirante->update([
                'estado' => 'activo', 'activo' => true, 'aprobado_at' => now(),
                'padre_id' => RedComercial::ubicar($aspirante, $yo),
            ]);
            RedComercial::historial($aspirante, 'aprobado', 'Aprobado por ' . $yo->nombre);
            return back()->with('success', $aspirante->nombre . ' ya es parte de su equipo. Puede entrar con su correo y clave.');
        }

        $aspirante->update(['estado' => 'rechazado', 'activo' => false]);
        RedComercial::historial($aspirante, 'rechazado', 'Rechazado por ' . $yo->nombre);
        return back()->with('success', 'Solicitud de ' . $aspirante->nombre . ' rechazada.');
    }

    // Quien lo invitó puede desactivar (o sacar del curso) a una persona que sumó. Volver a activarla lo hace LocalPro.
    public function desactivar(Request $request, Asesor $aspirante)
    {
        $yo = $request->attributes->get('asesor');
        abort_unless((int) $aspirante->reclutado_por === (int) $yo->id && in_array($aspirante->estado, ['activo', 'capacitacion'], true) && ($aspirante->activo || $aspirante->estado === 'capacitacion'), 403);

        $aspirante->estado === 'capacitacion'
            ? $aspirante->update(['estado' => 'rechazado', 'activo' => false])
            : $aspirante->update(['activo' => false]);
        RedComercial::historial($aspirante, 'inactivo', 'Desactivado por quien lo invitó: ' . $yo->nombre);
        return back()->with('success', $aspirante->nombre . ' quedó desactivado. Si fue un error, escríbale a LocalPro para activarlo de nuevo.');
    }
}
