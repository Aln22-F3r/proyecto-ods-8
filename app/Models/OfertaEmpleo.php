<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfertaEmpleo extends Model
{
    use SoftDeletes;

    protected $table = 'ofertas_empleo';
    protected $fillable = [
        'categoria_id',
        'titulo',
        'empresa',
        'descripcion',
        'ubicacion',
        'salario',
        'tipo_empleo',
        'fecha_publicacion',
        'estado',
        'logo',
    ];

    protected $casts = [
        'salario' => 'decimal:2',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class)->withTrashed();
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
