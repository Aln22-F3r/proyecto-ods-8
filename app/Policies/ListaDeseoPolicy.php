<?php

namespace App\Policies;

use App\Models\ListaDeseo;
use App\Models\Usuario;

class ListaDeseoPolicy
{
    // Candidato y admin pueden listar (se filtra por rol en el controlador) //
    public function viewAny(Usuario $user): bool
    {
        return in_array($user->rol?->nombre, ['Administrador', 'Candidato'], true);
    }

    // Admin ve cualquiera; candidato solo la propia //
    public function view(Usuario $user, ListaDeseo $item): bool
    {
        if ($user->rol?->nombre === 'Administrador') {
            return true;
        }

        return $user->rol?->nombre === 'Candidato' && $item->usuario_id === $user->id;
    }

    // Solo candidato puede agregar a su propia lista //
    public function create(Usuario $user): bool
    {
        return $user->rol?->nombre === 'Candidato';
    }

    // Candidato dueño quita el suyo; admin nivel 3 puede limpiar //
    public function delete(Usuario $user, ListaDeseo $item): bool
    {
        if ($user->rol?->nombre === 'Administrador' && $user->nivel === 3) {
            return true;
        }

        return $user->rol?->nombre === 'Candidato' && $item->usuario_id === $user->id;
    }
}
