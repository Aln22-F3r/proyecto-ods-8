<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Administrador extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'administradores';

    protected $fillable = [
        'nombre',
        'usuario',
        'password',
        'activo',
        'nivel',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'nivel' => 'integer',
    ];
}
