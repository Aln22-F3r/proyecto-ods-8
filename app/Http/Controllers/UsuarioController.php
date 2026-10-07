<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UsuarioController extends Controller
{
    public function index()
    {
        return Inertia::render('Usuarios/Index', [
            'usuarios' => Usuario::with('rol:id,nombre')->paginate(5),
        ]);
    }

    public function create()
    {
        return Inertia::render('Usuarios/Form', [
            'roles' => Rol::select('id', 'nombre')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'rol_id' => ['required', 'exists:roles,id'],
            'nombre' => ['required', 'string', 'min:2', 'max:100'],
            'apellido' => ['required', 'string', 'min:2', 'max:100'],
            'correo' => ['required', 'email', 'max:150', 'unique:usuarios,correo'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'telefono' => ['required', 'digits_between:10,20'],
            'fecha_registro' => ['required', 'date'],
            'estado' => ['required', 'boolean'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
            'email' => 'El correo no tiene un formato válido.',
            'unique' => 'Ese :attribute ya está registrado.',
            'exists' => 'El rol seleccionado no es válido.',
            'digits_between' => 'El teléfono debe tener solo números, entre :min y :max dígitos.',
            'date' => 'La fecha de registro no es válida.',
            'boolean' => 'El estado no es válido.',
        ], [
            'rol_id' => 'rol',
            'correo' => 'correo',
            'password' => 'contraseña',
            'telefono' => 'teléfono',
            'fecha_registro' => 'fecha de registro',
        ]);

        $datos['password'] = Hash::make($datos['password']);
        $datos['fecha_registro'] = Carbon::parse($datos['fecha_registro'])->format('Y-m-d H:i:s');

        try {
            Usuario::create($datos);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo guardar el usuario. Intenta de nuevo.');
        }

        return redirect('/usuarios')->with('exito', 'Usuario creado correctamente.');
    }
}
