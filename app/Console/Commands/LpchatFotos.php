<?php

namespace App\Console\Commands;

use App\Models\LpchatUsuario;
use App\Support\LpChat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 2026-11-17 (Jhoset): las fotos que llegan de PFN y NES son enlaces de Backblaze que se vencen.
 * Este comando las copia al servidor de LocalProo (livianas, 600 px). Corre cada 5 minutos, de a 150.
 */
class LpchatFotos extends Command
{
    protected $signature = 'lpchat:fotos {--lote=150}';

    protected $description = 'LP Chat: copia aquí las fotos de perfil que llegaron de PFN/NES como enlace';

    public function handle(): int
    {
        $ok = $mal = 0;
        LpchatUsuario::where('foto', 'like', 'http%')->orderBy('id')->limit((int) $this->option('lote'))->get()->each(function ($u) use (&$ok, &$mal) {
            $v = DB::table('lpchat_vinculos')->where('usuario_id', $u->id)->orderBy('id')->first();
            $local = LpChat::fotoRemota($v->origen ?? 'web', (string) ($v->origen_id ?? $u->id), $u->foto);
            if ($local) {
                $u->foto = $local;
                $u->saveQuietly();
                $ok++;
            } else {
                // Enlace vencido o caído: se quita para no reintentarlo siempre; la próxima sincronización de PFN/NES trae uno nuevo.
                $u->foto = null;
                $u->saveQuietly();
                $mal++;
            }
        });
        $this->info("LP Chat fotos: {$ok} copiadas, {$mal} sin poder bajar.");

        return self::SUCCESS;
    }
}
