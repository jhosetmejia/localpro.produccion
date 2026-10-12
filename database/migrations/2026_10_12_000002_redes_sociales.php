<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Mis redes: Facebook e Instagram conectados por cada tienda (mensajes y publicaciones)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redes_cuentas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->string('page_id', 40);
            $t->string('page_nombre', 120);
            $t->string('page_foto', 500)->nullable();
            $t->text('page_token'); // cifrado
            $t->string('ig_id', 40)->nullable();
            $t->string('ig_usuario', 80)->nullable();
            $t->string('ig_foto', 500)->nullable();
            $t->foreignId('conectado_por')->nullable()->constrained('users')->nullOnDelete();
            $t->string('estado', 15)->default('activa'); // activa, error, desconectada
            $t->string('error', 255)->nullable();
            $t->timestamp('sincronizado_en')->nullable();
            $t->timestamps();
            $t->unique(['tienda_id', 'page_id']);
            $t->index('page_id');
            $t->index('ig_id');
        });

        Schema::create('redes_conversaciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->foreignId('cuenta_id')->constrained('redes_cuentas')->cascadeOnDelete();
            $t->string('plataforma', 10); // facebook, instagram
            $t->string('contacto_id', 60); // PSID o IGSID
            $t->string('contacto_nombre', 120)->nullable();
            $t->string('contacto_usuario', 80)->nullable();
            $t->string('contacto_foto', 500)->nullable();
            $t->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $t->string('ultimo_texto', 255)->nullable();
            $t->timestamp('ultimo_en')->nullable();
            $t->timestamp('ultimo_del_cliente_en')->nullable(); // para la ventana de 24 horas
            $t->unsignedInteger('no_leidos')->default(0);
            $t->string('etiqueta', 20)->nullable(); // nuevo, interesado, pedido, cliente
            $t->timestamps();
            $t->unique(['cuenta_id', 'plataforma', 'contacto_id']);
            $t->index(['tienda_id', 'ultimo_en']);
        });

        Schema::create('redes_mensajes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->foreignId('conversacion_id')->constrained('redes_conversaciones')->cascadeOnDelete();
            $t->string('mid', 191)->nullable();
            $t->boolean('del_cliente');
            $t->text('texto')->nullable();
            $t->string('adjunto_tipo', 20)->nullable();
            $t->string('adjunto_url', 1000)->nullable();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('enviado_en')->nullable();
            $t->timestamps();
            $t->unique(['conversacion_id', 'mid']);
            $t->index(['conversacion_id', 'enviado_en']);
        });

        Schema::create('redes_publicaciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tienda_id')->constrained('tiendas')->cascadeOnDelete();
            $t->foreignId('cuenta_id')->constrained('redes_cuentas')->cascadeOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('producto_id')->nullable()->constrained('productos')->nullOnDelete();
            $t->text('texto')->nullable();
            $t->string('imagen', 255)->nullable(); // ruta en el disco público
            $t->boolean('en_facebook')->default(true);
            $t->boolean('en_instagram')->default(false);
            $t->string('estado', 15)->default('pendiente'); // programada, pendiente, publicada, parcial, error
            $t->timestamp('programada_para')->nullable();
            $t->timestamp('publicada_en')->nullable();
            $t->string('fb_post_id', 80)->nullable();
            $t->string('ig_media_id', 80)->nullable();
            $t->string('fb_url', 500)->nullable();
            $t->string('ig_url', 500)->nullable();
            $t->string('error', 500)->nullable();
            $t->timestamps();
            $t->index(['estado', 'programada_para']);
        });

        if (Schema::hasTable('permissions')) {
            $responder = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'responder-redes', 'guard_name' => 'web']);
            $publicar = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'publicar-redes', 'guard_name' => 'web']);
            foreach (\Spatie\Permission\Models\Role::all() as $rol) {
                if ($rol->hasPermissionTo('editar-negocio')) $rol->givePermissionTo([$responder, $publicar]);
                elseif ($rol->hasPermissionTo('usar-pos')) $rol->givePermissionTo($responder);
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('redes_publicaciones');
        Schema::dropIfExists('redes_mensajes');
        Schema::dropIfExists('redes_conversaciones');
        Schema::dropIfExists('redes_cuentas');
        \Illuminate\Support\Facades\DB::table('permissions')->whereIn('name', ['responder-redes', 'publicar-redes'])->delete();
    }
};
