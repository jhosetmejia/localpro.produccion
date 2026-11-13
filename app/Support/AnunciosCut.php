<?php

namespace App\Support;

use App\Models\Ajuste;
use App\Models\Evento;
use App\Models\Tienda;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Anuncios de LocalProCut: salen en el panel de calidad y mientras el video se exporta.
 * El súper administrador los maneja en Admin → LocalProCut (se guardan en el ajuste "cut_anuncios").
 * Si nunca se han guardado, se arman solos: eventos próximos (Marino Exposhow, PFN), chaquetas de Abril Store y LocalPro.
 */
class AnunciosCut
{
    private const MESES = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

    /** Los que ve la gente (sin los vencidos ni apagados) */
    public static function lista(): array
    {
        $hoy = now()->toDateString();
        // Fotos del mismo sitio: solo la ruta (el editor solo carga imágenes de LocalPro, con o sin www)
        $rel = function ($a) {
            $u = $a['img'] ?? null;
            if ($u && preg_match('~^https?://(www\.)?localproo\.com(/.*)$~i', $u, $m)) $a['img'] = $m[2];
            return $a;
        };
        return array_map($rel, array_values(array_filter(self::todos(), fn ($a) => ($a['on'] ?? true) && trim($a['t'] ?? '') !== '' && trim($a['u'] ?? '') !== ''
            && (empty($a['hasta']) || $a['hasta'] >= $hoy))));
    }

    /** Los guardados por el administrador, o los automáticos si no hay */
    public static function todos(): array
    {
        try {
            $g = json_decode((string) Ajuste::valor('cut_anuncios', ''), true);
        } catch (\Throwable $e) {
            $g = null;
        }
        return is_array($g) ? $g : self::automaticos();
    }

    public static function guardar(array $lista): void
    {
        Ajuste::guardar('cut_anuncios', json_encode(array_values($lista), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function restaurar(): void
    {
        Ajuste::guardar('cut_anuncios', '');
    }

    public static function automaticos(): array
    {
        $out = [];
        // Nariño Exposhow: anuncio con diseño de pasarela (sale siempre hasta el día del evento)
        try {
            $e = Evento::withoutGlobalScopes()->where(fn ($q) => $q->where('nombre', 'like', '%exposhow%')->orWhere('nombre', 'like', '%expo show%'))
                ->where('estado', '!=', 'borrador')->where(fn ($q) => $q->whereNull('fecha_evento')->orWhere('fecha_evento', '>=', now()->startOfDay()))
                ->orderBy('fecha_evento')->first();
        } catch (\Throwable $x) { $e = null; }
        $f = $e?->fecha_evento ?: \Illuminate\Support\Carbon::create(2026, 12, 18);
        $out[] = ['e' => '👗', 'd' => 'moda', 't' => 'Nariño Exposhow · ' . $f->day . ' de ' . self::MESES[$f->month],
            's' => 'La pasarela con los mejores modelos de Nariño', 'u' => $e?->url ?: url('/evento/narino-exposhow'),
            'img' => null, 'hasta' => $f->toDateString(), 'on' => true, 'fecha' => $f->day . ' ' . mb_strtoupper(mb_substr(self::MESES[$f->month], 0, 3))];
        // PFN: anuncio con diseño de pelea y el logo (sale siempre hasta el día de la pelea)
        try {
            $e = Evento::withoutGlobalScopes()->where('nombre', 'like', '%pfn%')->where('estado', '!=', 'borrador')
                ->where(fn ($q) => $q->whereNull('fecha_evento')->orWhere('fecha_evento', '>=', now()->startOfDay()))->orderBy('fecha_evento')->first();
        } catch (\Throwable $x) { $e = null; }
        $f = $e?->fecha_evento ?: \Illuminate\Support\Carbon::create(2026, 12, 12);
        $out[] = ['e' => '🥊', 'd' => 'pelea', 't' => 'PFN · ' . $f->day . ' de ' . self::MESES[$f->month],
            's' => 'Los mejores peleadores de Nariño en un solo lugar', 'u' => $e?->url ?: url('/evento/pfn'),
            'img' => null, 'hasta' => $f->toDateString(), 'on' => true,
            'fecha' => $f->day . ' ' . mb_strtoupper(mb_substr(self::MESES[$f->month], 0, 3)) . ' · CC La 17 · 3er piso'];
        try {
            $t = Tienda::query()->where('nombre', 'like', '%abril store%')->first();
            if ($t) {
                $cat = DB::table('caracteristicas')->join('categorias', 'categorias.caracteristica_id', '=', 'caracteristicas.id')
                    ->where('caracteristicas.tienda_id', $t->id)->where('caracteristicas.nombre', 'like', '%chaquet%')->value('caracteristicas.nombre');
                $out[] = ['e' => '🧥', 't' => 'Las mejores chaquetas de Pasto', 's' => 'Abril Store · pídalas por WhatsApp',
                    'u' => $t->url_catalogo_navegador . '?categoria=' . Str::slug($cat ?: 'chaquetas'), 'img' => null, 'hasta' => null, 'on' => true];
            }
        } catch (\Throwable $x) { /* nada */ }
        $out[] = ['e' => '🛍️', 't' => 'Venda más con su tienda en línea', 's' => 'Catálogo con pedidos por WhatsApp para su negocio', 'u' => url('/funciones?desde=cut-anuncio-tienda'), 'img' => null, 'hasta' => null, 'on' => true];
        $out[] = ['e' => '📅', 't' => 'Programe sus videos en redes', 's' => 'Facebook, Instagram y YouTube desde LocalPro', 'u' => url('/funciones?desde=cut-anuncio-redes'), 'img' => null, 'hasta' => null, 'on' => true];
        return $out;
    }
}
