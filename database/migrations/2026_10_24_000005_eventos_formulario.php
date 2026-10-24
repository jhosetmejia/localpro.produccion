<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Eventos: formulario de registro configurable (campos propios del organizador) en lugar del "colegio" fijo.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $t) {
            if (!Schema::hasColumn('eventos', 'campos')) $t->json('campos')->nullable();
            if (!Schema::hasColumn('eventos', 'pedir_nacimiento')) $t->boolean('pedir_nacimiento')->default(true);
            if (!Schema::hasColumn('eventos', 'pedir_celular')) $t->boolean('pedir_celular')->default(true);
        });
        Schema::table('evento_entradas', function (Blueprint $t) {
            if (!Schema::hasColumn('evento_entradas', 'respuestas')) $t->json('respuestas')->nullable();
        });
        Schema::table('eventos', function (Blueprint $t) {
            $t->boolean('pedir_colegio')->default(false)->change();
        });
        // Los eventos que pedían colegio lo conservan como campo propio
        foreach (DB::table('eventos')->where('pedir_colegio', true)->get(['id', 'campos']) as $e) {
            $campos = json_decode((string) $e->campos, true) ?: [];
            $campos[] = ['id' => 'colegio', 'etiqueta' => 'Colegio', 'tipo' => 'texto', 'opciones' => [], 'obligatorio' => true];
            DB::table('eventos')->where('id', $e->id)->update(['campos' => json_encode($campos, JSON_UNESCAPED_UNICODE), 'pedir_colegio' => false]);
        }
    }

    public function down(): void
    {
        Schema::table('eventos', fn (Blueprint $t) => $t->dropColumn(['campos', 'pedir_nacimiento', 'pedir_celular']));
        Schema::table('evento_entradas', fn (Blueprint $t) => $t->dropColumn('respuestas'));
    }
};
