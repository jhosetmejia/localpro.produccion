<?php

namespace App\Support;

use App\Models\Asesor;
use App\Models\Cliente;
use App\Models\Tienda;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reparto de la base de datos de clientes de una tienda ("Contactar clientes nuevos").
 * Regla: a cada número se le escribe UNA sola vez, lo escriba alguien del equipo de la tienda o un asesor
 * con quien el negocio compartió su base. Todo se ejecuta dentro de la tienda actual (TiendaActual).
 */
class ContactarBase
{
    // El número tal como se usa en WhatsApp (solo dígitos y con indicativo), para saber si dos clientes son la misma persona
    public static function numero(?string $tel): string
    {
        return (string) numero_whatsapp($tel);
    }

    // Los que se pueden asignar: con celular, sin contactar y sin asignar hoy a otra persona
    public static function libres()
    {
        $hoy = now()->toDateString();
        return Cliente::query()
            ->whereNull('contactado_at')
            ->where(fn ($q) => $q->whereNull('asignado_fecha')->orWhere('asignado_fecha', '<', $hoy))
            ->whereHas('persona', fn ($p) => $p->where('estado', 1)->whereNotNull('telefono')->where('telefono', '<>', ''));
    }

    // Números que ya no se pueden dar: ya contactados por alguien, o asignados hoy a otra persona
    public static function numerosOcupados(): array
    {
        $hoy = now()->toDateString();
        $ocupados = [];
        Cliente::query()
            ->where(fn ($q) => $q->whereNotNull('contactado_at')->orWhere('asignado_fecha', $hoy))
            ->with('persona:id,telefono')->select(['id', 'persona_id'])
            ->chunkById(1000, function ($lote) use (&$ocupados) {
                foreach ($lote as $c) {
                    $n = self::numero($c->persona?->telefono);
                    if ($n !== '') $ocupados[$n] = true;
                }
            });
        return $ocupados;
    }

    // Reparte contactos sin repetir número: una persona se contacta UNA sola vez, aunque esté cargada dos veces en la base
    public static function asignar(int $userId, int $cuantos): int
    {
        if ($cuantos <= 0) return 0;
        return DB::transaction(function () use ($userId, $cuantos) {
            $ocupados = self::numerosOcupados();
            $elegidos = [];
            $desde = 0;
            while (count($elegidos) < $cuantos) {
                $lote = self::libres()->with('persona:id,telefono')->where('id', '>', $desde)
                    ->orderBy('id')->limit(300)->lockForUpdate()->get(['id', 'persona_id']);
                if ($lote->isEmpty()) break;
                foreach ($lote as $c) {
                    $desde = $c->id;
                    $n = self::numero($c->persona?->telefono);
                    if ($n === '' || isset($ocupados[$n])) continue;
                    $ocupados[$n] = true;
                    $elegidos[] = $c->id;
                    if (count($elegidos) >= $cuantos) break;
                }
            }
            if (!$elegidos) return 0;
            Cliente::whereIn('id', $elegidos)->update(['asignado_a' => $userId, 'asignado_fecha' => now()->toDateString()]);
            return count($elegidos);
        });
    }

    // Los clientes de la base que tienen el mismo número (la misma persona cargada varias veces)
    public static function mismoNumero(Cliente $cliente): Collection
    {
        $n = self::numero($cliente->persona?->telefono);
        if ($n === '') return collect([$cliente->id]);
        $digitos = substr($n, -7);
        return Cliente::with('persona:id,telefono')
            ->whereHas('persona', fn ($p) => $p->where('telefono', 'like', '%' . $digitos . '%'))
            ->get(['id', 'persona_id'])
            ->filter(fn ($c) => self::numero($c->persona?->telefono) === $n)
            ->pluck('id')->push($cliente->id)->unique();
    }

    /**
     * Marca un contacto (y cualquier otro cliente con el mismo número). Devuelve [ok, mensaje, repetido].
     * Si otra persona ya le escribió, no se marca de nuevo y se avisa.
     */
    public static function marcar(Cliente $cliente, int $userId, string $estado): array
    {
        $cliente->loadMissing('persona');
        $ids = self::mismoNumero($cliente);
        if ($estado === 'enviado') {
            $previo = Cliente::with('contactadoPor:id,name')->whereIn('id', $ids)->whereNotNull('contactado_at')
                ->where('id', '<>', $cliente->id)->first();
            if ($previo && (int) $previo->contactado_por !== $userId) {
                Cliente::whereIn('id', $ids)->whereNull('contactado_at')->update([
                    'contactado_at' => $previo->contactado_at, 'contactado_por' => $previo->contactado_por, 'contacto_estado' => $previo->contacto_estado,
                ]);
                return [false, 'A este número ya le escribió ' . ($previo->contactadoPor->name ?? 'otra persona') . '. No le escriba otra vez.', true];
            }
        }
        Cliente::whereIn('id', $ids)->update(['contactado_at' => now(), 'contactado_por' => $userId, 'contacto_estado' => $estado]);
        return [true, null, false];
    }

    // ¿Alguien ya le escribió a este número? Devuelve el aviso o null si está libre
    public static function yaEscrito(Cliente $cliente): ?string
    {
        $cliente->loadMissing('persona');
        $previo = Cliente::with('contactadoPor:id,name')->whereIn('id', self::mismoNumero($cliente))->whereNotNull('contactado_at')->first();
        return $previo ? 'A este número ya le escribió ' . ($previo->contactadoPor->name ?? 'otra persona') . ' el ' . $previo->contactado_at->format('d/m/Y') . '. No le escriba otra vez.' : null;
    }

    // ---------- Base compartida con los asesores ----------

    /**
     * Ids de las personas de la red "de arriba" del asesor (su líder, el líder de su líder… y quien lo sumó).
     * Un negocio que comparte su base con "sus asesores" se la da a toda la gente que el dueño tiene debajo.
     */
    public static function deArriba(Asesor $a): array
    {
        $ids = [];
        $nivel = [$a->padre_id, $a->reclutado_por];
        for ($i = 0; $i < 10; $i++) {
            $nivel = array_values(array_diff(array_filter(array_unique($nivel)), $ids, [$a->id]));
            if (!$nivel) break;
            $ids = array_merge($ids, $nivel);
            $nivel = Asesor::whereIn('id', $nivel)->get(['padre_id', 'reclutado_por'])
                ->flatMap(fn ($x) => [$x->padre_id, $x->reclutado_por])->all();
        }
        return $ids;
    }

    // Negocios que le comparten su base de datos a este asesor
    public static function tiendasPara(Asesor $a): Collection
    {
        if (!$a->activo || !$a->curso_aprobado_at) return collect();
        $arriba = self::deArriba($a);
        $propia = (int) ($a->user?->tienda_id ?? 0);
        return Tienda::where('compartir_asesores', true)
            ->where(fn ($q) => $q->whereNull('compartir_asesor_id')->orWhereIn('compartir_asesor_id', $arriba ?: [0]))
            ->when($propia, fn ($q) => $q->where('id', '<>', $propia))
            ->orderBy('nombre')->get();
    }

    // ¿Este asesor puede escribirle a los clientes de esa tienda?
    public static function puede(Asesor $a, int $tiendaId): bool
    {
        return self::tiendasPara($a)->contains('id', $tiendaId);
    }

    // Cuántos contactos al día recibe cada asesor de cada negocio que le comparte su base
    public static function diariosAsesor(Tienda $t): int
    {
        return max(1, min(200, (int) ($t->contactos_diarios ?: 20)));
    }
}
