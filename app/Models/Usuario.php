<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Usuario extends Model
{

    protected $fillable = [
        'rol_id',
        'nombre',
        'apellido',
        'correo',
        'password',
        'telefono',
        'fecha_registro',
        'estado',
    ];

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    public function perfil(): HasOne
    {
        return $this->hasOne(Perfil::class);
    }

    public function postulaciones(): HasMany
    {
        return $this->hasMany(Postulacion::class);
    }

    public function listaDeseos(): HasMany
    {
        return $this->hasMany(ListaDeseo::class);
    }

    public function transacciones(): HasMany
    {
        return $this->hasMany(Transaccion::class);
    }

    public function loginSociales(): HasMany
    {
        return $this->hasMany(LoginSocial::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Log::class);
    }
}
