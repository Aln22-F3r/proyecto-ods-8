<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticatedUser
{
    // Middleware 'usuario.activo': valida cuenta activa y con rol en cada request autenticado //

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        // Debe ser un Usuario de nuestra app (no otro modelo autenticable) //
        if (!$usuario instanceof Usuario) {
            return response()->json([
                'message' => 'No tienes autorización para acceder a este recurso.',
            ], 403);
        }

        // Cuenta deshabilitada o sin rol → revocar token y rechazar //
        if (!$usuario->estado || !$usuario->rol) {
            $token = $usuario->currentAccessToken();

            if ($token instanceof PersonalAccessToken) {
                $token->delete();
            }

            return response()->json([
                'message' => 'Tu cuenta está deshabilitada o no tiene un rol asignado.',
            ], 403);
        }

        return $next($request);
    }
}
