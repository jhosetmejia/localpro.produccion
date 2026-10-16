<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Promoción / liquidación: "precio antes". Si es mayor que el precio de venta, el producto sale
// en la tienda en línea con el precio tachado, el % de descuento y en "Promociones".
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('productos', 'precio_antes')) {
            Schema::table('productos', fn (Blueprint $t) => $t->decimal('precio_antes', 14, 2)->nullable()->after('precio_venta'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('productos', 'precio_antes')) {
            Schema::table('productos', fn (Blueprint $t) => $t->dropColumn('precio_antes'));
        }
    }
};
