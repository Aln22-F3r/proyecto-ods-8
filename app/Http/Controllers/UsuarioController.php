<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
            'roles' => $this->roles(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $datos['password'] = Hash::make($datos['password']);
        $datos['fecha_registro'] = Carbon::parse($datos['fecha_registro'])->format('Y-m-d H:i:s');

        try {
            Usuario::create($datos);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo guardar el usuario. Intenta de nuevo.');
        }

        return redirect('/usuarios')->with('exito', 'Usuario creado correctamente.');
    }

    public function edit($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return redirect('/usuarios')->with('error', 'El usuario solicitado no existe. No se puede editar.');
        }

        return Inertia::render('Usuarios/Form', [
            'usuario' => [
                'id' => $usuario->id,
                'rol_id' => $usuario->rol_id,
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'correo' => $usuario->correo,
                'telefono' => $usuario->telefono,
                'fecha_registro' => Carbon::parse($usuario->fecha_registro)->format('Y-m-d\TH:i:s'),
                'estado' => $usuario->estado,
            ],
            'roles' => $this->roles(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return redirect('/usuarios')->with('error', 'El usuario solicitado no existe. No se puede actualizar.');
        }

        $datos = $this->validar($request, $usuario->id);

        $datos['fecha_registro'] = Carbon::parse($datos['fecha_registro'])->format('Y-m-d H:i:s');

        // Contraseña vacía = se conserva la actual //
        if (!empty($datos['password'])) {
            $datos['password'] = Hash::make($datos['password']);
        } else {
            unset($datos['password']);
        }

        $usuario->fill($datos);

        if (!$usuario->isDirty()) {
            return redirect('/usuarios')->with('advertencia', 'No se realizaron cambios en el usuario.');
        }

        try {
            $usuario->save();
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo actualizar el usuario. Intenta de nuevo.');
        }

        return redirect('/usuarios')->with('exito', 'Usuario actualizado correctamente.');
    }

    private function roles()
    {
        return Rol::select('id', 'nombre')->orderBy('nombre')->get();
    }

    private function validar(Request $request, $id = null): array
    {
        return $request->validate([
            'rol_id' => ['required', 'exists:roles,id'],
            'nombre' => ['required', 'string', 'min:2', 'max:100'],
            'apellido' => ['required', 'string', 'min:2', 'max:100'],
            'correo' => [
                'required',
                'email',
                'max:150',
                Rule::unique('usuarios', 'correo')->ignore($id),
            ],
            'password' => [$id ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
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
    }
}
