<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Se quita la palabra "Obituario(s)" de todas las plantillas (pedido de la funeraria). Si alguien escribió otro título, se respeta.
return new class extends Migration
{
    public function up(): void
    {
        DB::table('obituario_plantillas')->whereIn(DB::raw('LOWER(TRIM(titulo))'), ['obituario', 'obituarios'])->update(['titulo' => '', 'updated_at' => now()]);
    }

    public function down(): void
    {
    }
};
