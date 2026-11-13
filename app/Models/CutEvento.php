<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Lo que hace la persona en LocalProCut (agregar videos, exportar, compartir, instalar, registrarse…). */
class CutEvento extends Model
{
    protected $table = 'cut_eventos';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['datos' => 'array', 'created_at' => 'datetime'];

    public const NOMBRES = [
        'abrio' => 'Abrió el editor', 'agrego' => 'Agregó videos o fotos', 'exporto' => 'Exportó un video', 'exporto_falla' => 'Falló al exportar',
        'exportar' => 'Empezó a exportar', 'subtitulos' => 'Generó subtítulos', 'compartio_wa' => 'Compartió por WhatsApp', 'compartio' => 'Compartió el enlace',
        'compartio_video' => 'Compartió su video', 'descargo_video' => 'Descargó su video', 'clic_registro' => 'Tocó Registrarse', 'clic_app' => 'Tocó Descargar app',
        'instalo' => 'Instaló la app', 'clic_funciones' => 'Fue a ver las funciones', 'muro_registro' => 'Vio la invitación a registrarse', 'opino' => 'Abrió Opinar', 'clic_programar' => 'Tocó Programar en redes', 'clic_anuncio' => 'Tocó un anuncio',
    ];
}
