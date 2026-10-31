<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

// Seguridad: borra de storage/app/public cualquier archivo con extensión ejecutable (.php, .phtml, .phar…)
// que se haya podido subir antes de que se revisara la extensión de las fotos, logos y canciones.
return new class extends Migration
{
    public function up(): void
    {
        $raiz = storage_path('app/public');
        if (!is_dir($raiz)) return;
        $malas = '/\.(php\d?|phtml|phar|pht|phps|cgi|pl|py|sh|htaccess|shtml)$/i';
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && preg_match($malas, $f->getFilename())) {
                Log::warning('Seguridad: se borró un archivo ejecutable subido a storage público', ['archivo' => $f->getPathname()]);
                @unlink($f->getPathname());
            }
        }
    }

    public function down(): void {}
};
