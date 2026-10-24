<?php

namespace App\Http\Controllers;

use App\Models\LoginSocial;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class LoginSocialController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => LoginSocial::with('usuario:id,nombre,apellido')
                ->paginate(15),
        ]);
    }

    public function eliminados()
    {
        return response()->json([
            'data' => LoginSocial::onlyTrashed()
                ->with('usuario:id,nombre,apellido')
                ->paginate(15),
        ]);
    }

    public function show($id)
    {
        $loginSocial = LoginSocial::withTrashed()
            ->with('usuario:id,nombre,apellido,correo')
            ->find($id);

        if (!$loginSocial) {
            return response()->json([
                'message' => 'El login social solicitado no existe.',
            ], 404);
        }

        $zona = 'America/Mexico_City';

        return response()->json([
            'data' => [
                'id' => $loginSocial->id,
                'usuario_id' => $loginSocial->usuario_id,
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

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        try {
            $loginSocial = LoginSocial::create($datos);
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo guardar el login social. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => 'Login social creado correctamente.',
            'data' => $loginSocial->load('usuario:id,nombre,apellido'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $loginSocial = LoginSocial::find($id);

        if (!$loginSocial) {
            return response()->json([
                'message' => 'El login social solicitado no existe o está eliminado.',
            ], 404);
        }

        $datos = $this->validar($request, $loginSocial->id);

        $loginSocial->fill($datos);

        if (!$loginSocial->isDirty()) {
            return response()->json([
                'message' => 'No se realizaron cambios en el login social.',
                'data' => $loginSocial->load('usuario:id,nombre,apellido'),
            ]);
        }

        try {
            $loginSocial->save();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo actualizar el login social. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => 'Login social actualizado correctamente.',
            'data' => $loginSocial->fresh()->load('usuario:id,nombre,apellido'),
        ]);
    }

    // Borrado lógico //
    public function destroy($id)
    {
        $loginSocial = LoginSocial::with('usuario:id,nombre,apellido')
            ->find($id);

        if (!$loginSocial) {
            return response()->json([
                'message' => 'El login social solicitado no existe o ya fue eliminado.',
            ], 404);
        }

        $etiqueta = $this->etiqueta($loginSocial);

        try {
            $loginSocial->delete();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar el login social. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => "{$etiqueta} se eliminó. Puedes restaurarlo desde la lista de eliminados.",
        ]);
    }

    public function restore($id)
    {
        $loginSocial = LoginSocial::onlyTrashed()
            ->with('usuario:id,nombre,apellido')
            ->find($id);

        if (!$loginSocial) {
            return response()->json([
                'message' => 'El login social solicitado no existe o no está eliminado.',
            ], 404);
        }

        try {
            $loginSocial->restore();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo restaurar el login social. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => "{$this->etiqueta($loginSocial)} se restauró correctamente.",
            'data' => $loginSocial->fresh()->load('usuario:id,nombre,apellido'),
        ]);
    }

    // Borrado físico: solo para registros ya eliminados //
    public function forceDelete($id)
    {
        $loginSocial = LoginSocial::withTrashed()
            ->with('usuario:id,nombre,apellido')
            ->find($id);

        if (!$loginSocial) {
            return response()->json([
                'message' => 'El login social solicitado no existe.',
            ], 404);
        }

        if (!$loginSocial->trashed()) {
            return response()->json([
                'message' => 'El login social debe eliminarse primero antes de borrarlo definitivamente.',
            ], 409);
        }

        $etiqueta = $this->etiqueta($loginSocial);

        try {
            $loginSocial->forceDelete();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar definitivamente el login social. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => "{$etiqueta} se eliminó definitivamente.",
        ]);
    }

    private function etiqueta(LoginSocial $loginSocial): string
    {
        $usuario = $loginSocial->usuario
            ? "{$loginSocial->usuario->nombre} {$loginSocial->usuario->apellido}"
            : 'un usuario';

        return "El login social de «{$loginSocial->proveedor}» de «{$usuario}»";
    }

    private function validar(Request $request, $id = null): array
    {
        return $request->validate([
            'usuario_id' => [
                'required',
                Rule::exists('usuarios', 'id')->whereNull('deleted_at'),
            ],
            'proveedor' => [
                'required',
                'in:Google,Facebook,GitHub',
            ],
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
            'exists' => 'El usuario seleccionado no es válido o está eliminado.',
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
