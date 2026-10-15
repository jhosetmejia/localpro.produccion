<?php

namespace App\Console\Commands;

use App\Support\Imagenes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

// Comprime las imágenes que ya están guardadas (productos, logos, obituarios…) sin cambiarles el nombre.
// Uso: php artisan imagenes:optimizar        (--probar para ver cuánto se ahorraría sin tocar nada)
class OptimizarImagenesGuardadas extends Command
{
    protected $signature = 'imagenes:optimizar {--probar : Solo cuenta, no cambia nada} {--limite=0 : Máximo de archivos a revisar} {--dias=0 : Solo las guardadas en los últimos N días}';

    protected $description = 'Achica y comprime las imágenes guardadas para liberar espacio en el servidor';

    public function handle(): int
    {
        if (!Imagenes::disponible()) {
            $this->error('PHP no tiene la extensión GD instalada.');
            return self::FAILURE;
        }
        $raiz = Storage::disk('public')->path('');
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($raiz, \FilesystemIterator::SKIP_DOTS));
        $n = 0; $cambiadas = 0; $antes = 0; $despues = 0; $limite = (int) $this->option('limite');
        foreach ($it as $f) {
            if (!$f->isFile() || !preg_match('/\.(jpe?g|png|webp)$/i', $f->getFilename())) continue;
            if ($f->getSize() < Imagenes::MINIMO_BYTES) continue;
            if ((int) $this->option('dias') && $f->getMTime() < time() - 86400 * (int) $this->option('dias')) continue;
            if ($limite && $n >= $limite) break;
            $n++;
            $rel = str_replace('\\', '/', substr($f->getPathname(), strlen($raiz)));
            if ($this->option('probar')) {
                $antes += $f->getSize();
                $this->line('  ' . $rel . ' · ' . round($f->getSize() / 1024) . ' KB');
                continue;
            }
            $r = Imagenes::optimizar($f->getPathname(), Imagenes::maxPara($rel));
            if ($r) {
                $cambiadas++; $antes += $r[0]; $despues += $r[1];
                $this->line(sprintf('  %s · %d KB → %d KB', $rel, $r[0] / 1024, $r[1] / 1024));
            }
        }
        if ($this->option('probar')) {
            $this->info("$n imágenes pesadas (" . round($antes / 1048576, 1) . ' MB). Ejecute sin --probar para comprimirlas.');
        } else {
            $this->info("$cambiadas de $n imágenes comprimidas. Liberado: " . round(($antes - $despues) / 1048576, 1) . ' MB.');
        }
        return self::SUCCESS;
    }
}
