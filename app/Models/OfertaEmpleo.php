<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfertaEmpleo extends Model
{
    protected $table = 'ofertas_empleo';

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class);
    }

    public function listaDeseos(): HasMany
    {
        return $this->hasMany(ListaDeseo::class);
    }
}
