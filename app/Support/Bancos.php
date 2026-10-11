<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Bancos y billeteras digitales por país, para saber dónde se le paga a cada asesor.
 * Se guarda en asesores.banco como texto: "Bancolombia · Ahorros · 123456789".
 */
class Bancos
{
    // Billeteras que se identifican con correo o usuario (no con celular)
    public const CON_CORREO = ['PayPal', 'Binance (USDT)', 'Zelle', 'Wise', 'Payoneer', 'Venmo', 'Cash App'];

    public const INTERNACIONAL = ['PayPal', 'Binance (USDT)', 'Wise', 'Payoneer'];

    public const LISTA = [
        'CO' => [
            'billeteras' => ['Nequi', 'Daviplata', 'Llave Bre-B', 'Nu Colombia', 'Lulo Bank', 'Movii', 'Dale!', 'Ualá', 'RappiPay', 'Banco Pibank'],
            'bancos' => ['Bancolombia', 'Banco de Bogotá', 'Davivienda', 'BBVA Colombia', 'Banco de Occidente', 'Banco Popular', 'Banco AV Villas', 'Banco Caja Social', 'Banco Agrario', 'Scotiabank Colpatria', 'Itaú', 'Banco GNB Sudameris', 'Banco Falabella', 'Banco Pichincha', 'Bancoomeva', 'Banco Finandina', 'Banco W', 'Bancamía', 'Banco Mundo Mujer', 'Banco Serfinanza', 'Banco Coopcentral', 'Confiar Cooperativa', 'Banco Santander Colombia', 'Mibanco'],
        ],
        'CR' => [
            'billeteras' => ['SINPE Móvil'],
            'bancos' => ['Banco Nacional', 'Banco de Costa Rica', 'BAC Credomatic', 'Banco Popular', 'Davivienda', 'Scotiabank', 'Promerica', 'Banco Lafise', 'Coopealianza', 'Mucap', 'Coopenae'],
        ],
        'SV' => [
            'billeteras' => ['Tigo Money', 'Chivo Wallet', 'N1co'],
            'bancos' => ['Banco Agrícola', 'Banco Cuscatlán', 'Davivienda', 'BAC Credomatic', 'Banco Promerica', 'Banco Hipotecario', 'Banco Azul', 'Banco Industrial', 'Banco Atlántida', 'Banco de Fomento Agropecuario'],
        ],
        'EC' => [
            'billeteras' => ['Deuna', 'PeiGo', 'Payphone'],
            'bancos' => ['Banco Pichincha', 'Banco Guayaquil', 'Produbanco', 'Banco del Pacífico', 'Banco Bolivariano', 'Banco Internacional', 'Banco del Austro', 'Banco de Loja', 'Banco de Machala', 'Cooperativa JEP', 'BanEcuador'],
        ],
        'PE' => [
            'billeteras' => ['Yape', 'Plin', 'Tunki', 'Bim'],
            'bancos' => ['BCP', 'Interbank', 'BBVA Perú', 'Scotiabank Perú', 'BanBif', 'Banco de la Nación', 'Banco Pichincha', 'Mibanco', 'Caja Arequipa', 'Caja Huancayo', 'Caja Piura'],
        ],
        'MX' => [
            'billeteras' => ['Mercado Pago', 'Spin by OXXO', 'Nu México', 'Stori', 'Klar'],
            'bancos' => ['BBVA México', 'Santander', 'Banorte', 'Banamex', 'HSBC', 'Scotiabank', 'Inbursa', 'Banco Azteca', 'BanCoppel', 'Afirme', 'Banregio', 'Hey Banco'],
        ],
        'CL' => [
            'billeteras' => ['MACH', 'Mercado Pago', 'Tenpo', 'Tapp'],
            'bancos' => ['BancoEstado (CuentaRUT)', 'Banco de Chile', 'Santander', 'BCI', 'Scotiabank', 'Itaú', 'Banco Security', 'Banco Falabella', 'Banco Ripley', 'Banco BICE', 'Coopeuch'],
        ],
        'AR' => [
            'billeteras' => ['Mercado Pago', 'Ualá', 'Naranja X', 'Brubank', 'Personal Pay', 'Cuenta DNI'],
            'bancos' => ['Banco Nación', 'Banco Provincia', 'Banco Galicia', 'Santander', 'BBVA', 'Banco Macro', 'ICBC', 'Banco Credicoop', 'Banco Ciudad', 'Banco Patagonia', 'Banco Supervielle'],
        ],
        'VE' => [
            'billeteras' => ['Pago Móvil', 'Zelle', 'Binance (USDT)', 'Zinli', 'Reserve'],
            'bancos' => ['Banco de Venezuela', 'Banesco', 'Mercantil', 'BBVA Provincial', 'BNC', 'Bancamiga', 'Banco del Tesoro', 'Banco Bicentenario', 'Banco Exterior', 'Banplus', 'Bancaribe'],
        ],
        'BO' => [
            'billeteras' => ['Tigo Money', 'Soli', 'Yolo Pago'],
            'bancos' => ['Banco Nacional de Bolivia (BNB)', 'Banco Unión', 'Banco Mercantil Santa Cruz', 'BCP Bolivia', 'Banco BISA', 'Banco Ganadero', 'Banco Económico', 'BancoSol', 'Banco FIE'],
        ],
        'PY' => [
            'billeteras' => ['Tigo Money', 'Personal Pay', 'Wally', 'Zimple'],
            'bancos' => ['ueno bank', 'Itaú', 'Banco Continental', 'Banco GNB', 'Banco Familiar', 'Banco Basa', 'Sudameris', 'Banco Atlas', 'Visión Banco'],
        ],
        'UY' => [
            'billeteras' => ['Prex', 'Mercado Pago', 'Midinero'],
            'bancos' => ['BROU', 'Itaú', 'Santander', 'Scotiabank', 'BBVA', 'HSBC', 'Banco Heritage', 'BHU'],
        ],
        'PA' => [
            'billeteras' => ['Yappy', 'Nequi Panamá'],
            'bancos' => ['Banco General', 'Banistmo', 'BAC Credomatic', 'Banco Nacional de Panamá', 'Caja de Ahorros', 'Global Bank', 'Multibank', 'Credicorp Bank', 'Towerbank', 'Banesco Panamá'],
        ],
        'GT' => [
            'billeteras' => ['Tigo Money'],
            'bancos' => ['Banrural', 'Banco Industrial', 'G&T Continental', 'BAM', 'BAC Credomatic', 'Banco Promerica', 'Bantrab', 'Interbanco', 'Ficohsa'],
        ],
        'HN' => [
            'billeteras' => ['Tigo Money', 'Tengo'],
            'bancos' => ['Banco Atlántida', 'BAC Credomatic', 'Ficohsa', 'Banpaís', 'Banco de Occidente', 'Davivienda', 'Banrural', 'Banco Lafise'],
        ],
        'NI' => [
            'billeteras' => ['Tigo Money'],
            'bancos' => ['BANPRO', 'BAC Credomatic', 'Lafise Bancentro', 'Ficohsa', 'Avanz', 'BDF'],
        ],
        'DO' => [
            'billeteras' => ['tPago', 'Qik'],
            'bancos' => ['Banco Popular', 'Banreservas', 'Banco BHD', 'Scotiabank', 'Banco Santa Cruz', 'Banco Promerica', 'APAP', 'Banesco', 'Banco Caribe', 'Banco Vimenca'],
        ],
        'ES' => [
            'billeteras' => ['Bizum', 'Revolut'],
            'bancos' => ['Santander', 'BBVA', 'CaixaBank', 'Banco Sabadell', 'Bankinter', 'ING', 'Unicaja', 'Abanca', 'Kutxabank', 'Openbank', 'Ibercaja'],
        ],
        'US' => [
            'billeteras' => ['Zelle', 'Cash App', 'Venmo'],
            'bancos' => ['Chase', 'Bank of America', 'Wells Fargo', 'Citibank', 'Capital One', 'U.S. Bank', 'PNC', 'TD Bank', 'Truist'],
        ],
    ];

    public static function tipos(?string $pais): array
    {
        $t = ['ahorros' => 'Ahorros', 'corriente' => 'Corriente'];
        if ($pais === 'CL') $t['vista'] = 'Vista / CuentaRUT';
        if ($pais === 'MX') $t = ['debito' => 'Débito', 'clabe' => 'CLABE', 'ahorros' => 'Ahorros'];
        if ($pais === 'AR') $t = ['ahorros' => 'Caja de ahorros', 'corriente' => 'Cuenta corriente'];
        if ($pais === 'US') $t = ['ahorros' => 'Savings (ahorros)', 'corriente' => 'Checking (corriente)'];
        return $t;
    }

    /** Todo lo que necesita el formulario, por país */
    public static function todas(): array
    {
        $r = [];
        foreach (self::LISTA as $pais => $l) {
            $r[$pais] = [
                'billeteras' => $l['billeteras'],
                'bancos' => $l['bancos'],
                'tipos' => self::tipos($pais),
            ];
        }
        $r['_'] = ['internacional' => self::INTERNACIONAL, 'correo' => self::CON_CORREO, 'tipos' => self::tipos(null)];
        return $r;
    }

    public static function esBilletera(string $entidad): bool
    {
        if (in_array($entidad, self::INTERNACIONAL, true)) return true;
        foreach (self::LISTA as $l) if (in_array($entidad, $l['billeteras'], true)) return true;
        return false;
    }

    public static function reglas(): array
    {
        return [
            'pago_entidad' => 'nullable|string|max:60',
            'pago_otro' => 'nullable|string|max:60',
            'pago_tipo' => 'nullable|string|max:30',
            'pago_numero' => 'nullable|string|max:60',
            'pago_titular' => 'nullable|string|max:80',
        ];
    }

    /** Arma el texto que se guarda: "Bancolombia · Ahorros · 123456 · Titular" */
    public static function componer(Request $request): ?string
    {
        $entidad = trim((string) $request->input('pago_entidad'));
        if ($entidad === '_otro') $entidad = trim((string) $request->input('pago_otro'));
        $numero = trim((string) $request->input('pago_numero'));
        if ($entidad === '' && $numero === '') {
            // Formularios viejos que aún mandan "banco" como texto libre
            $libre = trim((string) $request->input('banco'));
            return $libre !== '' ? mb_substr($libre, 0, 120) : null;
        }
        $partes = [$entidad ?: 'Cuenta'];
        if (!self::esBilletera($entidad)) {
            $tipos = self::tipos($request->input('pais'));
            $t = $request->input('pago_tipo');
            if ($t && isset($tipos[$t])) $partes[] = $tipos[$t];
        }
        if ($numero !== '') $partes[] = $numero;
        $titular = trim((string) $request->input('pago_titular'));
        if ($titular !== '') $partes[] = 'Titular: ' . $titular;
        return mb_substr(implode(' · ', $partes), 0, 120);
    }

    /** Separa el texto guardado para volver a llenar el formulario */
    public static function partir(?string $banco, ?string $pais): array
    {
        $r = ['entidad' => '', 'otro' => '', 'tipo' => '', 'numero' => '', 'titular' => ''];
        $banco = trim((string) $banco);
        if ($banco === '') return $r;
        $partes = array_map('trim', explode('·', $banco));
        foreach ($partes as $i => $p) {
            if (str_starts_with($p, 'Titular:')) { $r['titular'] = trim(substr($p, 8)); unset($partes[$i]); }
        }
        $partes = array_values($partes);
        $conocidas = array_merge(self::INTERNACIONAL, ...array_map(fn ($l) => array_merge($l['billeteras'], $l['bancos']), array_values(self::LISTA)));
        if (count($partes) === 1) {
            // Texto libre de antes, ej "Nequi 300 123 4567"
            foreach ($conocidas as $c) {
                if (stripos($partes[0], $c) === 0) { $r['entidad'] = $c; $r['numero'] = trim(substr($partes[0], strlen($c)), " :-"); return $r; }
            }
            $r['entidad'] = '_otro'; $r['otro'] = $partes[0];
            return $r;
        }
        $ent = $partes[0];
        if (in_array($ent, $conocidas, true)) $r['entidad'] = $ent; else { $r['entidad'] = '_otro'; $r['otro'] = $ent; }
        $tipos = self::tipos($pais);
        if (count($partes) >= 3) {
            $k = array_search($partes[1], $tipos, true);
            $r['tipo'] = $k !== false ? $k : '';
            $r['numero'] = $partes[2];
        } else {
            $k = array_search($partes[1], $tipos, true);
            if ($k !== false) $r['tipo'] = $k; else $r['numero'] = $partes[1];
        }
        return $r;
    }
}
