<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiqueteraUso extends Model
{
    protected $table = 'tiquetera_usos';
    protected $fillable = ['tiquetera_id', 'user_id', 'cantidad', 'anulado'];
    protected $casts = ['anulado' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
