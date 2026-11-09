<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Cada página que se abrió dentro de una visita (el recorrido). Se guardan 60 días. */
class VisitaPagina extends Model
{
    protected $table = 'visitas_paginas';
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = ['created_at' => 'datetime'];
}
