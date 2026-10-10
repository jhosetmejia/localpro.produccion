<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// LocalPro como plataforma: cada tienda con sus propios datos, dueños y vendedores.
return new class extends Migration
{
    // Tablas cuyos registros pertenecen a una tienda
    private array $tablas = [
        'caracteristicas', 'categorias', 'personas', 'clientes', 'proveedores',
        'productos', 'producto_variantes', 'metodos_pago', 'cajas', 'pos_ventas', 'inventario_movimientos',
    ];

    public function up(): void
    {
        Schema::create('tiendas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->char('pais', 2)->default('CO'); // define moneda, indicativo de WhatsApp y zona horaria
            $table->string('tipo_negocio', 30)->default('moda');
            $table->string('descripcion', 120)->nullable(); // qué vende
            $table->boolean('usa_tallas')->default(true);
            $table->string('nit', 30)->nullable();
            $table->string('direccion', 120)->nullable();
            $table->string('ciudad', 60)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->string('mensaje_tiquete', 255)->nullable();
            $table->string('estado', 15)->default('activa'); // activa | suspendida
            $table->unsignedTinyInteger('onboarding_paso')->default(2);
            $table->boolean('onboarding_completo')->default(false);
            $table->unsignedInteger('consecutivo_pos')->default(0);
            $table->timestamps();
        });

        // La tienda que ya existía pasa a ser la tienda #1 (con sus datos y su logo)
        $neg = Schema::hasTable('negocio') ? DB::table('negocio')->first() : null;
        DB::table('tiendas')->insert([
            'id' => 1,
            'nombre' => $neg->nombre ?? 'Tienda demo',
            'pais' => 'CO',
            'tipo_negocio' => 'moda',
            'usa_tallas' => true,
            'nit' => $neg->nit ?? null,
            'direccion' => $neg->direccion ?? null,
            'ciudad' => $neg->ciudad ?? null,
            'telefono' => $neg->telefono ?? null,
            'email' => $neg->email ?? null,
            'logo_path' => $neg->logo_path ?? null,
            'mensaje_tiquete' => $neg->mensaje_tiquete ?? '¡Gracias por su compra!',
            'estado' => 'activa',
            'onboarding_paso' => 5,
            'onboarding_completo' => true,
            'consecutivo_pos' => (int) DB::table('pos_ventas')->max('id'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tienda_id')->nullable()->after('id')->constrained('tiendas')->nullOnDelete();
            $table->string('telefono', 20)->nullable()->after('email');
            $table->boolean('es_superadmin')->default(false)->after('password');
        });
        DB::table('users')->update(['tienda_id' => 1]);

        foreach ($this->tablas as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->foreignId('tienda_id')->nullable()->after('id')->constrained('tiendas')->cascadeOnDelete();
            });
            DB::table($t)->update(['tienda_id' => 1]);
        }

        // Lo que antes era único en todo el sistema ahora es único dentro de cada tienda
        Schema::table('metodos_pago', function (Blueprint $table) {
            $table->dropUnique('metodos_pago_nombre_unique');
            $table->unique(['tienda_id', 'nombre']);
        });
        Schema::table('producto_variantes', function (Blueprint $table) {
            $table->dropUnique('producto_variantes_codigo_barras_unique');
            $table->unique(['tienda_id', 'codigo_barras']);
        });
        Schema::table('pos_ventas', function (Blueprint $table) {
            $table->dropUnique('pos_ventas_numero_unique');
            $table->unique(['tienda_id', 'numero']);
        });

        // Roles de cada tienda
        $dueno = [
            'usar-pos', 'anular-venta-pos', 'ver-cajas', 'ver-cajas-todas', 'ver-inventario', 'ajustar-inventario',
            'ver-metodo-pago', 'crear-metodo-pago', 'editar-metodo-pago', 'editar-negocio',
            'ver-producto', 'crear-producto', 'editar-producto', 'eliminar-producto',
            'ver-categoria', 'crear-categoria', 'editar-categoria', 'eliminar-categoria',
            'ver-cliente', 'crear-cliente', 'editar-cliente', 'eliminar-cliente',
            'ver-proveedore', 'crear-proveedore', 'editar-proveedore', 'eliminar-proveedore',
            'ver-user', 'crear-user', 'editar-user', 'eliminar-user', 'ver-perfil', 'editar-perfil',
        ];
        $vendedor = ['usar-pos', 'ver-producto', 'ver-inventario', 'ver-cliente', 'crear-cliente', 'ver-perfil', 'editar-perfil'];

        foreach ($dueno as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        Role::firstOrCreate(['name' => 'dueno', 'guard_name' => 'web'])->syncPermissions($dueno);
        Role::firstOrCreate(['name' => 'vendedor', 'guard_name' => 'web'])->syncPermissions($vendedor);

        // Quien ya era administrador es el superadministrador de la plataforma
        $admin = Role::where('name', 'administrador')->first();
        if ($admin) {
            $ids = DB::table('model_has_roles')->where('role_id', $admin->id)->pluck('model_id');
            DB::table('users')->whereIn('id', $ids)->update(['es_superadmin' => true]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('pos_ventas', function (Blueprint $table) {
            $table->dropUnique(['tienda_id', 'numero']);
            $table->unique('numero');
        });
        Schema::table('producto_variantes', function (Blueprint $table) {
            $table->dropUnique(['tienda_id', 'codigo_barras']);
            $table->unique('codigo_barras');
        });
        Schema::table('metodos_pago', function (Blueprint $table) {
            $table->dropUnique(['tienda_id', 'nombre']);
            $table->unique('nombre');
        });
        foreach (array_reverse($this->tablas) as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropConstrainedForeignId('tienda_id');
            });
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tienda_id');
            $table->dropColumn(['telefono', 'es_superadmin']);
        });
        Schema::dropIfExists('tiendas');
    }
};
