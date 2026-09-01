<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Perfil extends Model
{
    protected $table = 'perfiles';

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
