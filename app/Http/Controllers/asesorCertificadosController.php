<?php

namespace App\Http\Controllers;

use App\Models\Asesor;
use App\Support\CertificadoAsesor;
use Illuminate\Http\Request;

// "Certificados" del asesor: certificado del curso básico + constancia de experiencia (con mínimo de cursos)
class asesorCertificadosController extends Controller
{
    public function index(Request $request)
    {
        $yo = $request->attributes->get('asesor');
        return view('asesor.certificados', [
            'yo' => $yo,
            'cursos' => CertificadoAsesor::cursosAprobados($yo),
            'minimo' => CertificadoAsesor::minimo(),
            'falta' => CertificadoAsesor::falta($yo),
        ]);
    }

    public function constancia(Request $request)
    {
        $yo = $request->attributes->get('asesor')->load('user');
        if (!CertificadoAsesor::puede($yo)) {
            return redirect()->route('asesor.certificados')
                ->with('error', 'Para descargar su constancia le falta: ' . implode(' y ', CertificadoAsesor::falta($yo)) . '.');
        }
        return view('asesor.constancia', $this->datos($yo) + ['publico' => false]);
    }

    // Verificación pública (enlace firmado del QR): la empresa que recibe la constancia confirma que es real
    public function verificar(Request $request, string $codigo)
    {
        abort_unless($request->hasValidSignature(), 404);
        $a = Asesor::with('user')->where('codigo', $codigo)->first();
        abort_unless($a && $a->activo && $a->curso_aprobado_at, 404, 'Esta constancia ya no está vigente.');
        return view('asesor.constancia', $this->datos($a) + ['publico' => true]);
    }

    private function datos(Asesor $a): array
    {
        return [
            'a' => $a,
            'cursos' => CertificadoAsesor::cursosAprobados($a),
            'desde' => CertificadoAsesor::desde($a),
            'negocios' => $a->id ? $a->tiendas()->count() : 0,
            'equipo' => $a->id ? $a->reclutados()->where('activo', true)->count() : 0,
            'codigo' => CertificadoAsesor::codigo($a),
            'verificar' => CertificadoAsesor::enlaceVerificar($a),
        ];
    }
}
