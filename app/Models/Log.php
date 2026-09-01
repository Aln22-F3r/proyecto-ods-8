<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
