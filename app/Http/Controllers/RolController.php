<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class RolController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Rol::withCount('usuarios')->paginate(15),
        ]);
    }

    public function eliminados()
    {
        return response()->json([
            'data' => Rol::onlyTrashed()
                ->withCount('usuarios')
                ->paginate(15),
        ]);
    }

    public function show($id)
    {
        $rol = Rol::withTrashed()
            ->withCount('usuarios')
            ->find($id);

        if (!$rol) {
            return response()->json([
                'message' => 'El rol solicitado no existe.',
            ], 404);
        }

        $zona = 'America/Mexico_City';

        return response()->json([
            'data' => [
                'id' => $rol->id,
                'nombre' => $rol->nombre,
                'descripcion' => $rol->descripcion,
                'usuarios_count' => $rol->usuarios_count,
                'creado' => $rol->created_at?->timezone($zona)->format('d/m/Y H:i'),
                'actualizado' => $rol->updated_at?->timezone($zona)->format('d/m/Y H:i'),
                'eliminado' => $rol->trashed(),
                'fecha_eliminacion' => $rol->deleted_at?->timezone($zona)->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        try {
            $rol = Rol::create($datos);
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo guardar el rol.',
            ], 500);
        }

        return response()->json([
            'message' => 'Rol creado correctamente.',
            'data' => $rol,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json([
                'message' => 'El rol solicitado no existe o está eliminado.',
            ], 404);
        }

        $datos = $this->validar($request);

        $rol->fill($datos);

        if (!$rol->isDirty()) {
            return response()->json([
                'message' => 'No se realizaron cambios en el rol.',
                'data' => $rol,
            ]);
        }

        try {
            $rol->save();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo actualizar el rol.',
            ], 500);
        }

        return response()->json([
            'message' => 'Rol actualizado correctamente.',
            'data' => $rol->fresh(),
        ]);
    }

    // Borrado lógico //
    public function destroy($id)
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json([
                'message' => 'El rol solicitado no existe o ya fue eliminado.',
            ], 404);
        }

        try {
            $rol->delete();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar el rol.',
            ], 500);
        }

        return response()->json([
            'message' => "El rol '{$rol->nombre}' se eliminó lógicamente. Puedes restaurarlo.",
        ]);
    }

    public function restore($id)
    {
        $rol = Rol::onlyTrashed()->find($id);

        if (!$rol) {
            return response()->json([
                'message' => 'El rol solicitado no existe o no está eliminado.',
            ], 404);
        }

        try {
            $rol->restore();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo restaurar el rol.',
            ], 500);
        }

        return response()->json([
            'message' => "El rol '{$rol->nombre}' se restauró correctamente.",
            'data' => $rol->fresh(),
        ]);
    }

    // Borrado físico: requiere borrado lógico previo y no permite roles con usuarios asociados //
    public function forceDelete($id)
    {
        $rol = Rol::withTrashed()->find($id);

        if (!$rol) {
            return response()->json([
                'message' => 'El rol solicitado no existe.',
            ], 404);
        }

        if (!$rol->trashed()) {
            return response()->json([
                'message' => 'El rol debe eliminarse primero mediante borrado lógico.',
            ], 409);
        }

        // Incluye usuarios eliminados lógicamente //
        $total = $rol->usuarios()->withTrashed()->count();

        if ($total > 0) {
            $usuarios = $total === 1 ? '1 usuario' : "{$total} usuarios";

            return response()->json([
                'message' => "No se puede eliminar definitivamente el rol '{$rol->nombre}': está asociado a {$usuarios}.",
            ], 409);
        }

        try {
            $rol->forceDelete();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar definitivamente el rol.',
            ], 500);
        }

        return response()->json([
            'message' => "El rol '{$rol->nombre}' se eliminó definitivamente.",
        ]);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:50'],
            'descripcion' => ['required', 'string', 'min:5', 'max:150'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
        ], [
            'nombre' => 'nombre',
            'descripcion' => 'descripción',
        ]);
    }
}
