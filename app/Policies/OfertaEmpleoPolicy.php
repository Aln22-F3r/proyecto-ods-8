<?php

namespace App\Policies;

use App\Models\OfertaEmpleo;
use App\Models\Usuario;

class OfertaEmpleoPolicy
{
    // Cualquier usuario autenticado puede ver el listado (se filtra en el controlador por rol) //
    public function viewAny(Usuario $user): bool
    {
        return in_array($user->rol?->nombre, ['Administrador', 'Empresa', 'Candidato'], true);
    }

    // Admin ve todo; dueño empresa ve la suya aunque esté cerrada; otros solo abiertas y no borradas //
    public function view(Usuario $user, OfertaEmpleo $oferta): bool
    {
        if ($user->rol?->nombre === 'Administrador') {
            return true;
        }

        if ($oferta->trashed()) {
            return false;   // no-admin nunca ve una oferta eliminada //
        }

        if ($user->rol?->nombre === 'Empresa' && $oferta->usuario_id === $user->id) {
            return true;    // empresa dueña ve la suya aunque esté cerrada //
        }

        return $oferta->estado === 'Abierta';   // candidato/otros solo abiertas //
    }

    // Empresa crea para sí; admin nivel 2+ crea para cualquier empresa //
    public function create(Usuario $user): bool
    {
        if ($user->rol?->nombre === 'Empresa') {
            return true;
        }

        return $user->rol?->nombre === 'Administrador'
            && in_array($user->nivel, [2, 3], true);
    }

    // Admin nivel 2+ edita cualquier oferta; empresa edita solo la suya //
    public function update(Usuario $user, OfertaEmpleo $oferta): bool
    {
        if ($user->rol?->nombre === 'Administrador' && in_array($user->nivel, [2, 3], true)) {
            return true;
        }

        return $user->rol?->nombre === 'Empresa' && $oferta->usuario_id === $user->id;
    }

    // Solo la empresa dueña puede pagar para destacar su propia oferta abierta //
    public function destacar(Usuario $user, OfertaEmpleo $oferta): bool
    {
        return $user->rol?->nombre === 'Empresa'
            && $oferta->usuario_id === $user->id
            && $oferta->estado === 'Abierta';
    }

    // Admin nivel 3 borra cualquier oferta; empresa borra solo la suya //
    public function delete(Usuario $user, OfertaEmpleo $oferta): bool
    {
        if ($user->rol?->nombre === 'Administrador' && $user->nivel === 3) {
            return true;
        }

        return $user->rol?->nombre === 'Empresa' && $oferta->usuario_id === $user->id;
    }

    // Solo admin nivel 3 restaura //
    public function restore(Usuario $user, OfertaEmpleo $oferta): bool
    {
        return $user->rol?->nombre === 'Administrador' && $user->nivel === 3;
    }

    // Solo admin nivel 3 borra físicamente //
    public function forceDelete(Usuario $user, OfertaEmpleo $oferta): bool
    {
        return $user->rol?->nombre === 'Administrador' && $user->nivel === 3;
    }
}
