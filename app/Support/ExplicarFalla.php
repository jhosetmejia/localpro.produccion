<?php

namespace App\Support;

use App\Models\ErrorCliente;
use Illuminate\Support\Str;

/**
 * Traduce un error técnico del navegador a palabras: qué intentaba hacer la persona, qué pasó y en qué equipo.
 * Se usa en Admin → Fallas.
 */
class ExplicarFalla
{
    public static function de(ErrorCliente $e): array
    {
        $d = is_array($e->datos) ? $e->datos : [];
        $m = (string) $e->mensaje;
        $fase = $d['fase'] ?? null;
        $pag = (string) $e->pagina;

        // ¿Qué intentaba hacer?
        [$icono, $intento] = match (true) {
            $fase === 'agregar' || Str::startsWith($m, 'No abrió un archivo') => ['📥', 'Agregar un video o una foto al editor'],
            $fase === 'exportar' || Str::startsWith(Str::lower($m), 'exportar:') => ['🎬', 'Exportar (guardar) su video terminado'],
            $fase === 'subtitulos' => ['💬', 'Ponerle subtítulos automáticos al video'],
            $fase === 'texto_a_voz' || Str::startsWith($m, 'Texto a voz') => ['🗣️', 'Convertir un texto en voz'],
            Str::contains($m, 'volver de otra app') => ['📱', 'Seguir editando después de salir a otra app'],
            Str::startsWith($m, 'Vista previa') || $fase === 'reproducir' => ['▶️', 'Ver su video en el editor (vista previa)'],
            Str::startsWith($m, 'El editor no arrancó') => ['🚫', 'Abrir el editor de video'],
            $e->modulo === 'cut' => ['✂️', 'Usar el editor de video LocalProCut'],
            $e->modulo === 'panel' => ['🧾', 'Usar el panel' . self::seccion($pag)],
            default => ['🌐', 'Ver una página de LocalPro' . self::seccion($pag)],
        };

        // ¿Qué pasó? (en palabras)
        $l = Str::lower($m);
        $paso = match (true) {
            Str::contains($l, ['decoding error', 'decoder error', 'decode']) => 'El celular no pudo leer una parte del video (video muy pesado, formato raro o con partes dañadas).',
            Str::contains($l, ['formato no compatible', 'no se puede convertir', 'not supported', 'notsupportederror', 'src not supported']) => 'El celular no reconoce el formato de ese archivo.',
            Str::contains($l, ['memoria', 'out of memory', 'quotaexceeded', 'allocation']) => 'Al celular se le acabó la memoria o el espacio.',
            Str::contains($l, ['notallowederror', 'permission', 'permiso']) => 'No dio permiso (cámara, micrófono o archivos).',
            Str::contains($l, ['failed to fetch', 'networkerror', 'network', 'load failed', 'no se pudo bajar']) => 'Se le cayó o le falló el internet en ese momento.',
            Str::contains($l, ['se queda pegada']) => 'La imagen se quedaba quieta mientras el video seguía sonando.',
            Str::contains($l, ['no arranca', 'se detiene']) => 'El video no arrancaba o se paraba solo.',
            Str::contains($l, ['negra']) => 'La pantalla quedaba negra (sonaba pero no se veía).',
            Str::contains($l, ['cancelado']) => 'La persona canceló.',
            Str::contains($l, ['undefined', 'null', 'is not a function', 'cannot read']) => 'Falla del programa (un error nuestro en el código).',
            default => 'Falla: ' . Str::limit($m, 160),
        };

        $equipo = self::equipo((string) $e->navegador);
        $det = [];
        if (!empty($d['ext']) || !empty($d['tipo'])) $det['Archivo'] = trim(($d['ext'] ?? '') . ' ' . (($d['tipo'] ?? '?') !== '?' ? '(' . $d['tipo'] . ')' : ''));
        if (isset($d['mb'])) $det['Tamaño'] = $d['mb'] . ' MB';
        if (!empty($d['px']) && $d['px'] !== '0x0') $det['Resolución del video'] = $d['px'];
        if (!empty($d['res'])) $det['Exportando en'] = $d['res'] . (isset($d['fps']) ? ' · ' . $d['fps'] . ' fps' : '');
        if (!empty($d['real']) || !empty($d['como'])) $det['Detectado como'] = ($d['real'] ?? '?') . ' → se abrió como ' . ($d['como'] ?? '?');
        if (!empty($d['voz'])) $det['Voz'] = $d['voz'];
        if (!empty($d['idioma'])) $det['Idioma'] = $d['idioma'];
        if (array_key_exists('ios', $d)) $det['iPhone'] = $d['ios'] ? 'sí' : 'no';
        if (preg_match('/\(cuadro (\d+)\)/', $m, $c)) $det['Se trabó en'] = 'el cuadro ' . $c[1];

        return compact('icono', 'intento', 'paso', 'equipo', 'det');
    }

    private static function seccion(string $pag): string
    {
        $p = trim(parse_url($pag, PHP_URL_PATH) ?: '', '/');
        return $p !== '' ? ' (' . Str::limit($p, 40) . ')' : '';
    }

    /** "Android 13 · Xiaomi 2201117TG · Chrome 131" a partir del navegador */
    public static function equipo(string $ua): string
    {
        if ($ua === '') return 'Equipo desconocido';
        $so = match (true) {
            (bool) preg_match('/iPhone OS ([\d_]+)/', $ua, $m) => 'iPhone · iOS ' . str_replace('_', '.', $m[1]),
            (bool) preg_match('/iPad.*OS ([\d_]+)/', $ua, $m) => 'iPad · iOS ' . str_replace('_', '.', $m[1]),
            (bool) preg_match('/Android ([\d.]+)(?:; ([^;)]+))?/', $ua, $m) => 'Android ' . $m[1] . (!empty($m[2]) && $m[2] !== 'K' ? ' · ' . trim($m[2]) : ''),
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS X') => 'Mac',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Otro',
        };
        $nav = match (true) {
            (bool) preg_match('/SamsungBrowser\/([\d]+)/', $ua, $m) => 'Samsung Internet ' . $m[1],
            (bool) preg_match('/EdgA?\/([\d]+)/', $ua, $m) => 'Edge ' . $m[1],
            (bool) preg_match('/(?:CriOS|Chrome)\/([\d]+)/', $ua, $m) => 'Chrome ' . $m[1],
            (bool) preg_match('/FxiOS\/([\d]+)|Firefox\/([\d]+)/', $ua, $m) => 'Firefox ' . ($m[1] ?: ($m[2] ?? '')),
            (bool) preg_match('/Version\/([\d.]+).*Safari/', $ua, $m) => 'Safari ' . $m[1],
            default => 'navegador',
        };
        if (str_contains($ua, 'wv)') || str_contains($ua, 'FBAN') || str_contains($ua, 'Instagram')) $nav .= ' (dentro de una app)';
        return $so . ' · ' . $nav;
    }
}
