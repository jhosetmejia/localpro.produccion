<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// La carta hecha con IA puede reemplazar la carta en línea del QR de las mesas
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cartas_ia') && !Schema::hasColumn('cartas_ia', 'en_qr')) {
            Schema::table('cartas_ia', function (Blueprint $table) {
                $table->boolean('en_qr')->default(false)->after('html');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cartas_ia', 'en_qr')) {
            Schema::table('cartas_ia', fn (Blueprint $table) => $table->dropColumn('en_qr'));
        }
    }
};
