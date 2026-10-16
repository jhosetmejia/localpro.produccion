<?php

namespace App\Console\Commands;

use App\Models\Prospecto;
use App\Models\ProspectoBusqueda;
use App\Support\Prospectos;
use Illuminate\Console\Command;

/**
 * Consigue prospectos nuevos: 1) busca firmas contables en Google Maps por ciudad (si hay clave de Maps)
 * y 2) entra a la página web de cada una para leer el correo de contacto que publican.
 * Busca lo suficiente para que nunca falten correos por enviar (unos 3 días de reserva).
 */
class ProspectosBuscar extends Command
{
    protected $signature = 'prospectos:buscar {--consultas= : Cuántas búsquedas en Maps hacer} {--webs=40 : Cuántas páginas web revisar}';

    protected $description = 'Busca firmas contables nuevas y el correo publicado en su página web';

    public function handle(): int
    {
        $this->buscarEnMaps();
        $this->leerPaginas((int) $this->option('webs'));
        $listos = Prospecto::where('estado', 'nuevo')->count();
        $this->info("Prospectos listos para el primer correo: $listos.");
        return self::SUCCESS;
    }

    private function buscarEnMaps(): void
    {
        if (!Prospectos::secreto('maps_clave')) {
            $this->line('Sin clave de Google Maps: solo se revisan las páginas ya cargadas.');
            return;
        }
        // Si hay reserva para más de 3 días, no se gasta en búsquedas
        $reserva = Prospecto::whereIn('estado', ['nuevo', 'sin_correo'])->count();
        $consultas = $this->option('consultas') !== null ? (int) $this->option('consultas')
            : ($reserva >= Prospectos::limiteDiario() * 3 ? 0 : 6);

        $hechas = 0;
        foreach (Prospectos::ciudades() as $ciudad) {
            foreach (Prospectos::TERMINOS as $termino) {
                if ($hechas >= $consultas) return;
                $consulta = "$termino en $ciudad";
                $b = ProspectoBusqueda::firstOrNew(['consulta' => $consulta]);
                if ($b->hecha_at && $b->hecha_at->gt(now()->subDays(90))) continue;
                try {
                    $res = Prospectos::buscarEnMaps($consulta);
                } catch (\Throwable $e) {
                    $this->error($e->getMessage());
                    return;
                }
                $nuevos = 0;
                foreach ($res as $r) {
                    $web = Prospectos::normalizarWeb($r['web']);
                    if (!$web) continue;
                    $host = preg_replace('/^www\./', '', Prospectos::host($web));
                    if (Prospecto::where('web', 'like', '%' . $host . '%')->exists()) continue;
                    Prospecto::create(['empresa' => $r['empresa'], 'web' => $web, 'telefono' => $r['telefono'], 'ciudad' => $ciudad, 'estado' => 'sin_correo', 'notas' => $r['direccion'] ? mb_substr($r['direccion'], 0, 500) : null]);
                    $nuevos++;
                }
                $b->fill(['encontrados' => $nuevos, 'hecha_at' => now()])->save();
                $hechas++;
                $this->line("Maps: \"$consulta\" → $nuevos firmas nuevas con página web.");
            }
        }
    }

    private function leerPaginas(int $max): void
    {
        $lista = Prospecto::where('estado', 'sin_correo')->whereNotNull('web')->where('intentos_busqueda', '<', 3)
            ->orderBy('intentos_busqueda')->orderBy('id')->limit($max)->get();
        foreach ($lista as $p) {
            [$correo, $fuente] = Prospectos::correoDeLaWeb($p->web);
            if (!$correo) {
                $p->intentos_busqueda++;
                if ($p->intentos_busqueda >= 3) {
                    $p->estado = 'descartado';
                    $p->notas = trim(($p->notas ? $p->notas . ' · ' : '') . 'Sin correo publicado en la web');
                }
                $p->save();
                continue;
            }
            if (Prospecto::where('correo', $correo)->where('id', '<>', $p->id)->exists()) {
                $p->update(['estado' => 'descartado', 'notas' => 'Correo repetido con otro prospecto: ' . $correo]);
                continue;
            }
            $p->update(['correo' => $correo, 'fuente' => mb_substr((string) $fuente, 0, 255), 'estado' => 'nuevo']);
            $this->line("Correo encontrado: {$p->empresa} → $correo");
        }
    }
}
