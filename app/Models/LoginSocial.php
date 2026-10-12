<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoginSocial extends Model
{
    use SoftDeletes;

    protected $table = 'login_sociales';
    protected $fillable = ['usuario_id', 'proveedor', 'proveedor_id', 'correo'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class)->withTrashed();
    }
}
