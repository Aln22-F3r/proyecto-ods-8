<?php

namespace App\Policies;

use App\Models\Postulacion;
use App\Models\Usuario;

class PostulacionPolicy
{
    // Cualquier rol autenticado puede listar (se filtra por rol en el controlador) //
    public function viewAny(Usuario $user): bool
    {
        return in_array($user->rol?->nombre, ['Administrador', 'Empresa', 'Candidato'], true);
    }

    // Admin todo; candidato dueño; empresa dueña de la oferta //
    public function view(Usuario $user, Postulacion $postulacion): bool
    {
        $rol = $user->rol?->nombre;

        if ($rol === 'Administrador') {
            return true;
        }

        // Candidato dueño de la postulación //
        if ($rol === 'Candidato' && $postulacion->usuario_id === $user->id) {
            return true;
        }

        // Empresa dueña de la oferta a la que se postuló //
        if ($rol === 'Empresa') {
            $oferta = $postulacion->ofertaEmpleo;
            return $oferta !== null && $oferta->usuario_id === $user->id;
        }

        return false;
    }

    // Solo candidato o admin nivel 2+ pueden crear (empresa nunca) //
    public function create(Usuario $user): bool
    {
        $rol = $user->rol?->nombre;

        if ($rol === 'Candidato') {
            return true;
        }

        if ($rol === 'Administrador' && in_array($user->nivel, [2, 3], true)) {
            return true;
        }

        return false;
    }

    // Admin 2+; candidato dueño (para marcar Retirada); empresa dueña de la oferta (para cambiar estado) //
    public function update(Usuario $user, Postulacion $postulacion): bool
    {
        $rol = $user->rol?->nombre;

        if ($rol === 'Administrador' && in_array($user->nivel, [2, 3], true)) {
            return true;
        }

        if ($rol === 'Candidato' && $postulacion->usuario_id === $user->id) {
            return true;
        }

        if ($rol === 'Empresa') {
            $oferta = $postulacion->ofertaEmpleo;
            return $oferta !== null && $oferta->usuario_id === $user->id;
        }

        return false;
    }

    // Candidato dueño (se retira) o admin nivel 3. Empresa NO borra (solo evalúa) //
    public function delete(Usuario $user, Postulacion $postulacion): bool
    {
        $rol = $user->rol?->nombre;

        if ($rol === 'Administrador' && $user->nivel === 3) {
            return true;
        }

        if ($rol === 'Candidato' && $postulacion->usuario_id === $user->id) {
            return true;
        }

        return false;
    }

    // Solo admin nivel 3 restaura //
    public function restore(Usuario $user, Postulacion $postulacion): bool
    {
        return $user->rol?->nombre === 'Administrador' && $user->nivel === 3;
    }

    // Solo admin nivel 3 borra físicamente //
    public function forceDelete(Usuario $user, Postulacion $postulacion): bool
    {
        return $user->rol?->nombre === 'Administrador' && $user->nivel === 3;
    }
}
