<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Extensión segura para guardar un archivo subido en la carpeta pública.
 * Nunca se usa la extensión que manda el usuario sin revisar: un "foto.php" que por dentro parece imagen
 * quedaría en /storage como .php y el servidor lo podría ejecutar. Solo se aceptan extensiones de la lista.
 */
class ArchivoSeguro
{
    public static function ext(UploadedFile $f, array $permitidas, string $porDefecto): string
    {
        $permitidas = array_map('strtolower', $permitidas);
        $cliente = strtolower((string) $f->getClientOriginalExtension());
        if (in_array($cliente, $permitidas, true)) return $cliente;
        $adivinada = strtolower((string) $f->guessExtension());
        return in_array($adivinada, $permitidas, true) ? $adivinada : $porDefecto;
    }

    public const IMAGEN = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
    public const AUDIO = ['mp3', 'm4a', 'aac', 'wav', 'ogg', 'oga', 'webm', 'mp4'];
}
