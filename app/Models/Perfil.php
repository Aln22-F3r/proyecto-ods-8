<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Perfil extends Model
{
    // Sin SoftDeletes: vive y muere con el usuario (cascade) //

    protected $table = 'perfiles';

    protected $fillable = [
        'usuario_id',
        'nombre',
        'apellido',
        'telefono',
        'profesion_oficio',
        'descripcion',
        'experiencia',
        'habilidades',
        'ciudad',
        'cv',
        'foto',
    ];

    protected $casts = [
        'experiencia' => 'integer',   // se guarda como int //
    ];

    public function usuario(): BelongsTo   // Dueño del perfil; sin withTrashed() para que whereHas('usuario') oculte borrados //
    {
        return $this->belongsTo(Usuario::class);
    }
}
