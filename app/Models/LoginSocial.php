<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginSocial extends Model
{
    protected $table = 'login_social';

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
