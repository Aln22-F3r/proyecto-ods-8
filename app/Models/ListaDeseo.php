<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListaDeseo extends Model
{
    protected $table = 'lista_deseos';
    protected $fillable = ['usuario_id', 'oferta_empleo_id', 'fecha_agregado'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function ofertaEmpleo(): BelongsTo
    {
        return $this->belongsTo(OfertaEmpleo::class);
    }
}
