<?php

namespace App\Support;

use App\Models\Ajuste;

/**
 * Moneda con la que trabaja cada país en LocalPro (tienda, plan, pagos y comisiones).
 * - Colombia y el resto: pesos colombianos (COP), como siempre.
 * - Venezuela: como se trabaja allá. Precios, reportes, plan y comisiones en DÓLARES (moneda estable), y se
 *   cobra en dólares o en BOLÍVARES a la tasa del día: la caja muestra el total en $ y en Bs, los métodos de
 *   pago en Bs (Pago Móvil, punto de venta, efectivo en Bs) reciben bolívares y el sistema los pasa a dólares.
 *   Tasa general en Admin → Planes y cobros (tasa_ves_usd); cada tienda puede poner la suya desde la caja.
 *
 * Wompi solo cobra en pesos: todo pago en otra moneda se cobra en COP con la tasa "pesos por US$1" (tasa_usd_cop).
 */
class Moneda
{
    public const COP = 'COP';
    public const USD = 'USD';
    public const VES = 'VES';

    // Países que no trabajan en pesos colombianos
    public const PAISES_CONFIGURABLES = ['VE' => 'USD'];

    private static array $cache = [];

    // Lee un ajuste sin romper si la base aún no existe (instalación nueva, migraciones)
    private static function ajuste(string $clave, $defecto)
    {
        try {
            return Ajuste::valor($clave, $defecto);
        } catch (\Throwable $e) {
            return $defecto;
        }
    }

    public static function olvidar(): void
    {
        self::$cache = [];
    }

    // Venezuela trabaja en dólares; los bolívares son una forma de cobro a la tasa del día (ver Tienda::tasaBs)
    public static function deVenezuela(): string
    {
        return self::USD;
    }

    // Cuándo se actualizó la tasa general de bolívares
    public static function tasaVesFecha(): ?\Carbon\Carbon
    {
        $f = self::ajuste('tasa_ves_usd_at', null);
        try {
            return $f ? \Carbon\Carbon::parse($f) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function dePais(?string $pais): string
    {
        return strtoupper((string) $pais) === 'VE' ? self::deVenezuela() : self::COP;
    }

    // Países que hoy NO trabajan en pesos colombianos (para sumar ventas en pesos en el admin)
    public static function paisesExtranjeros(): array
    {
        return array_keys(self::PAISES_CONFIGURABLES);
    }

    // Moneda de quien está usando el sistema: la del asesor (si es de la red) o la de su tienda
    public static function actual(): string
    {
        $u = auth()->user();
        if ($u && ($a = $u->asesor)) {
            return self::dePais($a->pais);
        }
        return self::dePais(TiendaActual::tienda()?->pais);
    }

    public static function esUsd(?string $moneda): bool
    {
        return strtoupper((string) $moneda) === self::USD;
    }

    // Cualquier moneda que no sea el peso colombiano (lleva centavos y se cobra en Wompi convertida)
    public static function esExtranjera(?string $moneda): bool
    {
        return strtoupper((string) ($moneda ?: self::COP)) !== self::COP;
    }

    public static function simbolo(?string $moneda): string
    {
        return match (strtoupper((string) $moneda)) {
            self::USD => 'US$',
            self::VES => 'Bs',
            default => '$',
        };
    }

    // COP: $100.000 · USD: US$25 / US$1,25 · VES: Bs 2.000 / Bs 1.250,50
    public static function formato($valor, ?string $moneda = self::COP, bool $codigo = false): string
    {
        $v = (float) $valor;
        $m = strtoupper((string) ($moneda ?: self::COP));
        $signo = $v < 0 ? '-' : '';
        if ($m === self::USD || $m === self::VES) {
            $dec = abs($v - round($v)) < 0.005 ? 0 : 2;
            $num = number_format(abs($v), $dec, ',', '.');
            $txt = $m === self::USD ? $signo . 'US$' . $num : $signo . 'Bs ' . $num;
            return $codigo ? $txt . ' ' . $m : $txt;
        }
        $txt = $signo . '$' . number_format(abs($v), 0, ',', '.');
        return $codigo ? $txt . ' COP' : $txt;
    }

    // Función de JavaScript equivalente a formato() para usar en las vistas: var fmt = {!! Moneda::js($m) !!};
    public static function js(?string $moneda = self::COP): string
    {
        if (self::esExtranjera($moneda)) {
            $pre = self::esUsd($moneda) ? 'US$' : 'Bs ';
            return "function (n) { var r = Math.round((+n || 0) * 100) / 100, a = Math.abs(r), t = (a % 1 ? a.toFixed(2) : String(Math.round(a))).split('.'); t[0] = t[0].replace(/\\B(?=(\\d{3})+(?!\\d))/g, '.'); return (r < 0 ? '-' : '') + '{$pre}' + t.join(','); }";
        }
        return "function (n) { var r = Math.round(+n || 0); return (r < 0 ? '-$' : '$') + Math.abs(r).toString().replace(/\\B(?=(\\d{3})+(?!\\d))/g, '.'); }";
    }

    // Pesos colombianos por cada dólar (Wompi solo recibe COP). Se cambia en Admin → Planes y cobros.
    public static function tasa(): float
    {
        return self::$cache['tasa'] ??= max(1, (float) self::ajuste('tasa_usd_cop', 4000));
    }

    // Bolívares por cada dólar (0 = sin configurar). Se cambia en Admin → Planes y cobros.
    public static function tasaVes(): float
    {
        return self::$cache['tasaVes'] ??= max(0, (float) self::ajuste('tasa_ves_usd', 0));
    }

    // Cuánto vale 1 unidad de la moneda en dólares
    private static function enUsd(float $v, string $m): float
    {
        return match ($m) {
            self::USD => $v,
            self::VES => self::tasaVes() > 0 ? $v / self::tasaVes() : 0,
            default => $v / self::tasa(),
        };
    }

    // Pasa un valor de una moneda a otra con las tasas del panel
    public static function convertir($valor, ?string $de, ?string $a): float
    {
        $de = strtoupper((string) ($de ?: self::COP));
        $a = strtoupper((string) ($a ?: self::COP));
        $v = (float) $valor;
        if ($de === $a) {
            return $v;
        }
        $usd = self::enUsd($v, $de);
        $r = match ($a) {
            self::USD => $usd,
            self::VES => $usd * self::tasaVes(),
            default => $usd * self::tasa(),
        };
        return $a === self::COP ? round($r) : round($r, 2);
    }

    // Lo que Wompi debe cobrar en pesos por un valor en esa moneda
    public static function aCop($valor, ?string $moneda): float
    {
        return self::convertir($valor, $moneda, self::COP);
    }

    // Expresión SQL que pasa a pesos una columna de valor según su columna de moneda (para sumar ingresos)
    public static function sqlACop(string $monto, string $moneda = 'moneda', ?string $cobroCop = null): string
    {
        $usd = $cobroCop ? "COALESCE($cobroCop, $monto * " . self::tasa() . ")" : "$monto * " . self::tasa();
        $ves = self::tasaVes() > 0 ? "$monto / " . self::tasaVes() . ' * ' . self::tasa() : '0';
        if ($cobroCop) $ves = "COALESCE($cobroCop, $ves)";
        return "CASE WHEN ($moneda) = 'USD' THEN $usd WHEN ($moneda) = 'VES' THEN $ves ELSE $monto END";
    }

    // Suma una columna de un query que tiene columna "moneda", pasando todo a la moneda indicada
    public static function sumar($query, string $columna, ?string $a): float
    {
        return (float) (clone $query)->selectRaw("moneda, SUM($columna) total")->groupBy('moneda')->get()
            ->sum(fn ($r) => self::convertir($r->total, $r->moneda ?: self::COP, $a));
    }

    // Paso de los campos de dinero de esa moneda
    public static function paso(?string $moneda): string
    {
        return self::esExtranjera($moneda) ? '0.01' : '1';
    }
}
