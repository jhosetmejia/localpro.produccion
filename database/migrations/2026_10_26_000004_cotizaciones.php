<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cotizaciones: el negocio arma una cotización con sus productos (o líneas libres), cambia precios si quiere
// y la entrega como PDF con su logo o por un enlace de WhatsApp. No mueve inventario ni caja.
// Con la fecha del alquiler muestra la disponibilidad: lo que está en cotizaciones aceptadas para esas fechas queda ocupado.
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cotizaciones')) {
            Schema::create('cotizaciones', function (Blueprint $t) {
                $t->id();
                $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
                $t->string('numero', 20);
                $t->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
                $t->string('cliente_nombre', 120);
                $t->string('cliente_documento', 30)->nullable();
                $t->string('cliente_telefono', 30)->nullable();
                $t->string('cliente_email', 120)->nullable();
                $t->string('cliente_direccion', 150)->nullable();
                $t->string('asunto', 150)->nullable();
                // Fecha del alquiler o del evento: con ella se calcula la disponibilidad (lo aceptado ese día queda ocupado)
                $t->date('evento_fecha')->nullable();
                $t->date('evento_hasta')->nullable();
                $t->string('evento_lugar', 150)->nullable();
                $t->unsignedBigInteger('alquiler_id')->nullable();
                $t->date('fecha');
                $t->date('vence')->nullable();
                $t->decimal('subtotal', 14, 2)->default(0);
                $t->decimal('descuento', 14, 2)->default(0);
                $t->decimal('total', 14, 2)->default(0);
                $t->text('condiciones')->nullable();
                $t->string('estado', 12)->default('pendiente'); // pendiente, aceptada, rechazada
                $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamps();
                $t->unique(['tienda_id', 'numero']);
                $t->index(['tienda_id', 'estado', 'fecha']);
                $t->index(['tienda_id', 'estado', 'evento_fecha']);
            });
        }
        if (!Schema::hasTable('cotizacion_items')) {
            Schema::create('cotizacion_items', function (Blueprint $t) {
                $t->id();
                $t->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
                $t->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
                $t->string('descripcion', 200);
                $t->decimal('cantidad', 12, 3)->default(1);
                $t->decimal('precio', 14, 2)->default(0);
                $t->decimal('precio_lista', 14, 2)->nullable(); // precio del producto al cotizar (para saber si se cambió)
                $t->decimal('subtotal', 14, 2)->default(0);
                $t->unsignedSmallInteger('orden')->default(0);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_items');
        Schema::dropIfExists('cotizaciones');
    }
};
