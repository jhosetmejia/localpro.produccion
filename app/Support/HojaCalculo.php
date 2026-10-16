<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Lee y escribe hojas de cálculo sin librerías externas.
 * - leer(): .xlsx (Excel), .csv y .txt → filas (arreglos). Las celdas numéricas de Excel llegan como int/float.
 * - xlsx(): arma un .xlsx sencillo (primera fila en negrita) para plantillas y exportaciones.
 */
class HojaCalculo
{
    public const MAX_FILAS = 20000;

    public static function leer(string $ruta, string $extension): array
    {
        $extension = strtolower($extension);
        if ($extension === 'xlsx' || $extension === 'xlsm') return self::leerXlsx($ruta);
        if (in_array($extension, ['csv', 'txt', 'tsv'], true)) return self::leerCsv($ruta);
        if ($extension === 'vcf' || $extension === 'vcard') return self::leerVcf($ruta);
        if ($extension === 'xls') throw new RuntimeException('Ese archivo es de Excel antiguo (.xls). Ábralo en Excel y use "Guardar como" → "Libro de Excel (.xlsx)" o "CSV", y súbalo de nuevo.');
        throw new RuntimeException('Formato no soportado. Suba un archivo de Excel (.xlsx) o CSV.');
    }

    // ---------- Contactos del celular (.vcf / vCard) ----------
    // Lo que exportan Android, iPhone (iCloud) y Google Contactos. Devuelve filas con títulos.
    private static function leerVcf(string $ruta): array
    {
        $txt = file_get_contents($ruta);
        if ($txt === false) throw new RuntimeException('No se pudo leer el archivo de contactos.');
        if (str_starts_with($txt, "\xEF\xBB\xBF")) $txt = substr($txt, 3);
        if (!mb_check_encoding($txt, 'UTF-8')) $txt = mb_convert_encoding($txt, 'UTF-8', 'Windows-1252');
        $txt = str_replace(["\r\n", "\r"], "\n", $txt);
        // Líneas partidas: las que siguen empiezan con espacio (vCard) o la anterior termina en "=" (quoted-printable)
        $txt = preg_replace("/\n[ \t]/", '', $txt);
        $txt = preg_replace("/=\n/", '', $txt);
        if (stripos($txt, 'BEGIN:VCARD') === false) throw new RuntimeException('Ese archivo no tiene contactos. Exporte los contactos como archivo .vcf y súbalo de nuevo.');

        $filas = [['Nombre', 'Celular', 'Correo', 'Dirección']];
        foreach (preg_split('/BEGIN:VCARD/i', $txt) as $bloque) {
            if (!preg_match('/END:VCARD/i', $bloque)) continue;
            $nombre = $n = $correo = $dir = ''; $tels = [];
            foreach (explode("\n", $bloque) as $linea) {
                if (!str_contains($linea, ':')) continue;
                [$cab, $valor] = explode(':', $linea, 2);
                $partes = explode(';', $cab);
                $prop = strtoupper(preg_replace('/^item\d+\./i', '', $partes[0]));
                $cabU = strtoupper($cab);
                if (str_contains($cabU, 'QUOTED-PRINTABLE')) $valor = quoted_printable_decode($valor);
                $valor = trim(str_replace(['\\,', '\\;', '\\n'], [',', ';', ' '], $valor));
                if ($prop === 'FN' && $valor !== '') $nombre = $valor;
                elseif ($prop === 'N' && $valor !== '') { $p = explode(';', $valor); $n = trim(($p[1] ?? '') . ' ' . ($p[2] ?? '') . ' ' . ($p[0] ?? '')); }
                elseif ($prop === 'TEL' && $valor !== '') {
                    // Primero el celular
                    if (str_contains($cabU, 'CELL') || str_contains($cabU, 'MOBILE')) array_unshift($tels, $valor); else $tels[] = $valor;
                }
                elseif ($prop === 'EMAIL' && $correo === '') $correo = $valor;
                elseif ($prop === 'ADR' && $dir === '') $dir = trim(preg_replace('/\s*;+\s*/', ' ', $valor));
            }
            $nombre = $nombre !== '' ? $nombre : preg_replace('/\s+/', ' ', $n);
            if ($nombre === '' && !$tels && $correo === '') continue;
            $filas[] = [$nombre, $tels[0] ?? '', $correo, $dir];
            if (count($filas) > self::MAX_FILAS) break;
        }
        if (count($filas) < 2) throw new RuntimeException('No se encontraron contactos en el archivo.');
        return $filas;
    }

    // ---------- CSV ----------
    private static function leerCsv(string $ruta): array
    {
        $txt = file_get_contents($ruta);
        if ($txt === false) throw new RuntimeException('No se pudo leer el archivo.');
        if (str_starts_with($txt, "\xEF\xBB\xBF")) $txt = substr($txt, 3);
        elseif (str_starts_with($txt, "\xFF\xFE") || str_starts_with($txt, "\xFE\xFF")) $txt = mb_convert_encoding($txt, 'UTF-8', 'UTF-16');
        if (!mb_check_encoding($txt, 'UTF-8')) $txt = mb_convert_encoding($txt, 'UTF-8', 'Windows-1252');
        $txt = str_replace(["\r\n", "\r"], "\n", $txt);

        // Separador: el que más aparezca en la primera línea
        $primera = strtok($txt, "\n") ?: '';
        $sep = ',';
        $mejor = -1;
        foreach ([';', ',', "\t", '|'] as $s) {
            $n = substr_count($primera, $s);
            if ($n > $mejor) { $mejor = $n; $sep = $s; }
        }

        $f = fopen('php://temp', 'r+');
        fwrite($f, $txt);
        rewind($f);
        $filas = [];
        while (($fila = fgetcsv($f, 0, $sep, '"', '')) !== false) {
            if ($fila === [null]) continue;
            $filas[] = array_map(fn ($v) => trim((string) $v), $fila);
            if (count($filas) > self::MAX_FILAS) break;
        }
        fclose($f);
        return self::limpiar($filas);
    }

    // ---------- XLSX ----------
    private static function leerXlsx(string $ruta): array
    {
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('El servidor no puede abrir archivos .xlsx. Guárdelo como CSV y súbalo de nuevo.');
        $zip = new ZipArchive();
        if ($zip->open($ruta) !== true) throw new RuntimeException('El archivo no parece un Excel válido (.xlsx).');

        try {
            // Textos compartidos
            $textos = [];
            if (($xml = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
                $sx = self::xml($xml);
                foreach ($sx->si as $si) {
                    if (isset($si->t)) { $textos[] = (string) $si->t; continue; }
                    $t = '';
                    foreach ($si->r as $r) $t .= (string) $r->t;
                    $textos[] = $t;
                }
            }

            // Primera hoja visible del libro
            $hoja = 'xl/worksheets/sheet1.xml';
            $wb = $zip->getFromName('xl/workbook.xml');
            $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
            if ($wb !== false && $rels !== false) {
                $w = self::xml($wb);
                $rid = null;
                foreach ($w->sheets->sheet ?? [] as $s) {
                    $attr = $s->attributes();
                    if ((string) ($attr['state'] ?? '') === 'hidden') continue;
                    $rid = (string) $s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                    break;
                }
                if ($rid) {
                    foreach (self::xml($rels)->Relationship as $r) {
                        if ((string) $r['Id'] === $rid) {
                            $t = ltrim((string) $r['Target'], '/');
                            $hoja = str_starts_with($t, 'xl/') ? $t : 'xl/' . $t;
                            break;
                        }
                    }
                }
            }
            $xml = $zip->getFromName($hoja);
            if ($xml === false) throw new RuntimeException('No se encontró la hoja con los datos dentro del Excel.');
            $sx = self::xml($xml);

            $filas = [];
            foreach ($sx->sheetData->row ?? [] as $row) {
                $fila = [];
                foreach ($row->c as $c) {
                    $ref = (string) $c['r'];
                    $col = $ref !== '' ? self::columna($ref) : count($fila);
                    $tipo = (string) $c['t'];
                    $v = isset($c->v) ? (string) $c->v : null;
                    if ($tipo === 's') $val = $textos[(int) $v] ?? '';
                    elseif ($tipo === 'inlineStr') { $val = ''; if (isset($c->is->t)) $val = (string) $c->is->t; else foreach ($c->is->r ?? [] as $r) $val .= (string) $r->t; }
                    elseif ($tipo === 'b') $val = $v === '1' ? 'SI' : 'NO';
                    elseif ($tipo === 'str' || $tipo === 'e') $val = (string) $v;
                    elseif ($v === null || $v === '') $val = '';
                    else $val = is_numeric($v) ? ((float) $v == (int) $v && abs((float) $v) < PHP_INT_MAX ? (int) $v : (float) $v) : $v;
                    $fila[$col] = is_string($val) ? trim($val) : $val;
                }
                if ($fila) {
                    $max = max(array_keys($fila));
                    $completa = [];
                    for ($i = 0; $i <= $max; $i++) $completa[] = $fila[$i] ?? '';
                    $filas[] = $completa;
                }
                if (count($filas) > self::MAX_FILAS) break;
            }
            return self::limpiar($filas);
        } finally {
            $zip->close();
        }
    }

    private static function xml(string $txt): \SimpleXMLElement
    {
        $sx = @simplexml_load_string($txt, \SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);
        if ($sx === false) throw new RuntimeException('El Excel está dañado o no se pudo leer.');
        return $sx;
    }

    // "AB12" → 27
    private static function columna(string $ref): int
    {
        preg_match('/^([A-Z]+)/', strtoupper($ref), $m);
        $n = 0;
        foreach (str_split($m[1] ?? 'A') as $ch) $n = $n * 26 + (ord($ch) - 64);
        return $n - 1;
    }

    // Quita filas vacías del final y columnas vacías a la derecha
    private static function limpiar(array $filas): array
    {
        $filas = array_values(array_filter($filas, fn ($f) => count(array_filter($f, fn ($v) => $v !== '' && $v !== null)) > 0));
        return $filas;
    }

    // ---------- Escribir .xlsx ----------
    public static function xlsx(array $filas, string $hoja = 'Productos', array $anchos = []): string
    {
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('ZipArchive no disponible');
        $esc = fn ($s) => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $col = function (int $i) { $s = ''; $i++; while ($i > 0) { $m = ($i - 1) % 26; $s = chr(65 + $m) . $s; $i = intdiv($i - 1, 26); } return $s; };

        $datos = '';
        foreach (array_values($filas) as $r => $fila) {
            $datos .= '<row r="' . ($r + 1) . '">';
            foreach (array_values($fila) as $c => $v) {
                $ref = $col($c) . ($r + 1);
                $estilo = $r === 0 ? ' s="1"' : '';
                if (is_int($v) || is_float($v)) $datos .= '<c r="' . $ref . '"' . $estilo . '><v>' . $v . '</v></c>';
                elseif ($v !== null && $v !== '') $datos .= '<c r="' . $ref . '" t="inlineStr"' . $estilo . '><is><t xml:space="preserve">' . $esc($v) . '</t></is></c>';
            }
            $datos .= '</row>';
        }
        $cols = '';
        if ($anchos) {
            $cols = '<cols>';
            foreach (array_values($anchos) as $i => $w) $cols .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
            $cols .= '</cols>';
        }

        $archivos = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="' . $esc(mb_substr($hoja, 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF16B34A"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs></styleSheet>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>' . $cols . '<sheetData>' . $datos . '</sheetData></worksheet>',
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        foreach ($archivos as $n => $c) $zip->addFromString($n, $c);
        $zip->close();
        $bin = file_get_contents($tmp);
        @unlink($tmp);
        return $bin;
    }
}
