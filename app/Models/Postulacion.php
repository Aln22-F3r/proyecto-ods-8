<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Postulacion extends Model
{
    protected $table = 'postulaciones';
    protected $fillable = [
        'usuario_id',
        'oferta_empleo_id',
        'fecha_postulacion',
        'estado',
        'comentario',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function ofertaEmpleo(): BelongsTo
    {
        return $this->belongsTo(OfertaEmpleo::class);
    }
}
