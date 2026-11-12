<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens, SoftDeletes;

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

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class)->withTrashed();
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
