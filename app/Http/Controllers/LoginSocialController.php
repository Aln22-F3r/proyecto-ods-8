<?php

namespace App\Http\Controllers;

use App\Models\LoginSocial;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LoginSocialController extends Controller
{
    public function index()
    {
        return Inertia::render('LoginSociales/Index', [
            'loginSociales' => LoginSocial::with('usuario:id,nombre,apellido')->paginate(5),
        ]);
    }

    public function create()
    {
        return Inertia::render('LoginSociales/Form', [
            'usuarios' => $this->usuarios(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        try {
            LoginSocial::create($datos);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo guardar el login social. Intenta de nuevo.');
        }

        return redirect('/login-sociales')->with('exito', 'Login social creado correctamente.');
    }

    public function edit($id)
    {
        $loginSocial = LoginSocial::find($id);

        if (!$loginSocial) {
            return redirect('/login-sociales')->with('error', 'El login social solicitado no existe. No se puede editar.');
        }

        return Inertia::render('LoginSociales/Form', [
            'loginSocial' => [
                'id' => $loginSocial->id,
                'usuario_id' => $loginSocial->usuario_id,
                'proveedor' => $loginSocial->proveedor,
                'proveedor_id' => $loginSocial->proveedor_id,
                'correo' => $loginSocial->correo,
            ],
            'usuarios' => $this->usuarios(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $loginSocial = LoginSocial::find($id);

        if (!$loginSocial) {
            return redirect('/login-sociales')->with('error', 'El login social solicitado no existe. No se puede actualizar.');
        }

        $datos = $this->validar($request, $loginSocial->id);

        $loginSocial->fill($datos);

        if (!$loginSocial->isDirty()) {
            return redirect('/login-sociales')->with('advertencia', 'No se realizaron cambios en el login social.');
        }

        try {
            $loginSocial->save();
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo actualizar el login social. Intenta de nuevo.');
        }

        return redirect('/login-sociales')->with('exito', 'Login social actualizado correctamente.');
    }

    private function usuarios()
    {
        return Usuario::select('id', 'nombre', 'apellido')->orderBy('nombre')->get();
    }

    private function validar(Request $request, $id = null): array
    {
        return $request->validate([
            'usuario_id' => ['required', 'exists:usuarios,id'],
            'proveedor' => ['required', 'in:Google,Facebook,GitHub'],
            'proveedor_id' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('login_sociales', 'proveedor_id')
                    ->where('proveedor', $request->input('proveedor'))
                    ->ignore($id),
            ],
            'correo' => ['required', 'email', 'max:150'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
            'exists' => 'El usuario seleccionado no es válido.',
            'in' => 'El proveedor seleccionado no es válido.',
            'unique' => 'Ese ID ya está registrado para el proveedor seleccionado.',
            'email' => 'El correo no tiene un formato válido.',
        ], [
            'usuario_id' => 'usuario',
            'proveedor_id' => 'ID del proveedor',
            'correo' => 'correo',
        ]);
    }
}
