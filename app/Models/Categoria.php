<?php

namespace App\Models;

use App\Models\Concerns\PerteneceATienda;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use PerteneceATienda;

    use HasFactory;

    public function productos(){
        return $this->belongsToMany(Producto::class)->withTimestamps();
    }

    public function caracteristica(){
        return $this->belongsTo(Caracteristica::class);
    }

    protected $fillable = ['caracteristica_id'];
}
