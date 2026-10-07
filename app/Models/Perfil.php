<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Perfil extends Model
{
    protected $table = 'perfiles';
    protected $fillable = [
        'usuario_id',
        'profesion_oficio',
        'descripcion',
        'experiencia',
        'habilidades',
        'ciudad',
        'cv',
        'foto',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
