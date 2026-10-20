<?php

namespace App\Support;

use App\Models\Documento;
use App\Models\Persona;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Convierte cualquier Excel/CSV de personas en clientes.
 * Busca solo las columnas importantes (nombre, cédula, celular, correo, dirección) por el título
 * o, si no tiene títulos, por lo que hay escrito (correos con @, celulares de 10 dígitos, cédulas…).
 */
class ImportarClientes
{
    public const CAMPOS = [
        'nombre' => 'Nombre',
        'apellido' => 'Apellido',
        'documento' => 'Cédula / NIT',
        'telefono' => 'Celular / Teléfono',
        'email' => 'Correo',
        'direccion' => 'Dirección',
    ];

    private const SINONIMOS = [
        'apellido' => ['apellido', 'apellidos', 'last name', 'lastname', 'surname', 'primer apellido'],
        'nombre' => ['nombre', 'nombres', 'nombre completo', 'cliente', 'razon social', 'contacto', 'name', 'full name', 'first name', 'persona', 'nombre cliente', 'nombre y apellido', 'nombres y apellidos', 'proveedor', 'nombre proveedor', 'empresa', 'nombre empresa', 'nombre o empresa'],
        'documento' => ['cedula', 'cc', 'c c', 'documento', 'nit', 'identificacion', 'numero de documento', 'no documento', 'num documento', 'dni', 'id', 'rut', 'ruc', 'n documento', 'doc', 'nro documento'],
        'telefono' => ['celular', 'telefono', 'tel', 'cel', 'movil', 'whatsapp', 'wpp', 'phone', 'mobile', 'numero', 'numero celular', 'telefono celular', 'contacto telefono'],
        'email' => ['correo', 'email', 'e mail', 'mail', 'correo electronico', 'e-mail'],
        'direccion' => ['direccion', 'address', 'domicilio', 'barrio', 'ubicacion', 'direccion de residencia', 'residencia'],
    ];

    private static function limpiar(?string $s): string
    {
        $s = mb_strtolower(trim((string) $s));
        $s = Str::ascii($s);
        $s = preg_replace('/[^a-z0-9@]+/', ' ', $s);
        return trim($s);
    }

    /** Analiza las filas y devuelve: fila de títulos, columnas detectadas y datos. */
    public static function analizar(array $filas): array
    {
        $filas = array_values(array_filter($filas, fn ($f) => is_array($f) && count(array_filter($f, fn ($v) => trim((string) $v) !== '')) > 0));
        if (!$filas) return ['titulos' => [], 'mapa' => [], 'datos' => []];

        $ancho = max(array_map('count', $filas));
        // ¿La primera fila son títulos?
        $mapa = self::porTitulos($filas[0]);
        $conTitulos = count($mapa) > 0;
        $titulos = $conTitulos ? array_map(fn ($v) => trim((string) $v), $filas[0]) : [];
        $datos = $conTitulos ? array_slice($filas, 1) : $filas;

        // Columnas que no se encontraron por título: se adivinan por el contenido
        $muestra = array_slice($datos, 0, 60);
        foreach (['email', 'telefono', 'documento', 'nombre', 'direccion'] as $campo) {
            if (isset($mapa[$campo])) continue;
            $mejor = null; $puntos = 0;
            for ($c = 0; $c < $ancho; $c++) {
                if (in_array($c, $mapa, true)) continue;
                $p = self::puntaje($campo, array_map(fn ($f) => trim((string) ($f[$c] ?? '')), $muestra));
                if ($p > $puntos) { $puntos = $p; $mejor = $c; }
            }
            if ($mejor !== null && $puntos >= 0.5) $mapa[$campo] = $mejor;
        }

        if (!$titulos) for ($c = 0; $c < $ancho; $c++) $titulos[$c] = 'Columna ' . self::letra($c);
        return ['titulos' => $titulos, 'mapa' => $mapa, 'datos' => $datos];
    }

    private static function porTitulos(array $fila): array
    {
        $mapa = [];
        foreach ($fila as $c => $v) {
            $t = self::limpiar($v);
            if ($t === '') continue;
            foreach (self::SINONIMOS as $campo => $lista) {
                if (isset($mapa[$campo])) continue;
                // Google Contactos trae "Phone 1 - Label" antes de "Phone 1 - Value": el tipo no es el número
                // También "Phonetic First Name" (contiene "phone" pero es otra cosa)
                if (in_array($campo, ['telefono', 'email', 'direccion'], true) && preg_match('/\b(label|type|tipo|etiqueta|phonetic)\b/', $t)) continue;
                foreach ($lista as $s) {
                    if ($t === $s || (str_contains($t, $s) && strlen($s) > 3)) {
                        // "Nombres y apellidos" es nombre completo; "Apellidos" solo es apellido
                        if ($campo === 'apellido' && str_contains($t, 'nombre')) continue 2;
                        if ($campo === 'nombre' && str_contains($t, 'apellido') && !str_contains($t, 'nombre')) continue 2;
                        $mapa[$campo] = $c;
                        continue 3;
                    }
                }
            }
        }
        return $mapa;
    }

    // Qué tanto parece una columna ser de cierto campo (0 a 1)
    private static function puntaje(string $campo, array $valores): float
    {
        $valores = array_values(array_filter($valores, fn ($v) => $v !== ''));
        if (!$valores) return 0;
        $ok = 0;
        foreach ($valores as $v) {
            $d = preg_replace('/\D/', '', $v);
            $ok += match ($campo) {
                'email' => filter_var($v, FILTER_VALIDATE_EMAIL) ? 1 : 0,
                'telefono' => preg_match('/^(57)?3\d{9}$/', $d) ? 1 : 0,
                'documento' => (preg_match('/^\d{5,11}(-\d)?$/', preg_replace('/[.\s]/', '', $v)) && !preg_match('/^(57)?3\d{9}$/', $d)) ? 1 : 0,
                'nombre' => (preg_match('/^[\p{L}][\p{L}\s.\'-]{2,}$/u', $v) && !preg_match('/\d/', $v)) ? 1 : 0,
                'direccion' => (preg_match('/\d/', $v) && preg_match('/\p{L}/u', $v) && preg_match('/(calle|cra|carrera|cl|kr|av|avenida|diagonal|transversal|mz|manzana|casa|barrio|#|no\.?)/i', $v)) ? 1 : 0,
                default => 0,
            };
        }
        return $ok / count($valores);
    }

    public static function letra(int $c): string
    {
        $s = '';
        for ($c++; $c > 0; $c = intdiv($c - 1, 26)) $s = chr(65 + ($c - 1) % 26) . $s;
        return $s;
    }

    // Quita emojis, símbolos y caracteres invisibles de los nombres de contactos del celular
    private static function nombreLimpio(string $t): string
    {
        $t = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{FE0F}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}]/u', '', $t);
        $t = preg_replace('/[^\p{L}\p{N}\s\.\'&-]/u', ' ', $t);
        $t = trim(preg_replace('/\s+/', ' ', $t), " .-'");
        return preg_match('/\p{L}/u', $t) ? $t : '';
    }

    /** Arma los clientes con el mapa elegido. */
    public static function filas(array $datos, array $mapa): array
    {
        $val = fn ($f, $campo) => isset($mapa[$campo]) && $mapa[$campo] !== '' ? trim((string) ($f[(int) $mapa[$campo]] ?? '')) : '';
        $out = [];
        foreach ($datos as $f) {
            $nombre = self::nombreLimpio($val($f, 'nombre') . ' ' . $val($f, 'apellido'));
            // Varios números en la misma celda ("+57 300… ::: 315…"): se toma el primero
            $telTxt = preg_split('/\s*(?::::|,|;|\/|\|)\s*/', $val($f, 'telefono'))[0] ?? '';
            $tel = preg_replace('/\D/', '', $telTxt);
            if (strlen($tel) === 12 && str_starts_with($tel, '57')) $tel = substr($tel, 2);
            // Varios números pegados o con extensión ("602 7345678 - 300 1234567", "ext. 12"): se toma el celular o los primeros 10
            if (strlen($tel) > 12) $tel = preg_match('/3\d{9}/', $tel, $cel) ? $cel[0] : substr($tel, 0, 10);
            // Contacto sin nombre pero con celular: se guarda igual para no perderlo
            if ($nombre === '' && strlen($tel) >= 7) $nombre = 'Contacto ' . $tel;
            $email = mb_strtolower($val($f, 'email'));
            $out[] = [
                'nombre' => mb_substr(Str::title(mb_strtolower($nombre)), 0, 80),
                'documento' => mb_substr(preg_replace('/[^0-9A-Za-z\-]/', '', $val($f, 'documento')), 0, 20),
                'telefono' => mb_substr($tel, 0, 20),
                'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? mb_substr($email, 0, 120) : '',
                'direccion' => mb_substr($val($f, 'direccion'), 0, 80),
            ];
        }
        return $out;
    }

    /**
     * Crea los clientes (o proveedores con $relacion = 'proveedore'). No duplica: si ya existe la cédula,
     * el celular o el correo entre los de ese tipo, lo salta. Un proveedor que ya existe como cliente
     * (misma cédula/NIT) se marca también como proveedor, sin crear otra persona.
     */
    public static function guardar(array $clientes, string $relacion = 'cliente'): array
    {
        $docCC = Documento::orderByRaw("tipo_documento = 'Cédula de ciudadanía' desc")->value('id');
        $docNIT = Documento::where('tipo_documento', 'NIT')->value('id') ?: $docCC;
        $existentes = Persona::whereHas($relacion)->get(['numero_documento', 'telefono', 'email']);
        $docs = $existentes->pluck('numero_documento')->filter()->map(fn ($v) => mb_strtolower($v))->flip()->all();
        $tels = $existentes->pluck('telefono')->filter()->flip()->all();
        $mails = $existentes->pluck('email')->filter()->map(fn ($v) => mb_strtolower($v))->flip()->all();
        $todosDocs = Persona::pluck('numero_documento')->map(fn ($v) => mb_strtolower($v))->flip()->all();

        $r = ['creados' => 0, 'repetidos' => 0, 'sin_datos' => 0];
        DB::transaction(function () use ($clientes, $docCC, $docNIT, $relacion, &$docs, &$tels, &$mails, &$todosDocs, &$r) {
            foreach ($clientes as $c) {
                if ($c['nombre'] === '' || ($c['documento'] === '' && $c['telefono'] === '' && $c['email'] === '')) {
                    $r['sin_datos']++;
                    continue;
                }
                $dk = mb_strtolower($c['documento']);
                if (($dk !== '' && isset($docs[$dk])) || ($c['telefono'] !== '' && isset($tels[$c['telefono']])) || ($c['email'] !== '' && isset($mails[$c['email']]))) {
                    $r['repetidos']++;
                    continue;
                }
                $numero = mb_substr($c['documento'] !== '' ? $c['documento'] : ($c['telefono'] !== '' ? 'TEL-' . $c['telefono'] : 'IMP-' . Str::upper(Str::random(8))), 0, 20);
                if (isset($todosDocs[mb_strtolower($numero)])) {
                    // Ya existe la persona con otro papel (ej. es cliente y ahora se sube como proveedor): se le agrega este
                    $ya = $relacion !== 'cliente' ? Persona::where('numero_documento', $numero)->whereDoesntHave($relacion)->first() : null;
                    if ($ya) { $ya->$relacion()->create(['persona_id' => $ya->id]); $r['creados']++; }
                    else $r['repetidos']++;
                    continue;
                }
                $esNit = str_contains($c['documento'], '-') || preg_match('/^[89]\d{8}$/', $c['documento']);
                try {
                    DB::transaction(function () use ($c, $esNit, $docNIT, $docCC, $numero, $relacion) {
                        $p = Persona::create([
                            'razon_social' => mb_substr($c['nombre'], 0, 80),
                            'direccion' => mb_substr($c['direccion'], 0, 80),
                            'telefono' => $c['telefono'] ?: null,
                            'email' => $c['email'] ?: null,
                            'tipo_persona' => $esNit ? 'juridica' : 'natural',
                            'documento_id' => $esNit ? $docNIT : $docCC,
                            'numero_documento' => $numero,
                        ]);
                        $p->$relacion()->create(['persona_id' => $p->id]);
                    });
                } catch (\Throwable $e) {
                    report($e);
                    $r['errores'] = ($r['errores'] ?? 0) + 1;
                    $r['error'] ??= $c['nombre'] . ': ' . mb_substr($e->getMessage(), 0, 160);
                    continue;
                }
                if ($dk !== '') $docs[$dk] = 1;
                if ($c['telefono'] !== '') $tels[$c['telefono']] = 1;
                if ($c['email'] !== '') $mails[$c['email']] = 1;
                $todosDocs[mb_strtolower($numero)] = 1;
                $r['creados']++;
            }
        });
        return $r;
    }
}
