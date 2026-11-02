<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Autogestión: el cliente pide desde el QR de la mesa, el pedido llega a caja y él se acerca a pagar.
// pedidos.origen: null = lo tomó el mesero · 'qr' = lo hizo el cliente desde la carta.
// tiendas.pedidos_qr: el dueño lo puede apagar en Pedidos → Configurar.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pedidos') && !Schema::hasColumn('pedidos', 'origen')) {
            Schema::table('pedidos', fn (Blueprint $t) => $t->string('origen', 12)->nullable()->after('modo'));
        }
        if (Schema::hasTable('tiendas') && !Schema::hasColumn('tiendas', 'pedidos_qr')) {
            Schema::table('tiendas', fn (Blueprint $t) => $t->boolean('pedidos_qr')->default(true));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pedidos', 'origen')) Schema::table('pedidos', fn (Blueprint $t) => $t->dropColumn('origen'));
        if (Schema::hasColumn('tiendas', 'pedidos_qr')) Schema::table('tiendas', fn (Blueprint $t) => $t->dropColumn('pedidos_qr'));
    }
};
