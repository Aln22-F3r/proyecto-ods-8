<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Administrador extends Authenticatable
{
    protected $table = 'administradores';

    protected $fillable = [
        'nombre',
        'usuario',
        'password',
        'activo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
