<?php

namespace App\Console\Commands;

use App\Support\LpChat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * 2026-11-17 (Jhoset): LP Chat guarda los últimos 3 meses (antes 7 días) de cada chat (mensajes, fotos, videos y audios)
 * y los estados 24 horas, para no llenar el servidor. Corre cada hora.
 */
class LpchatLimpiar extends Command
{
    protected $signature = 'lpchat:limpiar';

    protected $description = 'LP Chat: borra mensajes y archivos de más de 3 meses y estados de más de 24 horas';

    public function handle(): int
    {
        $disco = Storage::disk('local');
        $limite = now()->subDays(LpChat::DIAS);
        $msj = 0;
        // Lo que vino de NES/PFN (historial de esos chats) no se borra.
        DB::table('lpchat_mensajes')->where('created_at', '<', $limite)->whereNull('origen')->orderBy('id')->chunkById(500, function ($filas) use ($disco, &$msj) {
            foreach ($filas as $m) {
                if ($m->adjunto) {
                    $disco->delete($m->adjunto);
                }
            }
            $msj += DB::table('lpchat_mensajes')->whereIn('id', $filas->pluck('id'))->delete();
        });
        // "Ver una vez" que se mandó y nunca se abrió en 3 meses ya quedó arriba. Los abiertos ya se borraron al verlos.
        $est = 0;
        DB::table('lpchat_estados')->where('created_at', '<', now()->subDay())->orderBy('id')->chunkById(500, function ($filas) use ($disco, &$est) {
            foreach ($filas as $e) {
                if ($e->archivo) {
                    $disco->delete($e->archivo);
                }
            }
            DB::table('lpchat_estados_vistos')->whereIn('estado_id', $filas->pluck('id'))->delete();
            $est += DB::table('lpchat_estados')->whereIn('id', $filas->pluck('id'))->delete();
        });
        // Carpetas de días viejos que quedaron vacías
        foreach (['lpchat/adjuntos', 'lpchat/estados'] as $base) {
            foreach ($disco->allDirectories($base) as $dir) {
                if (! $disco->allFiles($dir) && ! $disco->directories($dir)) {
                    $disco->deleteDirectory($dir);
                }
            }
        }
        $this->info("LP Chat: {$msj} mensajes y {$est} estados borrados.");

        return self::SUCCESS;
    }
}
