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
            'eliminados' => false,
        ]);
    }

    public function eliminados()
    {
        return Inertia::render('LoginSociales/Index', [
            'loginSociales' => LoginSocial::onlyTrashed()->with('usuario:id,nombre,apellido')->paginate(5),
            'eliminados' => true,
        ]);
    }

    public function show($id)
    {
        $loginSocial = LoginSocial::withTrashed()
            ->with('usuario:id,nombre,apellido,correo')
            ->find($id);

        if (!$loginSocial) {
            return redirect('/login-sociales')->with('error', 'El login social solicitado no existe.');
        }

        $zona = 'America/Mexico_City';

        return Inertia::render('LoginSociales/Show', [
            'loginSocial' => [
                'id' => $loginSocial->id,
                'usuario' => $loginSocial->usuario
                    ? "{$loginSocial->usuario->nombre} {$loginSocial->usuario->apellido}"
                    : null,
                'correo_usuario' => $loginSocial->usuario?->correo,
                'proveedor' => $loginSocial->proveedor,
                'proveedor_id' => $loginSocial->proveedor_id,
                'correo' => $loginSocial->correo,
                'creado' => $loginSocial->created_at?->timezone($zona)->format('d/m/Y H:i'),
                'actualizado' => $loginSocial->updated_at?->timezone($zona)->format('d/m/Y H:i'),
                'eliminado' => $loginSocial->trashed(),
                'fecha_eliminacion' => $loginSocial->deleted_at?->timezone($zona)->format('d/m/Y H:i'),
            ],
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
            // Se incluye el usuario actual aunque esté eliminado, para que no desaparezca del select //
            'usuarios' => Usuario::withTrashed()
                ->where(function ($q) use ($loginSocial) {
                    $q->whereNull('deleted_at')->orWhere('id', $loginSocial->usuario_id);
                })
                ->select('id', 'nombre', 'apellido')
                ->orderBy('nombre')
                ->get(),
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

    // Borrado lógico: solo llena deleted_at, el registro se conserva //
    public function destroy($id)
    {
        $loginSocial = LoginSocial::with('usuario:id,nombre,apellido')->find($id);

        if (!$loginSocial) {
            return redirect('/login-sociales')->with('error', 'El login social solicitado no existe o ya fue eliminado.');
        }

        try {
            $loginSocial->delete();
        } catch (\Throwable $e) {
            return redirect('/login-sociales')->with('error', 'No se pudo eliminar el login social. Intenta de nuevo.');
        }

        return redirect('/login-sociales')->with('exito', "{$this->etiqueta($loginSocial)} se eliminó. Puedes restaurarlo desde la lista de eliminados.");
    }

    public function restore($id)
    {
        $loginSocial = LoginSocial::onlyTrashed()->with('usuario:id,nombre,apellido')->find($id);

        if (!$loginSocial) {
            return redirect('/login-sociales/eliminados')->with('error', 'El login social solicitado no existe o no está eliminado.');
        }

        try {
            $loginSocial->restore();
        } catch (\Throwable $e) {
            return redirect('/login-sociales/eliminados')->with('error', 'No se pudo restaurar el login social. Intenta de nuevo.');
        }

        return redirect('/login-sociales/eliminados')->with('exito', "{$this->etiqueta($loginSocial)} se restauró y ya aparece en la lista principal.");
    }

    // Borrado físico: solo para registros ya eliminados. Ninguna otra tabla depende de los login sociales. //
    public function forceDelete($id)
    {
        $loginSocial = LoginSocial::withTrashed()->with('usuario:id,nombre,apellido')->find($id);

        if (!$loginSocial) {
            return redirect('/login-sociales/eliminados')->with('error', 'El login social solicitado no existe.');
        }

        if (!$loginSocial->trashed()) {
            return redirect('/login-sociales')->with('advertencia', 'El login social debe eliminarse primero (borrado lógico) antes de poder borrarlo definitivamente.');
        }

        $etiqueta = $this->etiqueta($loginSocial);

        try {
            $loginSocial->forceDelete();
        } catch (\Throwable $e) {
            return redirect('/login-sociales/eliminados')->with('error', 'No se pudo eliminar definitivamente el login social. Intenta de nuevo.');
        }

        return redirect('/login-sociales/eliminados')->with('exito', "{$etiqueta} se eliminó definitivamente.");
    }

    private function etiqueta(LoginSocial $loginSocial): string
    {
        $usuario = $loginSocial->usuario
            ? "{$loginSocial->usuario->nombre} {$loginSocial->usuario->apellido}"
            : 'un usuario';

        return "El login social de «{$loginSocial->proveedor}» de «{$usuario}»";
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
