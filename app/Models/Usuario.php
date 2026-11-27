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
    use HasApiTokens, SoftDeletes;   // tokens Sanctum + borrado lógico //

    protected $fillable = [
        'rol_id',
        'correo',
        'password',
        'nivel',            // solo aplica a Administrador (1, 2 o 3) //
        'estado',           // false = cuenta deshabilitada //
        'fecha_registro',
    ];

    protected $hidden = [
        'password',         // nunca se serializa en JSON //
        'remember_token',
    ];

    protected $casts = [
        'estado'         => 'boolean',
        'nivel'          => 'integer',
        'fecha_registro' => 'datetime',
    ];

    // --- Relaciones --- //

    public function rol(): BelongsTo                    // Rol del usuario (Administrador/Candidato/Empresa) //
    {
        return $this->belongsTo(Rol::class);
    }

    public function perfil(): HasOne                    // Perfil 1:1 (nombre, apellido, cv, foto, etc.) //
    {
        return $this->hasOne(Perfil::class);
    }

    public function ofertasEmpleo(): HasMany            // Ofertas publicadas (solo Empresa) //
    {
        return $this->hasMany(OfertaEmpleo::class);
    }

    public function postulaciones(): HasMany            // Postulaciones hechas (solo Candidato) //
    {
        return $this->hasMany(Postulacion::class);
    }

    public function listaDeseos(): HasMany              // Ofertas guardadas como favoritas (Candidato) //
    {
        return $this->hasMany(ListaDeseo::class);
    }

    public function notificaciones(): HasMany           // Notificaciones recibidas //
    {
        return $this->hasMany(Notificacion::class);
    }

    public function logs(): HasMany                     // Registro de acciones del usuario //
    {
        return $this->hasMany(Log::class);
    }

    public function pagos(): HasMany                    // pagos hechos por esta empresa //
    {
        return $this->hasMany(Pago::class);
    }
}
