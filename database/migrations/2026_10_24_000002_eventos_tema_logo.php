<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

// Eventos: estilo de la página (normal o terror/Halloween) y logo del evento.
// El Último Grito: estilo terror, logo oficial, sin afiche de información y sin pedir colegio (es una fiesta fuera de instituciones).
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('eventos')) return;
        Schema::table('eventos', function (Blueprint $t) {
            if (!Schema::hasColumn('eventos', 'tema')) $t->string('tema', 20)->default('normal');
            if (!Schema::hasColumn('eventos', 'logo_path')) $t->string('logo_path')->nullable();
        });
        DB::statement("ALTER TABLE eventos ALTER pedir_colegio SET DEFAULT 0");

        // TikTok / Instagram guardados como enlace o con @: se deja solo el usuario
        foreach (DB::table('eventos')->get(['id', 'tiktok', 'instagram']) as $e) {
            $cambios = [];
            foreach (['tiktok', 'instagram'] as $r) {
                $limpio = \App\Models\Evento::usuarioRed($e->$r);
                if ($limpio !== $e->$r) $cambios[$r] = $limpio;
            }
            if ($cambios) DB::table('eventos')->where('id', $e->id)->update($cambios);
        }

        $logo = base_path('deploy/afiches/ultimo-grito-logo.png');
        foreach (DB::table('eventos')->where(fn ($q) => $q->where('nombre', 'like', '%grito%')->orWhere('slug', 'like', '%grito%'))->get() as $e) {
            $upd = ['tema' => 'terror', 'pedir_colegio' => false, 'imagen2_path' => null];
            if (!$e->logo_path && is_file($logo)) {
                $ruta = 'eventos/' . $e->tienda_id . '/logo-' . $e->id . '.png';
                Storage::disk('public')->put($ruta, file_get_contents($logo));
                $upd['logo_path'] = $ruta;
            }
            DB::table('eventos')->where('id', $e->id)->update($upd);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('eventos')) return;
        Schema::table('eventos', fn (Blueprint $t) => $t->dropColumn(['tema', 'logo_path']));
    }
};
