<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rol extends Model
{
    protected $table = 'roles';   // catálogo inamovible: Candidato, Empresa, Administrador //

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function usuarios(): HasMany   // Usuarios con este rol //
    {
        return $this->hasMany(Usuario::class);
    }
}
