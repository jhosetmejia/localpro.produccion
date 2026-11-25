<?php

use App\Models\LpchatUsuario;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// 2026-11-25 (Jhoset): cada persona tiene UN usuario único (como un @) que manda LocalPro y vale igual en
// LP Chat, PFN y NES: localproo.com/@rafael-gomez, pastofightnight.com/rafael-gomez, narinoexposhow.com/@rafael-gomez.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lpchat_usuarios')) {
            return;
        }
        if (! Schema::hasColumn('lpchat_usuarios', 'usuario')) {
            Schema::table('lpchat_usuarios', function (Blueprint $t) {
                $t->string('usuario', 60)->nullable()->unique()->after('codigo');
            });
        }
        // A los que ya existen se les pone su usuario por orden de llegada (el más antiguo se queda con el nombre limpio).
        // Las cuentas de empresa se quedan con el @ de su negocio.
        DB::table('lpchat_usuarios')->whereNull('usuario')->orderBy('id')->select('id', 'nombre', 'tienda_id')->chunk(500, function ($filas) {
            foreach ($filas as $f) {
                $base = $f->nombre;
                if ($f->tienda_id && ($slug = DB::table('tiendas')->where('id', $f->tienda_id)->value('slug'))) {
                    $base = $slug;
                }
                DB::table('lpchat_usuarios')->where('id', $f->id)->update(['usuario' => LpchatUsuario::usuarioLibre((string) $base, (int) $f->id, $f->tienda_id ? (int) $f->tienda_id : null)]);
            }
        });
    }

    public function down(): void
    {
    }
};
