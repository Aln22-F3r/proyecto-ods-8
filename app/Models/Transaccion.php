<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaccion extends Model
{
    protected $table = 'transacciones';
    protected $fillable = [
        'usuario_id',
        'tipo_registro',
        'descripcion',
        'fecha',
        'estado',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
