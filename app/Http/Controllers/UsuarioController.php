<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class UsuarioController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Usuario::with('rol:id,nombre')->paginate(15),
        ]);
    }

    public function eliminados()
    {
        return response()->json([
            'data' => Usuario::onlyTrashed()
                ->with('rol:id,nombre')
                ->paginate(15),
        ]);
    }

    public function show($id)
    {
        $usuario = Usuario::withTrashed()
            ->with(['rol:id,nombre', 'perfil'])
            ->withCount([
                'postulaciones',
                'listaDeseos',
                'transacciones',
                'loginSociales',
                'logs',
            ])
            ->find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'El usuario solicitado no existe.',
            ], 404);
        }

        $zona = 'America/Mexico_City';

        return response()->json([
            'data' => [
                'id' => $usuario->id,
                'rol_id' => $usuario->rol_id,
                'rol' => $usuario->rol?->nombre,
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'correo' => $usuario->correo,
                'telefono' => $usuario->telefono,
                'fecha_registro' => $usuario->fecha_registro
                    ? Carbon::parse($usuario->fecha_registro)->timezone($zona)->format('d/m/Y H:i')
                    : null,
                'estado' => $usuario->estado,
                'perfil' => $usuario->perfil?->profesion_oficio,
                'postulaciones_count' => $usuario->postulaciones_count,
                'lista_deseos_count' => $usuario->lista_deseos_count,
                'transacciones_count' => $usuario->transacciones_count,
                'login_sociales_count' => $usuario->login_sociales_count,
                'logs_count' => $usuario->logs_count,
                'creado' => $usuario->created_at?->timezone($zona)->format('d/m/Y H:i'),
                'actualizado' => $usuario->updated_at?->timezone($zona)->format('d/m/Y H:i'),
                'eliminado' => $usuario->trashed(),
                'fecha_eliminacion' => $usuario->deleted_at?->timezone($zona)->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $datos['password'] = Hash::make($datos['password']);
        $datos['fecha_registro'] = Carbon::parse($datos['fecha_registro'])
            ->format('Y-m-d H:i:s');

        try {
            $usuario = Usuario::create($datos);
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo guardar el usuario.',
            ], 500);
        }

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'data' => $usuario->load('rol:id,nombre'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'El usuario solicitado no existe o está eliminado.',
            ], 404);
        }

        $datos = $this->validar($request, $usuario->id);

        $datos['fecha_registro'] = Carbon::parse($datos['fecha_registro'])
            ->format('Y-m-d H:i:s');

        // Si no se envía contraseña o viene vacía, se conserva la actual. //
        if (!empty($datos['password'])) {
            $datos['password'] = Hash::make($datos['password']);
        } else {
            unset($datos['password']);
        }

        $usuario->fill($datos);

        if (!$usuario->isDirty()) {
            return response()->json([
                'message' => 'No se realizaron cambios en el usuario.',
                'data' => $usuario->load('rol:id,nombre'),
            ]);
        }

        try {
            $usuario->save();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo actualizar el usuario.',
            ], 500);
        }

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'data' => $usuario->fresh()->load('rol:id,nombre'),
        ]);
    }

    // Borrado lógico //
    public function destroy($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'El usuario solicitado no existe o ya fue eliminado.',
            ], 404);
        }

        try {
            $usuario->delete();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar el usuario.',
            ], 500);
        }

        return response()->json([
            'message' => "El usuario '{$usuario->nombre} {$usuario->apellido}' se eliminó lógicamente. Puedes restaurarlo.",
        ]);
    }

    public function restore($id)
    {
        $usuario = Usuario::onlyTrashed()->find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'El usuario solicitado no existe o no está eliminado.',
            ], 404);
        }

        try {
            $usuario->restore();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo restaurar el usuario.',
            ], 500);
        }

        return response()->json([
            'message' => "El usuario '{$usuario->nombre} {$usuario->apellido}' se restauró correctamente.",
            'data' => $usuario->fresh()->load('rol:id,nombre'),
        ]);
    }

    // Borrado físico: requiere borrado lógico previo y no permite dependencias. //
    public function forceDelete($id)
    {
        $usuario = Usuario::withTrashed()->find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'El usuario solicitado no existe.',
            ], 404);
        }

        if (!$usuario->trashed()) {
            return response()->json([
                'message' => 'El usuario debe eliminarse primero mediante borrado lógico.',
            ], 409);
        }

        // Se incluyen registros relacionados eliminados lógicamente. //
        $dependencias = [
            'perfil' => [
                $usuario->perfil()->withTrashed()->count(),
                'perfil',
                'perfiles',
            ],
            'postulaciones' => [
                $usuario->postulaciones()->withTrashed()->count(),
                'postulación',
                'postulaciones',
            ],
            'lista de deseos' => [
                $usuario->listaDeseos()->count(),
                'elemento en la lista de deseos',
                'elementos en la lista de deseos',
            ],
            'transacciones' => [
                $usuario->transacciones()->withTrashed()->count(),
                'transacción',
                'transacciones',
            ],
            'login sociales' => [
                $usuario->loginSociales()->withTrashed()->count(),
                'login social',
                'login sociales',
            ],
            'logs' => [
                $usuario->logs()->withTrashed()->count(),
                'log',
                'logs',
            ],
        ];

        $detalle = [];

        foreach ($dependencias as [$total, $singular, $plural]) {
            if ($total > 0) {
                $detalle[] = $total . ' ' . ($total === 1 ? $singular : $plural);
            }
        }

        if (count($detalle) > 0) {
            return response()->json([
                'message' => "No se puede eliminar definitivamente al usuario '{$usuario->nombre} {$usuario->apellido}': tiene registros asociados (" . implode(', ', $detalle) . '). Elimina primero esos registros.',
                'dependencias' => $detalle,
            ], 409);
        }

        try {
            $usuario->forceDelete();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar definitivamente el usuario.',
            ], 500);
        }

        return response()->json([
            'message' => "El usuario '{$usuario->nombre} {$usuario->apellido}' se eliminó definitivamente.",
        ]);
    }

    private function validar(Request $request, $id = null): array
    {
        return $request->validate([
            'rol_id' => [
                'required',
                Rule::exists('roles', 'id')->whereNull('deleted_at'),
            ],
            'nombre' => ['required', 'string', 'min:2', 'max:100'],
            'apellido' => ['required', 'string', 'min:2', 'max:100'],
            'correo' => [
                'required',
                'email',
                'max:150',
                Rule::unique('usuarios', 'correo')->ignore($id),
            ],
            'password' => [
                $id ? 'nullable' : 'required',
                'string',
                'min:8',
                'max:255',
            ],
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
            'exists' => 'El rol seleccionado no existe o está eliminado.',
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
