<?php

namespace App\Support\Dian;

use App\Models\Ajuste;
use App\Models\Cliente;
use App\Models\DocumentoElectronico;
use App\Models\PosVenta;
use App\Models\Tienda;
use App\Support\Dian\Proveedores\Http;
use App\Support\Dian\Proveedores\Proveedor;
use App\Support\Dian\Proveedores\Simulado;
use App\Support\TiendaActual;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

// Facturación electrónica DIAN: decide qué documento sale en cada venta, lo arma, lo envía al proveedor y lleva su estado.
class Facturador
{
    // ---------- Proveedor tecnológico (lo configura LocalPro en Admin → Facturación DIAN) ----------

    public static function proveedor(): ?Proveedor
    {
        $tipo = Ajuste::valor('fe_proveedor');
        if ($tipo === 'simulado') return new Simulado();
        if ($tipo === 'api') {
            $url = Ajuste::valor('fe_url');
            $token = self::secreto('fe_token');
            return $url && $token ? new Http($url, $token, TiendaActual::tienda()?->fe_credenciales ?? []) : null;
        }
        return null;
    }

    public static function secreto(string $clave): ?string
    {
        $v = Ajuste::valor($clave);
        if (!$v) return null;
        try { return Crypt::decryptString($v); } catch (Throwable) { return null; }
    }

    public static function nombreProveedor(): string
    {
        return match (Ajuste::valor('fe_proveedor')) {
            'simulado' => 'Modo práctica (sin envío a la DIAN)',
            'api' => Ajuste::valor('fe_nombre') ?: 'Proveedor tecnológico conectado',
            default => 'Sin proveedor (los documentos quedan en cola)',
        };
    }

    // ---------- Reglas del negocio ----------

    // ¿Está obligado a facturar? Responsables de IVA, régimen simple y personas jurídicas sí.
    // La persona natural no responsable de IVA (R-99-PN / O-49) puede entregar tiquete sin valor fiscal.
    public static function obligado(Tienda $t): bool
    {
        $r = self::responsabilidades($t);
        return $t->tipo_persona === 'juridica' || in_array('O-48', $r, true) || in_array('O-47', $r, true) || in_array('O-13', $r, true);
    }

    // IVA con que se crea un producto nuevo: 19 % si el negocio es responsable de IVA
    public static function impuestoPorDefecto(?Tienda $t): string
    {
        return $t && in_array('O-48', self::responsabilidades($t), true) ? 'iva19' : 'ninguno';
    }

    public static function responsabilidades(Tienda $t): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $t->responsabilidades))));
    }

    // Qué documento sale: factura electrónica si el cliente la pide, POS electrónico si está activo, si no tiquete interno
    public static function tipoDocumento(Tienda $t, bool $quiereFe): string
    {
        if ($t->pais && $t->pais !== 'CO') return 'tiquete';
        if ($quiereFe && $t->fe_activa) return 'fe';
        if ($t->pos_electronico) return 'pos_e';
        return 'tiquete';
    }

    // Revisión de lo que le falta al negocio para facturar bien ante la DIAN
    public static function preparacion(Tienda $t): array
    {
        $hoy = today();
        $r = self::responsabilidades($t);
        $nit = Catalogos::nitLimpio($t->nit);
        $sinImpuesto = \App\Models\Producto::where('estado', 1)->where('impuesto', 'ninguno')->count();
        $responsableIva = in_array('O-48', $r, true);
        $posRango = $t->resolucion_hasta ? max(0, (int) $t->resolucion_hasta - max((int) $t->consecutivo_pos, (int) $t->resolucion_desde - 1)) : null;
        $feRango = $t->fe_hasta ? max(0, (int) $t->fe_hasta - max((int) $t->consecutivo_fe, (int) $t->fe_desde - 1)) : null;
        $prov = Ajuste::valor('fe_proveedor');

        $l = [
            ['datos', 'NIT con dígito de verificación', $nit !== '' && $t->dv !== null && $t->dv === Catalogos::dv($nit),
                $nit === '' ? 'Escriba el NIT del RUT.' : ($t->dv === null ? 'Falta el DV.' : ($t->dv !== Catalogos::dv($nit) ? 'El DV no corresponde al NIT (debería ser ' . Catalogos::dv($nit) . ').' : 'NIT ' . $nit . '-' . $t->dv))],
            ['datos', 'Razón social o nombre del titular del RUT', filled($t->razon_social), $t->razon_social ?: 'Tal como aparece en el RUT.'],
            ['datos', 'Tipo de persona', in_array($t->tipo_persona, ['natural', 'juridica'], true), $t->tipo_persona ? ucfirst($t->tipo_persona) : 'Natural o jurídica.'],
            ['datos', 'Responsabilidades fiscales (casilla 53 del RUT)', count($r) > 0, count($r) ? implode(', ', $r) : 'Ej: R-99-PN si no es responsable de IVA, O-48 si lo es.'],
            ['datos', 'Dirección y municipio (código DANE)', filled($t->direccion) && strlen((string) $t->municipio_dane) === 5, filled($t->municipio_dane) ? 'Municipio ' . $t->municipio_dane : 'Pasto es 52001, Bogotá 11001, Medellín 05001, Cali 76001.'],
            ['datos', 'Correo para facturación', filled($t->email_facturacion ?: $t->email), $t->email_facturacion ?: ($t->email ?: 'Donde le llegan copias y respuestas.')],
            ['impuestos', 'IVA definido en sus productos', !$responsableIva || $sinImpuesto === 0,
                $responsableIva ? ($sinImpuesto ? "$sinImpuesto productos sin IVA definido. Póngales IVA 19 %, 5 %, exento o excluido." : 'Todos sus productos tienen IVA definido.') : 'No es responsable de IVA: sus productos van sin IVA.'],
            ['numeracion', 'Resolución POS de la DIAN (documento equivalente)', filled($t->resolucion_numero) && $t->resolucion_prefijo && $t->resolucion_hasta && (!$t->resolucion_vigencia || $t->resolucion_vigencia->gte($hoy)) && $posRango !== 0,
                !$t->resolucion_numero ? 'Pídala gratis en el portal de la DIAN (servicios en línea → numeración de facturación).' : ($t->resolucion_vigencia && $t->resolucion_vigencia->lt($hoy) ? 'Está vencida: solicite una nueva.' : ($posRango !== null ? "Le quedan $posRango números." : 'Complete prefijo y rango.'))],
            ['numeracion', 'Resolución de factura electrónica (si sus clientes piden factura)', !$t->fe_activa || (filled($t->fe_resolucion_numero) && $t->fe_prefijo && $t->fe_hasta && filled($t->fe_clave_tecnica) && (!$t->fe_resolucion_vigencia || $t->fe_resolucion_vigencia->gte($hoy)) && $feRango !== 0),
                $t->fe_activa ? ($feRango !== null ? "Le quedan $feRango números." : 'Complete prefijo, rango y clave técnica.') : 'Opcional: actívela cuando un cliente le pida factura con su NIT.'],
            ['envio', 'Proveedor tecnológico conectado', $prov === 'api', $prov === 'api' ? self::nombreProveedor() : ($prov === 'simulado' ? 'Modo práctica: arma y valida los documentos, pero aún no los envía a la DIAN.' : 'LocalPro lo conecta. Mientras tanto los documentos quedan guardados en cola.')],
            ['envio', 'Habilitación ante la DIAN (set de pruebas aprobado)', $t->fe_ambiente === 'produccion', $t->fe_ambiente === 'produccion' ? 'En producción.' : 'Se hace con el proveedor: se envían documentos de prueba y la DIAN habilita al negocio.'],
        ];
        $ok = count(array_filter($l, fn ($x) => $x[2]));
        return ['lista' => $l, 'ok' => $ok, 'total' => count($l), 'pct' => (int) round($ok * 100 / count($l)), 'obligado' => self::obligado($t)];
    }

    // ---------- Datos del cliente (adquiriente) ----------

    public static function adquiriente(?Cliente $c): array
    {
        $p = $c?->persona;
        $doc = trim((string) $p?->numero_documento);
        if (!$p || $doc === '' || str_starts_with($doc, 'TEL-')) return Catalogos::CONSUMIDOR_FINAL + ['telefono' => $p?->telefono, 'nombre_real' => $p?->razon_social];
        $tipo = Catalogos::tipoDoc($p->documento?->tipo_documento);
        $num = $tipo === '31' ? Catalogos::nitLimpio($doc) : preg_replace('/[^0-9A-Za-z]/', '', $doc);
        return [
            'tipo_doc' => $tipo, 'numero' => $num, 'dv' => $tipo === '31' ? ($p->dv ?? Catalogos::dv($num)) : null,
            'nombre' => $p->razon_social, 'tipo_persona' => $tipo === '31' ? ($p->tipo_persona === 'natural' ? 'natural' : 'juridica') : 'natural',
            'responsabilidad' => $p->responsabilidad ?: 'R-99-PN', 'email' => $p->email, 'direccion' => $p->direccion ?: null,
            'municipio' => $p->municipio_dane, 'telefono' => $p->telefono,
        ];
    }

    // ---------- Documento de una venta ----------

    // Se llama dentro de la transacción de la venta (ya con sus productos guardados)
    public static function registrarVenta(PosVenta $v): ?DocumentoElectronico
    {
        if ($v->tipo_doc === 'tiquete') return null;
        $t = Tienda::find($v->tienda_id);
        [$pref, $cons] = self::partirNumero($v->numero);
        return DocumentoElectronico::create([
            'tienda_id' => $t->id, 'tipo' => $v->tipo_doc, 'pos_venta_id' => $v->id, 'numero' => $v->numero,
            'prefijo' => $pref, 'consecutivo' => $cons, 'estado' => 'pendiente', 'ambiente' => $t->fe_ambiente ?: 'pruebas', 'total' => $v->total,
        ]);
    }

    // Nota crédito electrónica: corrige una factura o POS electrónico (anulación total o devolución parcial)
    public static function notaCredito(PosVenta $v, string $motivo, string $codigoMotivo = '2', ?\App\Models\Devolucion $dev = null, ?array $lineas = null, ?float $total = null): ?DocumentoElectronico
    {
        $orig = DocumentoElectronico::withoutGlobalScopes()->where('pos_venta_id', $v->id)->whereIn('tipo', ['fe', 'pos_e'])->latest('id')->first();
        if (!$orig || $orig->estado === 'rechazado') return null;
        $t = Tienda::whereKey($v->tienda_id)->lockForUpdate()->first();
        $n = (int) DocumentoElectronico::withoutGlobalScopes()->where('tienda_id', $t->id)->where('tipo', 'nc')->max('consecutivo') + 1;
        return DocumentoElectronico::create([
            'tienda_id' => $t->id, 'tipo' => 'nc', 'pos_venta_id' => $v->id, 'devolucion_id' => $dev?->id, 'referencia_id' => $orig->id,
            'numero' => 'NCE-' . $n, 'prefijo' => 'NCE', 'consecutivo' => $n, 'estado' => 'pendiente', 'ambiente' => $orig->ambiente,
            'total' => $total ?? $v->total,
            'payload' => ['motivo' => $motivo, 'codigo_motivo' => $codigoMotivo, 'lineas' => $lineas],
        ]);
    }

    public static function partirNumero(string $numero): array
    {
        return preg_match('/^([A-Za-z]+)-?0*(\d+)$/', $numero, $m) ? [strtoupper($m[1]), (int) $m[2]] : [null, null];
    }

    // Arma el documento completo (formato LocalPro, independiente del proveedor)
    public static function payload(DocumentoElectronico $d): array
    {
        $t = Tienda::find($d->tienda_id);
        $v = PosVenta::withoutGlobalScopes()->with(['items', 'pagos.metodo'])->find($d->pos_venta_id);
        $esNc = $d->tipo === 'nc';
        $orig = $esNc ? $d->referencia : null;
        $extra = $esNc ? ($d->payload ?? []) : [];
        $fecha = ($d->created_at ?? now())->copy()->setTimezone('America/Bogota');

        // Líneas: en la nota crédito parcial solo lo devuelto
        $items = [];
        $lineasNc = $esNc ? collect($extra['lineas'] ?? [])->keyBy('item_id') : null;
        foreach ($v->items as $i => $it) {
            $cant = (float) $it->cantidad;
            // Ventas anteriores a la facturación no tenían el desglose: se usa el subtotal
            $tot = ((float) $it->base + (float) $it->iva_valor) ?: (float) $it->subtotal;
            if ($esNc && $lineasNc->isNotEmpty()) {
                if (!$lineasNc->has($it->id)) continue;
                $f = min(1, (float) $lineasNc[$it->id]['cantidad'] / max($cant, 0.0001));
                $cant = (float) $lineasNc[$it->id]['cantidad'];
                $tot = round($tot * $f, 2);
            }
            if ($tot <= 0 && $cant <= 0) continue;
            [, $tasa, $codigo] = Catalogos::impuesto($it->impuesto);
            $base = $tasa > 0 ? round($tot / (1 + $tasa / 100), 2) : $tot;
            $unidad = optional(optional($it->variante)->producto)->unidad ?: 'und';
            $items[] = [
                'linea' => count($items) + 1,
                'codigo' => optional(optional($it->variante)->producto)->codigo ?: (string) ($it->producto_variante_id ?? $it->id),
                'descripcion' => trim($it->descripcion . ' ' . ($it->talla ? 'T:' . $it->talla : '') . ' ' . ($it->color ?? '')),
                'cantidad' => $cant, 'unidad' => Catalogos::UNIDADES[$unidad] ?? '94',
                'precio_unitario' => $cant > 0 ? round($base / $cant, 4) : 0, 'precio_unitario_con_impuesto' => (float) $it->precio,
                'base' => $base, 'impuesto' => $codigo ? ['codigo' => $codigo, 'tipo' => $it->impuesto, 'tasa' => (float) $tasa, 'valor' => round($tot - $base, 2)] : null,
                'excluido' => $it->impuesto === 'excluido', 'total' => $tot,
            ];
        }
        $base = round(array_sum(array_column($items, 'base')), 2);
        $iva = round(array_sum(array_map(fn ($x) => ($x['impuesto']['codigo'] ?? '') === '01' ? $x['impuesto']['valor'] : 0, $items)), 2);
        $inc = round(array_sum(array_map(fn ($x) => ($x['impuesto']['codigo'] ?? '') === '04' ? $x['impuesto']['valor'] : 0, $items)), 2);
        $total = round(array_sum(array_column($items, 'total')), 2);

        $resol = match ($d->tipo) {
            'fe' => ['numero' => $t->fe_resolucion_numero, 'fecha' => $t->fe_resolucion_fecha?->format('Y-m-d'), 'vigencia' => $t->fe_resolucion_vigencia?->format('Y-m-d'), 'prefijo' => $t->fe_prefijo, 'desde' => $t->fe_desde, 'hasta' => $t->fe_hasta],
            'pos_e' => ['numero' => $t->resolucion_numero, 'fecha' => $t->resolucion_fecha?->format('Y-m-d'), 'vigencia' => $t->resolucion_vigencia?->format('Y-m-d'), 'prefijo' => $t->resolucion_prefijo, 'desde' => $t->resolucion_desde, 'hasta' => $t->resolucion_hasta],
            default => null,
        };

        $medios = [];
        if (!$esNc) {
            foreach ($v->pagos as $p) {
                $cod = $p->metodo->medio_dian ?: ($p->metodo->es_efectivo ? '10' : 'ZZZ');
                $medios[$cod] = ['codigo' => $cod, 'nombre' => Catalogos::MEDIOS_PAGO[$cod] ?? $p->metodo->nombre, 'valor' => round(($medios[$cod]['valor'] ?? 0) + (float) $p->monto, 2)];
            }
        }

        $nit = Catalogos::nitLimpio($t->nit);
        return [
            'version' => 'localpro-1', 'tipo' => $d->tipo, 'numero' => $d->numero, 'prefijo' => $d->prefijo, 'consecutivo' => $d->consecutivo,
            'fecha' => $fecha->format('Y-m-d'), 'hora' => $fecha->format('H:i:sP'), 'moneda' => 'COP', 'ambiente' => $d->ambiente,
            'resolucion' => $resol,
            'clave' => $d->tipo === 'fe' ? $t->fe_clave_tecnica : null,
            'emisor' => [
                'nit' => $nit, 'dv' => $t->dv, 'razon_social' => $t->razon_social ?: $t->nombre, 'nombre_comercial' => $t->nombre,
                'tipo_persona' => $t->tipo_persona ?: 'natural', 'responsabilidades' => self::responsabilidades($t), 'regimen' => $t->regimen, 'ciiu' => $t->ciiu,
                'direccion' => $t->direccion, 'municipio' => $t->municipio_dane, 'ciudad' => $t->ciudad, 'departamento' => $t->departamento,
                'pais' => 'CO', 'telefono' => $t->telefono, 'email' => $t->email_facturacion ?: $t->email,
            ],
            'adquiriente' => $v->adquiriente ?: Catalogos::CONSUMIDOR_FINAL,
            'forma_pago' => ['codigo' => '1', 'nombre' => 'Contado'],
            'medios_pago' => array_values($medios),
            'items' => $items,
            'totales' => ['bruto' => $total, 'base' => $base, 'iva' => $iva, 'inc' => $inc, 'impuestos' => round($iva + $inc, 2), 'descuento' => 0, 'total' => $total],
            'referencia' => $orig ? ['tipo' => $orig->tipo, 'numero' => $orig->numero, 'cufe' => $orig->cufe, 'fecha' => $orig->created_at?->format('Y-m-d'), 'codigo_motivo' => $extra['codigo_motivo'] ?? '2', 'motivo' => $extra['motivo'] ?? 'Anulación'] : null,
            'vendedor' => $v->user?->name, 'venta_localpro' => $v->id,
        ];
    }

    // ---------- Envío ----------

    // Envía (o reintenta) un documento. Nunca lanza error: lo deja anotado en el documento.
    public static function enviar(DocumentoElectronico $d): DocumentoElectronico
    {
        $antes = TiendaActual::id();
        TiendaActual::set($d->tienda_id);
        try {
            $payload = self::payload($d);
            $prov = self::proveedor();
            if (!$prov) {
                $d->forceFill(['payload' => self::sinSecretos($payload) + ($d->tipo === 'nc' ? ($d->payload ?? []) : []), 'ultimo_error' => 'Sin proveedor tecnológico conectado: queda en cola.'])->save();
                return $d;
            }
            $r = $prov->enviar($d, $payload);
            $estado = $r['estado'] ?? 'error';
            $d->forceFill([
                'payload' => self::sinSecretos($payload) + ($d->tipo === 'nc' ? ['motivo' => $d->payload['motivo'] ?? null, 'codigo_motivo' => $d->payload['codigo_motivo'] ?? null, 'lineas' => $d->payload['lineas'] ?? null] : []),
                'respuesta' => $r['respuesta'] ?? null, 'estado' => $estado, 'proveedor' => $prov->nombre(),
                'cufe' => $r['cufe'] ?? $d->cufe, 'qr' => $r['qr'] ?? ($r['cufe'] ?? null ? Cufe::urlConsulta($r['cufe'], $d->ambiente) : $d->qr),
                'pdf_url' => $r['pdf_url'] ?? $d->pdf_url, 'xml_url' => $r['xml_url'] ?? $d->xml_url,
                'ultimo_error' => in_array($estado, ['rechazado', 'error'], true) ? mb_substr((string) ($r['mensaje'] ?? 'Error'), 0, 1000) : null,
                'intentos' => $d->intentos + 1, 'enviado_en' => now(),
                'validado_en' => in_array($estado, ['aceptado', 'simulado'], true) ? now() : $d->validado_en,
            ])->save();
        } catch (Throwable $e) {
            Log::error('DIAN enviar ' . $d->id . ': ' . $e->getMessage());
            $d->forceFill(['estado' => 'error', 'ultimo_error' => mb_substr($e->getMessage(), 0, 1000), 'intentos' => $d->intentos + 1])->save();
        } finally {
            TiendaActual::set($antes);
        }
        return $d;
    }

    // La clave técnica no se guarda en el registro del documento
    private static function sinSecretos(array $p): array
    {
        unset($p['clave']);
        return $p;
    }

    // Después de guardar la venta: intenta enviar ya, sin hacer esperar al cajero si algo falla
    public static function enviarDespues(?DocumentoElectronico $d): void
    {
        if (!$d) return;
        DB::afterCommit(function () use ($d) {
            app()->terminating(fn () => self::enviar($d->fresh()));
        });
    }

    // Valida la numeración antes de vender (se llama dentro de la transacción)
    public static function asegurarNumeracion(Tienda $t, string $tipo): void
    {
        if ($tipo === 'tiquete') return;
        [$num, $hasta, $vig, $cons, $desde] = $tipo === 'fe'
            ? [$t->fe_resolucion_numero, $t->fe_hasta, $t->fe_resolucion_vigencia, $t->consecutivo_fe, $t->fe_desde]
            : [$t->resolucion_numero, $t->resolucion_hasta, $t->resolucion_vigencia, $t->consecutivo_pos, $t->resolucion_desde];
        $nombre = $tipo === 'fe' ? 'factura electrónica' : 'POS electrónico';
        if (!$num) throw ValidationException::withMessages(['pagos' => "Falta la resolución de $nombre. Configúrela en Facturación DIAN."]);
        if ($vig && $vig->lt(today())) throw ValidationException::withMessages(['pagos' => "La resolución de $nombre está vencida. Solicite una nueva a la DIAN."]);
        if ($hasta && max((int) $cons + 1, (int) $desde) > (int) $hasta) throw ValidationException::withMessages(['pagos' => "Se acabaron los números autorizados de $nombre. Solicite una nueva resolución."]);
    }
}
