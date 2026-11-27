<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Postulacion extends Model
{
    use SoftDeletes;

    protected $table = 'postulaciones';

    protected $fillable = [
        'usuario_id',
        'oferta_empleo_id',
        'cv',
        'fecha_postulacion',
        'estado',
        'comentario',
    ];

    protected $casts = [
        'fecha_postulacion' => 'datetime',
    ];

    public function usuario(): BelongsTo                   // candidato dueño de la postulación //
    {
        return $this->belongsTo(Usuario::class)->withTrashed();
    }

    public function ofertaEmpleo(): BelongsTo              // oferta a la que se postuló //
    {
        return $this->belongsTo(OfertaEmpleo::class)->withTrashed();
    }
}
