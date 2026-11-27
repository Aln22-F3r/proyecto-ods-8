<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OfertaEmpleo extends Model
{
    use SoftDeletes;

    protected $table = 'ofertas_empleo';

    protected $fillable = [
        'usuario_id',
        'categoria_id',
        'titulo',
        'descripcion',
        'ubicacion',
        'salario',
        'tipo_empleo',
        'fecha_publicacion',
        'estado',
        'destacada',
        'destacada_hasta',
        'logo',
    ];

    protected $casts = [
        'salario'         => 'decimal:2',
        'fecha_publicacion' => 'datetime',
        'destacada'       => 'boolean',
        'destacada_hasta' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class)->withTrashed();
    }

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

    public function pagos(): HasMany   // nuevo: historial de pagos de esta oferta //
    {
        return $this->hasMany(Pago::class);
    }

    // Scope: ofertas que están activamente destacadas (no expiradas) //
    public function scopeDestacadasVigentes(Builder $query): Builder
    {
        return $query->where('destacada', true)
            ->where('destacada_hasta', '>', now());
    }

    // Helper: ¿sigue vigente el destacado a día de hoy? //
    public function estaDestacadaVigente(): bool
    {
        return $this->destacada
            && $this->destacada_hasta
            && $this->destacada_hasta->isFuture();
    }
}
