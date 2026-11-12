<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'usuario' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'usuario.required' => 'El nombre de usuario es obligatorio.',
            'usuario.max' => 'El nombre de usuario no puede tener más de :max caracteres.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.max' => 'La contraseña no puede tener más de :max caracteres.',
        ]);

        $administrador = Administrador::where(
            'usuario',
            $credenciales['usuario']
        )->first();

        if (
            !$administrador ||
            !Hash::check($credenciales['password'], $administrador->password)
        ) {
            throw ValidationException::withMessages([
                'usuario' => ['El usuario o la contraseña son incorrectos.'],
            ]);
        }

        if (!$administrador->activo) {
            return response()->json([
                'mensaje' => 'Tu cuenta está deshabilitada. Contacta a otro administrador.',
            ], 403);
        }

        $token = $administrador->createToken('admin-token')->plainTextToken;

        return response()->json([
            'mensaje' => "Inicio de sesión exitoso. Bienvenido, {$administrador->nombre}.",
            'token' => $token,
            'administrador' => $administrador,
        ], 200);
    }

    public function me(Request $request)
    {
        return response()->json([
            'administrador' => $request->user(),
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'mensaje' => 'Sesión cerrada correctamente.',
        ], 200);
    }
}
