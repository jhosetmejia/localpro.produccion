<?php

namespace App\Http\Controllers;

use App\Models\Asesor;
use App\Models\User;
use App\Support\Bancos;
use App\Support\RedComercial;
use App\Support\Ubicaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Enlace para sumar asesores: el interesado se inscribe y queda "aspirante" hasta que lo apruebe quien lo invitó
class aspiranteController extends Controller
{
    private function invitador(string $codigo): Asesor
    {
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
        $d = $request->validate([
            'name' => 'required|string|max:80',
            'documento' => 'required|string|max:20',
            'telefono' => 'required|string|max:20',
            'email' => 'required|email|max:120|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'pais' => 'required|string|size:2',
            'zona' => 'nullable|string|max:60',
            'banco' => 'nullable|string|max:120',
        ] + Bancos::reglas() + Ubicaciones::reglas($request->input('pais'), $request->input('departamento'), true), Ubicaciones::mensajes() + [
            'email.unique' => 'Ya existe una cuenta con ese correo.',
            'password.min' => 'La clave debe tener mínimo 8 caracteres.',
            'password.confirmed' => 'Las claves no coinciden.',
            'documento.required' => 'Escriba su cédula.',
            'telefono.required' => 'Escriba su WhatsApp.',
        ]);

        $d['banco'] = Bancos::componer($request);

        DB::transaction(function () use ($d, $invita) {
            $u = User::create(['name' => $d['name'], 'email' => strtolower($d['email']), 'telefono' => $d['telefono'], 'password' => Hash::make($d['password'])]);
            $a = Asesor::create([
                'user_id' => $u->id, 'cargo' => 'asesor', 'padre_id' => null, 'reclutado_por' => $invita->id,
                'pais' => $d['pais'], 'departamento' => $d['departamento'] ?? null, 'ciudad' => $d['ciudad'] ?? null,
                'zona' => $d['zona'] ?? null, 'documento' => $d['documento'], 'banco' => $d['banco'] ?? null,
                'codigo' => Asesor::nuevoCodigo($d['name']), 'activo' => false, 'estado' => 'pendiente',
            ]);
            RedComercial::historial($a, 'solicitud', 'Se inscribió con el enlace de ' . $invita->nombre, $u->id);
        });

        return redirect()->route('unete.form', $codigo)->with('enviado', $d['name']);
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
}
