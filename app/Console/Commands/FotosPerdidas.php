<?php

namespace App\Console\Commands;

use App\Models\Producto;
use App\Models\ProductoFoto;
use Illuminate\Console\Command;

// Fotos de productos que están en la base pero cuyo archivo no está en el servidor (salen rotas en la tienda).
// Uso: php artisan localpro:fotos-perdidas          (lista: tienda · producto · archivo)
//      php artisan localpro:fotos-perdidas --solo-archivos   (un nombre de archivo por línea, para el piloto automático)
class FotosPerdidas extends Command
{
    protected $signature = 'localpro:fotos-perdidas {--solo-archivos : Solo los nombres de archivo, uno por línea}';
    protected $description = 'Lista las fotos de productos cuyo archivo no está en el servidor';

    public function handle(): int
    {
        $perdidas = [];
        Producto::withoutGlobalScopes()->whereNotNull('img_path')->where('img_path', '<>', '')
            ->select('id', 'tienda_id', 'nombre', 'img_path')->orderBy('id')
            ->each(function ($p) use (&$perdidas) {
                if (!Producto::fotoExiste($p->img_path)) $perdidas[] = [$p->tienda_id, $p->id, $p->nombre, $p->img_path];
            });
        ProductoFoto::withoutGlobalScopes()->with(['producto' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'nombre')])
            ->orderBy('id')->each(function ($f) use (&$perdidas) {
                if (!Producto::fotoExiste($f->path)) $perdidas[] = [$f->tienda_id, $f->producto_id, ($f->producto->nombre ?? '?') . ' (' . $f->color . ')', $f->path];
            });

        if ($this->option('solo-archivos')) {
            foreach (array_unique(array_column($perdidas, 3)) as $a) $this->line($a);
            return self::SUCCESS;
        }
        foreach ($perdidas as [$t, $id, $n, $a]) $this->line("Tienda $t · producto $id · $n · $a");
        $this->info(count($perdidas) . ' fotos sin archivo.');
        return self::SUCCESS;
    }
}
