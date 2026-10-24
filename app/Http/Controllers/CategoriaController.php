<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;

class CategoriaController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Categoria::withCount('ofertasEmpleo')->paginate(15),
        ]);
    }

    public function eliminados()
    {
        return response()->json([
            'data' => Categoria::onlyTrashed()
                ->withCount('ofertasEmpleo')
                ->paginate(15),
        ]);
    }

    public function show($id)
    {
        $categoria = Categoria::withTrashed()
            ->withCount('ofertasEmpleo')
            ->find($id);

        if (!$categoria) {
            return response()->json([
                'message' => 'La categoría solicitada no existe.',
            ], 404);
        }

        $zona = 'America/Mexico_City';

        return response()->json([
            'data' => [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'descripcion' => $categoria->descripcion,
                'ofertas_count' => $categoria->ofertas_empleo_count,
                'creado' => $categoria->created_at
                    ?->timezone($zona)->format('d/m/Y H:i'),
                'actualizado' => $categoria->updated_at
                    ?->timezone($zona)->format('d/m/Y H:i'),
                'eliminado' => $categoria->trashed(),
                'fecha_eliminacion' => $categoria->deleted_at
                    ?->timezone($zona)->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        try {
            $categoria = Categoria::create($datos);
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo guardar la categoría.',
            ], 500);
        }

        return response()->json([
            'message' => 'Categoría creada correctamente.',
            'data' => $categoria->loadCount('ofertasEmpleo'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'message' => 'La categoría solicitada no existe o está eliminada.',
            ], 404);
        }

        $datos = $this->validar($request);

        $categoria->fill($datos);

        if (!$categoria->isDirty()) {
            return response()->json([
                'message' => 'No se realizaron cambios en la categoría.',
                'data' => $categoria->loadCount('ofertasEmpleo'),
            ]);
        }

        try {
            $categoria->save();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo actualizar la categoría.',
            ], 500);
        }

        return response()->json([
            'message' => 'Categoría actualizada correctamente.',
            'data' => $categoria->fresh()->loadCount('ofertasEmpleo'),
        ]);
    }

    public function destroy($id)
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'message' => 'La categoría no existe o ya fue eliminada.',
            ], 404);
        }

        try {
            $categoria->delete();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar la categoría.',
            ], 500);
        }

        return response()->json([
            'message' => "La categoría '{$categoria->nombre}' se eliminó correctamente. Puedes restaurarla desde la lista de eliminadas.",
        ]);
    }

    public function restore($id)
    {
        $categoria = Categoria::onlyTrashed()->find($id);

        if (!$categoria) {
            return response()->json([
                'message' => 'La categoría no existe o no está eliminada.',
            ], 404);
        }

        try {
            $categoria->restore();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo restaurar la categoría.',
            ], 500);
        }

        return response()->json([
            'message' => 'Categoría restaurada correctamente.',
            'data' => $categoria->fresh()->loadCount('ofertasEmpleo'),
        ]);
    }

    public function forceDelete($id)
    {
        $categoria = Categoria::withTrashed()->find($id);

        if (!$categoria) {
            return response()->json([
                'message' => 'La categoría solicitada no existe.',
            ], 404);
        }

        if (!$categoria->trashed()) {
            return response()->json([
                'message' => 'La categoría debe eliminarse lógicamente antes de borrarla definitivamente.',
            ], 409);
        }

        // Incluye ofertas activas y eliminadas lógicamente. //
        $total = $categoria->ofertasEmpleo()->withTrashed()->count();

        if ($total > 0) {
            return response()->json([
                'message' => "No se puede eliminar definitivamente la categoría '{$categoria->nombre}' porque tiene {$total} oferta(s) de empleo asociada(s), activas o eliminadas.",
                'dependencias' => [
                    'ofertas_empleo' => $total,
                ],
            ], 409);
        }

        try {
            $categoria->forceDelete();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar definitivamente la categoría.',
            ], 500);
        }

        return response()->json([
            'message' => 'Categoría eliminada definitivamente.',
        ]);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:100'],
            'descripcion' => ['required', 'string', 'min:5', 'max:255'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
        ], [
            'descripcion' => 'descripción',
        ]);
    }
}
