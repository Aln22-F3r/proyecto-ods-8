<?php

namespace App\Http\Middleware;

use App\Models\Administrador;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        $administrador = $request->user();

        if (!$administrador instanceof Administrador) {
            return response()->json([
                'mensaje' => 'No tienes autorización para acceder a este recurso.',
            ], 403);
        }

        if (!$administrador->activo) {
            $token = $administrador->currentAccessToken();

            if ($token instanceof PersonalAccessToken) {
                $token->delete();
            }

            return response()->json([
                'mensaje' => 'Tu cuenta de administrador está deshabilitada.',
            ], 403);
        }

        return $next($request);
    }
}
