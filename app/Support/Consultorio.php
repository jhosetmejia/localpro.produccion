<?php

namespace App\Support;

// Plantillas de historia clínica por especialidad y diagnósticos CIE-10 frecuentes en consulta particular.
// Cada plantilla define secciones con campos: [clave, etiqueta, tipo (texto|area|numero), ayuda].
// Los campos comunes (motivo, enfermedad actual, signos vitales, análisis, plan) van en todas.
class Consultorio
{
    public const SIGNOS = [
        ['ta', 'Tensión arterial', 'texto', 'mmHg · ej. 120/80'],
        ['fc', 'Frec. cardiaca', 'numero', 'lpm'],
        ['fr', 'Frec. respiratoria', 'numero', 'rpm'],
        ['temp', 'Temperatura', 'numero', '°C'],
        ['sat', 'Saturación O₂', 'numero', '%'],
        ['peso', 'Peso', 'numero', 'kg'],
        ['talla', 'Talla', 'numero', 'cm'],
    ];

    public static function plantillas(): array
    {
        return [
            'general' => [
                'nombre' => 'Medicina general', 'icono' => 'fa-stethoscope', 'signos' => true,
                'campos' => [
                    ['revision', 'Revisión por sistemas', 'area', 'Síntomas por sistemas (cardiovascular, respiratorio, digestivo…)'],
                    ['examen', 'Examen físico', 'area', 'Hallazgos por regiones'],
                ],
            ],
            'odontologia' => [
                'nombre' => 'Odontología', 'icono' => 'fa-tooth', 'signos' => false,
                'campos' => [
                    ['extraoral', 'Examen extraoral', 'area', 'ATM, ganglios, labios, asimetrías'],
                    ['intraoral', 'Examen intraoral', 'area', 'Encías, mucosas, lengua, paladar, oclusión'],
                    ['odontograma', 'Odontograma (piezas y hallazgos)', 'area', 'Ej. 16: caries oclusal · 26: obturación en resina · 38: semi-incluido'],
                    ['higiene', 'Índice de placa / higiene oral', 'texto', 'Ej. O\'Leary 35 %'],
                    ['procedimiento', 'Procedimiento realizado hoy', 'area', 'Ej. Profilaxis y detartraje supragingival'],
                ],
            ],
            'psicologia' => [
                'nombre' => 'Psicología', 'icono' => 'fa-brain', 'signos' => false,
                'campos' => [
                    ['examen_mental', 'Examen mental', 'area', 'Apariencia, conciencia, orientación, afecto, pensamiento, juicio'],
                    ['historia_personal', 'Historia personal y familiar', 'area', 'Dinámica familiar, eventos significativos, red de apoyo'],
                    ['pruebas', 'Pruebas aplicadas y resultados', 'area', 'Ej. Beck, BAI…'],
                    ['intervencion', 'Intervención de la sesión', 'area', 'Técnicas y temas trabajados'],
                    ['tareas', 'Tareas / acuerdos', 'area', ''],
                ],
            ],
            'estetica' => [
                'nombre' => 'Medicina estética', 'icono' => 'fa-spa', 'signos' => true,
                'campos' => [
                    ['fototipo', 'Fototipo / tipo de piel', 'texto', 'Ej. Fitzpatrick III · piel mixta'],
                    ['zona', 'Zona a tratar y hallazgos', 'area', ''],
                    ['procedimiento', 'Procedimiento realizado', 'area', 'Producto, lote, dosis, técnica, zonas'],
                    ['consentimiento', 'Consentimiento informado', 'texto', 'Ej. Firmado el 17/10/2026'],
                    ['cuidados', 'Cuidados posteriores indicados', 'area', ''],
                ],
            ],
            'fisioterapia' => [
                'nombre' => 'Fisioterapia', 'icono' => 'fa-person-walking', 'signos' => true,
                'campos' => [
                    ['dolor', 'Dolor (EVA 0-10) y localización', 'texto', 'Ej. EVA 6/10 región lumbar'],
                    ['evaluacion', 'Evaluación (postura, arcos de movimiento, fuerza)', 'area', ''],
                    ['pruebas', 'Pruebas especiales', 'area', ''],
                    ['objetivos', 'Objetivos del tratamiento', 'area', ''],
                    ['sesion', 'Sesión realizada', 'area', 'Técnicas, ejercicios, tiempo'],
                ],
            ],
            'nutricion' => [
                'nombre' => 'Nutrición', 'icono' => 'fa-apple-whole', 'signos' => true,
                'campos' => [
                    ['medidas', 'Medidas (cintura, cadera, % grasa)', 'texto', 'Ej. Cintura 88 cm · cadera 102 cm · grasa 31 %'],
                    ['recordatorio', 'Recordatorio de 24 horas / hábitos', 'area', ''],
                    ['laboratorios', 'Laboratorios', 'area', 'Glicemia, perfil lipídico…'],
                    ['plan_alimentario', 'Plan alimentario', 'area', ''],
                ],
            ],
        ];
    }

    public static function plantilla(?string $clave): array
    {
        $p = self::plantillas();
        return $p[$clave] ?? $p['general'];
    }

    // Diagnósticos CIE-10 más usados en consulta particular. Se puede escribir cualquier otro código a mano.
    public const CIE10 = [
        'Z000' => 'Examen médico general', 'Z001' => 'Control de salud de rutina del niño', 'Z019' => 'Examen especial no especificado',
        'Z300' => 'Consejo y asesoramiento general sobre la anticoncepción', 'Z713' => 'Consulta para instrucción y vigilancia de la dieta',
        'Z719' => 'Consulta no especificada', 'Z763' => 'Persona sana que acompaña al enfermo',
        'J00X' => 'Rinofaringitis aguda (resfriado común)', 'J029' => 'Faringitis aguda no especificada', 'J039' => 'Amigdalitis aguda no especificada',
        'J069' => 'Infección aguda de las vías respiratorias superiores', 'J209' => 'Bronquitis aguda no especificada', 'J304' => 'Rinitis alérgica no especificada',
        'J459' => 'Asma no especificada', 'A09X' => 'Diarrea y gastroenteritis de presunto origen infeccioso', 'K297' => 'Gastritis no especificada',
        'K30X' => 'Dispepsia', 'K219' => 'Enfermedad del reflujo gastroesofágico sin esofagitis', 'K590' => 'Constipación', 'K580' => 'Síndrome de colon irritable con diarrea',
        'N390' => 'Infección de vías urinarias, sitio no especificado', 'N946' => 'Dismenorrea no especificada', 'N912' => 'Amenorrea sin otra especificación',
        'I10X' => 'Hipertensión esencial (primaria)', 'E119' => 'Diabetes mellitus tipo 2 sin complicaciones', 'E039' => 'Hipotiroidismo no especificado',
        'E660' => 'Obesidad debida a exceso de calorías', 'E669' => 'Obesidad no especificada', 'E785' => 'Hiperlipidemia no especificada',
        'E441' => 'Desnutrición proteicocalórica leve', 'D509' => 'Anemia por deficiencia de hierro sin otra especificación',
        'R51X' => 'Cefalea', 'G439' => 'Migraña no especificada', 'G442' => 'Cefalea debida a tensión', 'R104' => 'Otros dolores abdominales y los no especificados', 'R509' => 'Fiebre no especificada',
        'M545' => 'Lumbago no especificado', 'M542' => 'Cervicalgia', 'M255' => 'Dolor en articulación', 'M791' => 'Mialgia', 'M751' => 'Síndrome del manguito rotatorio',
        'M770' => 'Epicondilitis media', 'M722' => 'Fibromatosis de la aponeurosis plantar (fascitis plantar)', 'S934' => 'Esguince y torcedura del tobillo',
        'S134' => 'Esguince y torcedura de la columna cervical', 'M179' => 'Gonartrosis no especificada', 'M199' => 'Artrosis no especificada',
        'L700' => 'Acné vulgar', 'L709' => 'Acné no especificado', 'L811' => 'Cloasma (melasma)', 'L814' => 'Otras hiperpigmentaciones por melanina',
        'L659' => 'Pérdida de cabello no cicatricial no especificada', 'L301' => 'Dishidrosis', 'L309' => 'Dermatitis no especificada', 'B354' => 'Tiña del cuerpo',
        'L905' => 'Fibrosis y afecciones cicatriciales de la piel', 'L574' => 'Cutis laxa senil (envejecimiento cutáneo)', 'L988' => 'Otros trastornos de la piel',
        'K020' => 'Caries limitada al esmalte', 'K021' => 'Caries de la dentina', 'K029' => 'Caries dental no especificada', 'K040' => 'Pulpitis',
        'K041' => 'Necrosis de la pulpa', 'K045' => 'Periodontitis apical crónica', 'K050' => 'Gingivitis aguda', 'K051' => 'Gingivitis crónica',
        'K053' => 'Periodontitis crónica', 'K036' => 'Depósitos (acreciones) en los dientes', 'K011' => 'Dientes impactados', 'K076' => 'Trastornos de la articulación temporomaxilar',
        'K081' => 'Pérdida de dientes por accidente, extracción o enfermedad periodontal', 'K003' => 'Dientes moteados (fluorosis)', 'K071' => 'Anomalías de la relación maxilobasal',
        'F329' => 'Episodio depresivo no especificado', 'F321' => 'Episodio depresivo moderado', 'F411' => 'Trastorno de ansiedad generalizada',
        'F412' => 'Trastorno mixto de ansiedad y depresión', 'F419' => 'Trastorno de ansiedad no especificado', 'F410' => 'Trastorno de pánico',
        'F432' => 'Trastornos de adaptación', 'F431' => 'Trastorno de estrés postraumático', 'F430' => 'Reacción al estrés agudo', 'F900' => 'Perturbación de la actividad y de la atención (TDAH)',
        'F510' => 'Insomnio no orgánico', 'F509' => 'Trastorno de la ingestión de alimentos no especificado', 'F102' => 'Trastornos por uso de alcohol: síndrome de dependencia',
        'F603' => 'Trastorno de la personalidad emocionalmente inestable', 'Z630' => 'Problemas en la relación entre esposos o pareja', 'Z634' => 'Desaparición o muerte de miembro de la familia (duelo)',
        'Z731' => 'Problemas relacionados con la acentuación de rasgos de la personalidad', 'Z560' => 'Problemas relacionados con el empleo (desempleo)',
        'H101' => 'Conjuntivitis atópica aguda', 'H109' => 'Conjuntivitis no especificada', 'H669' => 'Otitis media no especificada', 'H612' => 'Cerumen impactado',
        'T784' => 'Alergia no especificada', 'R42X' => 'Mareo y desvanecimiento', 'R53X' => 'Malestar y fatiga', 'R05X' => 'Tos', 'G470' => 'Trastornos del inicio y del mantenimiento del sueño',
    ];

    public static function buscarCie(string $q, int $max = 12): array
    {
        $q = mb_strtolower(trim($q));
        if ($q === '') return [];
        $sin = fn ($t) => strtr(mb_strtolower($t), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
        $qq = $sin($q);
        $res = [];
        foreach (self::CIE10 as $cod => $txt) {
            if (str_starts_with(mb_strtolower($cod), $q) || str_contains($sin($txt), $qq)) {
                $res[] = ['codigo' => $cod, 'texto' => $txt];
                if (count($res) >= $max) break;
            }
        }
        return $res;
    }
}
