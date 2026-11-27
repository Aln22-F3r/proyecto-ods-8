<?php

namespace App\Policies;

use App\Models\Pago;
use App\Models\Usuario;

class PagoPolicy
{
    // Admin y empresa pueden listar pagos (se filtra por rol en el controlador) //
    public function viewAny(Usuario $user): bool
    {
        return in_array($user->rol?->nombre, ['Administrador', 'Empresa'], true);
    }

    // Admin ve cualquiera; empresa solo los propios //
    public function view(Usuario $user, Pago $pago): bool
    {
        if ($user->rol?->nombre === 'Administrador') {
            return true;
        }

        return $user->rol?->nombre === 'Empresa' && $pago->usuario_id === $user->id;
    }

    // Solo el dueño empresa puede confirmar, y solo si el pago está pendiente //
    public function confirmar(Usuario $user, Pago $pago): bool
    {
        return $user->rol?->nombre === 'Empresa'
            && $pago->usuario_id === $user->id
            && $pago->estado === Pago::ESTADO_PENDIENTE;
    }
}
