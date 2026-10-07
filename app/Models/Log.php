<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{

    protected $fillable = ['usuario_id', 'accion', 'fecha', 'ip'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
