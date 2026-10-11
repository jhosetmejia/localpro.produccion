<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// Catálogo en línea de cada tienda: localpro…/t/{slug}, con pedidos por WhatsApp
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tiendas', function (Blueprint $table) {
            $table->string('slug', 60)->nullable()->unique()->after('nombre');
            $table->boolean('catalogo_activo')->default(true)->after('slug');
        });
        foreach (DB::table('tiendas')->get() as $t) {
            DB::table('tiendas')->where('id', $t->id)->update(['slug' => self::slugUnico($t->nombre, $t->id)]);
        }
    }

    public static function slugUnico(string $nombre, int $id): string
    {
        $base = Str::slug($nombre) ?: 'tienda';
        $slug = $base;
        $i = 2;
        while (DB::table('tiendas')->where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function down(): void
    {
        Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn(['slug', 'catalogo_activo']));
    }
};
