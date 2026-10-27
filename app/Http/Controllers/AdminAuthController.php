<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        return view('admin.login');
    }

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

        $guard = Auth::guard('admin');

        if (!$guard->validate($credenciales)) {
            return back()
                ->withErrors(['usuario' => 'El usuario o la contraseña son incorrectos.'])
                ->onlyInput('usuario');
        }

        $admin = Administrador::where('usuario', $credenciales['usuario'])->first();

        if (!$admin->activo) {
            return back()
                ->withErrors(['usuario' => 'Tu cuenta está deshabilitada. Contacta a otro administrador.'])
                ->onlyInput('usuario');
        }

        $guard->login($admin);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard')
            ->with('exito', "Inicio de sesión exitoso. Bienvenido, {$admin->nombre}.");
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flash('status', 'Sesión cerrada correctamente.');

        return Inertia::location(route('admin.login'));
    }
}
