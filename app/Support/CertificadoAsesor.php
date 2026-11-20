<?php

namespace App\Support;

use App\Models\AcademiaAvance;
use App\Models\Ajuste;
use App\Models\Asesor;
use Illuminate\Support\Facades\URL;

/**
 * 2026-11-20 (Jhoset): "Certificados" del asesor. La constancia de experiencia como Asesor Comercial LocalPro
 * (para presentar en otro trabajo) se descarga en PDF solo si aprobó al menos N cursos de "Aprende LocalPro"
 * (ajuste `certificado_asesor_min_cursos`, por defecto 2). Lleva la nota legal: la vinculación es comercial
 * e independiente, por comisión; no es contrato de trabajo ni obliga a pagar salario, prestaciones,
 * horas extras ni aportes a salud, pensión o ARL.
 */
class CertificadoAsesor
{
    public static function minimo(): int
    {
        return max(1, (int) Ajuste::valor('certificado_asesor_min_cursos', 2));
    }

    /** Cursos de Aprende aprobados: [slug => ['titulo' => ..., 'fecha' => Carbon]] */
    public static function cursosAprobados(Asesor $a): array
    {
        if (!$a->user_id) return [];
        $cursos = Academia::cursos();
        return AcademiaAvance::where('user_id', $a->user_id)->whereNotNull('aprobado_at')->orderBy('aprobado_at')->get()
            ->filter(fn ($av) => isset($cursos[$av->curso]))
            ->mapWithKeys(fn ($av) => [$av->curso => ['titulo' => $cursos[$av->curso]['titulo'], 'fecha' => $av->aprobado_at]])
            ->all();
    }

    /** Qué le falta para descargar la constancia (vacío = puede descargarla) */
    public static function falta(Asesor $a): array
    {
        $falta = [];
        $n = count(self::cursosAprobados($a));
        if ($n < self::minimo()) {
            $f = self::minimo() - $n;
            $falta[] = 'aprobar ' . $f . ' curso' . ($f === 1 ? '' : 's') . ' más en Aprende LocalPro (lleva ' . $n . ' de ' . self::minimo() . ')';
        }
        if (!filled($a->documento)) $falta[] = 'poner su cédula en Mi cuenta';
        return $falta;
    }

    public static function puede(Asesor $a): bool
    {
        return self::falta($a) === [];
    }

    public static function desde(Asesor $a)
    {
        return $a->aprobado_at ?? $a->curso_aprobado_at ?? $a->created_at ?? now();
    }

    public static function codigo(Asesor $a): string
    {
        return 'EXP-' . $a->codigo;
    }

    /** Enlace público (firmado) para que la empresa que lo recibe verifique que es real */
    public static function enlaceVerificar(Asesor $a): string
    {
        return URL::signedRoute('constancia.verificar', ['codigo' => $a->codigo]);
    }

    public static function fechaTexto($fecha): string
    {
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        return $fecha->day . ' de ' . $meses[$fecha->month - 1] . ' de ' . $fecha->year;
    }

    /** Texto de la nota legal (sale en la constancia, en la página de certificados y lo usa Mía) */
    public const NOTA_LEGAL = 'Esta constancia certifica únicamente una vinculación de carácter comercial e independiente y no constituye ni prueba la existencia de un contrato de trabajo. El asesor desarrolla su actividad con autonomía, sin subordinación ni horario fijo, y su única remuneración son las comisiones causadas por ventas efectivas, según el modelo comercial de LocalPro. Por lo tanto, este documento no genera para LocalPro obligación de reconocer salarios, prestaciones sociales, vacaciones, horas extras, recargos, indemnizaciones ni aportes al Sistema de Seguridad Social (salud, pensión y riesgos laborales), los cuales corresponden al asesor como trabajador independiente. No sirve como certificado de tiempo cotizado para pensión.';
}
