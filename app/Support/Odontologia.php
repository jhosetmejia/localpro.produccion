<?php

namespace App\Support;

// Consultorio odontológico: piezas dentales (FDI), hallazgos del odontograma y plantillas de consentimiento informado.
// Convención colombiana del odontograma: ROJO = lo que hay que tratar (caries, extracción indicada…),
// AZUL = lo que ya está hecho o existe (resinas, coronas, ausentes…).
class Odontologia
{
    // Piezas en el orden en que se dibujan (vista de frente: derecha del paciente a la izquierda de la pantalla)
    public const PERMANENTES = [
        'sup' => ['18', '17', '16', '15', '14', '13', '12', '11', '21', '22', '23', '24', '25', '26', '27', '28'],
        'inf' => ['48', '47', '46', '45', '44', '43', '42', '41', '31', '32', '33', '34', '35', '36', '37', '38'],
    ];

    public const TEMPORALES = [
        'sup' => ['55', '54', '53', '52', '51', '61', '62', '63', '64', '65'],
        'inf' => ['85', '84', '83', '82', '81', '71', '72', '73', '74', '75'],
    ];

    public const DENTICIONES = ['permanente' => 'Permanente (adulto)', 'temporal' => 'Temporal (niños)', 'mixta' => 'Mixta'];

    public const CARAS = ['V' => 'Vestibular', 'L' => 'Lingual / palatina', 'M' => 'Mesial', 'D' => 'Distal', 'O' => 'Oclusal / incisal'];

    // clave => [nombre, color, alcance (cara|pieza), procedimiento que se sugiere para el plan (null = nada), símbolo]
    public const HALLAZGOS = [
        'caries' => ['Caries', '#e11d48', 'cara', 'Resina por superficie', null],
        'fractura' => ['Fractura', '#e11d48', 'cara', 'Restauración de fractura', null],
        'resina' => ['Resina (hecha)', '#2563eb', 'cara', null, null],
        'amalgama' => ['Amalgama (hecha)', '#1e3a8a', 'cara', null, null],
        'sellante' => ['Sellante', '#0891b2', 'cara', null, null],
        'sellante_ind' => ['Sellante indicado', '#f97316', 'cara', 'Sellante de fosetas y fisuras', null],
        'exodoncia' => ['Extracción indicada', '#e11d48', 'pieza', 'Exodoncia simple', 'X'],
        'ausente' => ['Ausente', '#2563eb', 'pieza', null, 'X'],
        'endodoncia_ind' => ['Endodoncia indicada', '#e11d48', 'pieza', 'Endodoncia', 'E'],
        'endodoncia' => ['Endodoncia (hecha)', '#2563eb', 'pieza', null, 'E'],
        'corona_ind' => ['Corona indicada', '#e11d48', 'pieza', 'Corona', 'C'],
        'corona' => ['Corona (hecha)', '#2563eb', 'pieza', null, 'C'],
        'implante' => ['Implante', '#2563eb', 'pieza', null, 'I'],
        'protesis' => ['Prótesis / póntico', '#2563eb', 'pieza', null, 'P'],
        'sin_erupcionar' => ['Sin erupcionar', '#64748b', 'pieza', null, 'SE'],
        'movilidad' => ['Movilidad', '#e11d48', 'pieza', 'Valoración periodontal', 'M'],
    ];

    public static function piezasValidas(): array
    {
        return array_merge(...array_values(self::PERMANENTES), ...array_values(self::TEMPORALES));
    }

    // Limpia lo que llega del navegador: solo piezas, caras y hallazgos conocidos
    public static function limpiarPiezas($datos): array
    {
        $validas = array_flip(self::piezasValidas());
        $out = [];
        foreach ((array) $datos as $pieza => $d) {
            $pieza = (string) $pieza;
            if (!isset($validas[$pieza]) || !is_array($d)) continue;
            $fila = [];
            $h = $d['pieza'] ?? null;
            if (is_string($h) && (self::HALLAZGOS[$h][2] ?? null) === 'pieza') $fila['pieza'] = $h;
            foreach ((array) ($d['caras'] ?? []) as $cara => $hc) {
                if (isset(self::CARAS[$cara]) && is_string($hc) && (self::HALLAZGOS[$hc][2] ?? null) === 'cara') $fila['caras'][$cara] = $hc;
            }
            $nota = trim(mb_substr((string) ($d['nota'] ?? ''), 0, 160));
            if ($nota !== '') $fila['nota'] = $nota;
            if ($fila) $out[$pieza] = $fila;
        }
        ksort($out, SORT_NATURAL);
        return $out;
    }

    // Lo que hay que tratar según el odontograma: [[pieza, caras, procedimiento, hallazgo]]
    public static function pendientes(array $piezas): array
    {
        $out = [];
        foreach ($piezas as $pieza => $d) {
            if (!empty($d['pieza']) && ($proc = self::HALLAZGOS[$d['pieza']][3] ?? null)) {
                $out[] = ['pieza' => (string) $pieza, 'caras' => null, 'procedimiento' => $proc, 'hallazgo' => $d['pieza']];
                // Si la pieza se va a extraer no se le sugieren resinas
                if ($d['pieza'] === 'exodoncia') continue;
            }
            $porProc = [];
            foreach ((array) ($d['caras'] ?? []) as $cara => $h) {
                if ($proc = self::HALLAZGOS[$h][3] ?? null) $porProc[$proc][] = $cara;
            }
            foreach ($porProc as $proc => $caras) {
                $out[] = ['pieza' => (string) $pieza, 'caras' => implode('', $caras), 'procedimiento' => $proc, 'hallazgo' => null, 'cantidad' => $proc === 'Resina por superficie' ? count($caras) : 1];
            }
        }
        return $out;
    }

    // Resumen corto de una pieza para listas e impresión: "16: caries O, M · endodoncia indicada"
    public static function resumen(array $piezas): array
    {
        $out = [];
        foreach ($piezas as $pieza => $d) {
            $partes = [];
            if (!empty($d['pieza'])) $partes[] = mb_strtolower(self::HALLAZGOS[$d['pieza']][0] ?? $d['pieza']);
            $grupos = [];
            foreach ((array) ($d['caras'] ?? []) as $cara => $h) $grupos[$h][] = $cara;
            foreach ($grupos as $h => $caras) $partes[] = mb_strtolower(self::HALLAZGOS[$h][0] ?? $h) . ' ' . implode(', ', $caras);
            if (!empty($d['nota'])) $partes[] = $d['nota'];
            if ($partes) $out[$pieza] = implode(' · ', $partes);
        }
        return $out;
    }

    // ===== Consentimientos informados =====
    // Texto con marcadores: {paciente}, {documento}, {procedimiento}, {profesional}, {consultorio}, {fecha}
    public static function consentimientos(): array
    {
        $base = "Yo, {paciente}, identificado(a) con {documento}, en pleno uso de mis facultades, autorizo al odontólogo(a) {profesional} de {consultorio} para que me realice el siguiente procedimiento: {procedimiento}.\n\n";
        $cierre = "\n\nDeclaro que me explicaron en lenguaje claro en qué consiste el procedimiento, sus beneficios, los riesgos y las alternativas, que pude hacer todas las preguntas que quise y que me respondieron. Sé que puedo retirar esta autorización en cualquier momento antes del procedimiento. Me comprometo a seguir las indicaciones y a asistir a los controles.\n\nFirmo en {ciudad} el {fecha}.";
        return [
            'general' => ['Consentimiento general de atención odontológica', 'Valoración, limpieza y tratamiento odontológico',
                $base . "Entiendo que la atención incluye valoración, radiografías cuando se necesiten, anestesia local, limpiezas y tratamientos de operatoria (resinas). Los riesgos más comunes son sensibilidad pasajera, molestia en la zona, inflamación leve y, con la anestesia, adormecimiento por algunas horas. En raras ocasiones puede haber reacción alérgica a los materiales.\n\nInformé al profesional mis enfermedades, alergias y los medicamentos que tomo." . $cierre],
            'exodoncia' => ['Consentimiento para exodoncia (extracción)', 'Exodoncia de la pieza ___',
                $base . "Entiendo que la exodoncia consiste en retirar el diente de su alvéolo. Pueden presentarse dolor, inflamación, sangrado, morados, limitación para abrir la boca, alveolitis (infección del alvéolo), lesión de dientes vecinos, comunicación con el seno maxilar en dientes superiores y, en casos poco frecuentes, adormecimiento temporal o prolongado del labio o la lengua.\n\nAlternativas que me explicaron: conservar el diente con otro tratamiento cuando sea posible, o no tratarlo, con los riesgos que eso implica (dolor, infección)." . $cierre],
            'endodoncia' => ['Consentimiento para endodoncia (tratamiento de conducto)', 'Endodoncia de la pieza ___',
                $base . "Entiendo que la endodoncia consiste en retirar el nervio (pulpa) del diente, limpiar y sellar sus conductos. Puede haber dolor o molestia los días siguientes, fractura de instrumentos dentro del conducto, perforaciones, y que el diente quede más frágil, por lo que puede necesitar una corona o reconstrucción después. Aun bien hecha, una endodoncia puede fracasar y requerir retratamiento, cirugía o la extracción.\n\nAlternativas: la extracción del diente o no tratarlo." . $cierre],
            'cirugia' => ['Consentimiento para cirugía oral e implantes', 'Cirugía / implante en la zona ___',
                $base . "Entiendo que el procedimiento es quirúrgico. Pueden presentarse dolor, inflamación, sangrado, morados, infección, adormecimiento temporal o prolongado de labios, lengua o mentón, comunicación con el seno maxilar y, en el caso de implantes, que el hueso no se integre al implante y haya que retirarlo y repetir el procedimiento.\n\nMe comprometo a no fumar, a tomar los medicamentos indicados y a cuidar la higiene de la zona." . $cierre],
            'blanqueamiento' => ['Consentimiento para blanqueamiento dental', 'Blanqueamiento dental',
                $base . "Entiendo que el blanqueamiento aclara el color de los dientes naturales, que el resultado depende de cada persona y no es permanente. Puede causar sensibilidad dental y molestia en las encías por algunos días. Las resinas, coronas y carillas no cambian de color con el blanqueamiento.\n\nMe comprometo a evitar café, té, vino, cigarrillo y alimentos con colorantes los días que me indiquen." . $cierre],
            'ortodoncia' => ['Consentimiento para tratamiento de ortodoncia', 'Tratamiento de ortodoncia',
                $base . "Entiendo que la ortodoncia mueve los dientes con aparatos durante un tiempo aproximado que me informaron, que depende de mi colaboración y de asistir a los controles. Pueden presentarse molestias y llagas, manchas blancas o caries si no hay buena higiene, reabsorción de raíces, problemas de la articulación y que los dientes vuelvan a moverse si no uso los retenedores al terminar." . $cierre],
            'anestesia' => ['Consentimiento para anestesia local', 'Aplicación de anestesia local',
                $base . "Entiendo que la anestesia local adormece la zona por algunas horas. Puede causar dolor en el sitio de la inyección, morado, aceleración del corazón y, rara vez, reacción alérgica o adormecimiento prolongado. Informé si estoy embarazada, si tengo enfermedades del corazón, presión alta o alergias." . $cierre],
            'menor' => ['Consentimiento del acudiente (paciente menor de edad)', 'Tratamiento odontológico del menor',
                "Yo, {firmante}, en calidad de acudiente o representante legal del menor {paciente}, identificado(a) con {documento}, autorizo al odontólogo(a) {profesional} de {consultorio} para realizarle el siguiente procedimiento: {procedimiento}.\n\nMe explicaron en qué consiste, sus beneficios, riesgos y alternativas, y pude resolver mis dudas. Me comprometo a acompañar al menor en las citas y a seguir las indicaciones." . $cierre],
        ];
    }

    public static function textoConsentimiento(string $clave, array $datos): string
    {
        $c = self::consentimientos()[$clave] ?? self::consentimientos()['general'];
        $rep = [];
        foreach (['paciente', 'documento', 'procedimiento', 'profesional', 'consultorio', 'fecha', 'ciudad', 'firmante'] as $k) {
            $rep['{' . $k . '}'] = (string) ($datos[$k] ?? '');
        }
        return strtr($c[2], $rep);
    }
}
