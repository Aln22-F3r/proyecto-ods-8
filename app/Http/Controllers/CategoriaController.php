<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    private const ZONA = 'America/Mexico_City';   // zona horaria para formatear fechas //

    // ======================= AUTENTICADO (cualquier rol) ======================= //

    // GET /categorias/listado: lista simple de categorías activas para dropdowns //
    public function listado()
    {
        return response()->json([
            'data' => Categoria::orderBy('nombre')
                ->get(['id', 'nombre', 'descripcion']),
        ]);
    }

    // ======================= ADMIN: LECTURA ======================= //

    // GET /admin/categorias: listado paginado con conteo de ofertas activas //
    public function index()
    {
        Gate::authorize('consultar');   // nivel 1, 2 o 3 //

        return response()->json([
            'data' => Categoria::withCount('ofertasEmpleo')->paginate(15),
        ]);
    }

    // GET /admin/categorias/eliminados: listado de categorías con borrado lógico //
    public function eliminados()
    {
        Gate::authorize('consultar');   // nivel 1, 2 o 3 //

        return response()->json([
            'data' => Categoria::onlyTrashed()
                ->withCount('ofertasEmpleo')
                ->paginate(15),
        ]);
    }

    // GET /admin/categorias/{id}: detalle de una categoría (incluye eliminadas) //
    public function show($id)
    {
        Gate::authorize('consultar');

        $categoria = Categoria::withTrashed()
            ->withCount('ofertasEmpleo')
            ->find($id);

        if (!$categoria) {
            return response()->json([
                'message' => 'La categoría solicitada no existe.',
            ], 404);
        }

        return response()->json([
            'data' => [
                'id'                => $categoria->id,
                'nombre'            => $categoria->nombre,
                'descripcion'       => $categoria->descripcion,
                'ofertas_count'     => $categoria->ofertas_empleo_count,
                'creado'            => $categoria->created_at?->timezone(self::ZONA)->format('d/m/Y H:i'),
                'actualizado'       => $categoria->updated_at?->timezone(self::ZONA)->format('d/m/Y H:i'),
                'eliminado'         => $categoria->trashed(),
                'fecha_eliminacion' => $categoria->deleted_at?->timezone(self::ZONA)->format('d/m/Y H:i'),
            ],
        ]);
    }

    // ======================= ADMIN: ESCRITURA ======================= //

    // POST /admin/categorias: crear categoría //
    public function store(Request $request)
    {
        Gate::authorize('crear');   // nivel 2 o 3 //

        $datos = $this->validar($request);

        try {
            $categoria = Categoria::create($datos);
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo guardar la categoría.',
            ], 500);
        }

        // Auditoría: categoría creada //
        LogService::registrar(
            $request->user()->id,
            'crear-categoria',
            "Creó la categoría «{$categoria->nombre}».",
            'categorias',
            $categoria->id
        );

        return response()->json([
            'message' => 'Categoría creada correctamente.',
            'data'    => $categoria->loadCount('ofertasEmpleo'),
        ], 201);
    }

    // PUT /admin/categorias/{id}: actualizar categoría //
    public function update(Request $request, $id)
    {
        Gate::authorize('editar');   // nivel 2 o 3 //

        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'message' => 'La categoría solicitada no existe o está eliminada.',
            ], 404);
        }

        $datos = $this->validar($request, $categoria->id);

        $categoria->fill($datos);

        // Sin cambios reales → no hacer UPDATE //
        if (!$categoria->isDirty()) {
            return response()->json([
                'message' => 'No se realizaron cambios en la categoría.',
                'data'    => $categoria->loadCount('ofertasEmpleo'),
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

        // Auditoría: categoría actualizada //
        LogService::registrar(
            $request->user()->id,
            'actualizar-categoria',
            "Actualizó la categoría «{$categoria->nombre}».",
            'categorias',
            $categoria->id
        );

        return response()->json([
            'message' => 'Categoría actualizada correctamente.',
            'data'    => $categoria->fresh()->loadCount('ofertasEmpleo'),
        ]);
    }

    // ======================= ADMIN: BORRADO ======================= //

    // DELETE /admin/categorias/{id}: soft delete //
    public function destroy(Request $request, $id)
    {
        Gate::authorize('eliminar');   // solo nivel 3 //

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

        // Auditoría: borrado lógico de categoría //
        LogService::registrar(
            $request->user()->id,
            'eliminar-categoria',
            "Eliminó lógicamente la categoría «{$categoria->nombre}».",
            'categorias',
            $categoria->id
        );

        return response()->json([
            'message' => "La categoría '{$categoria->nombre}' se eliminó. Puedes restaurarla.",
        ]);
    }

    // PATCH /admin/categorias/{id}/restaurar: revertir soft delete //
    public function restore(Request $request, $id)
    {
        Gate::authorize('eliminar');   // solo nivel 3 //

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

        // Auditoría: restaurar categoría //
        LogService::registrar(
            $request->user()->id,
            'restaurar-categoria',
            "Restauró la categoría «{$categoria->nombre}».",
            'categorias',
            $categoria->id
        );

        return response()->json([
            'message' => 'Categoría restaurada correctamente.',
            'data'    => $categoria->fresh()->loadCount('ofertasEmpleo'),
        ]);
    }

    // DELETE /admin/categorias/{id}/definitivo: bloquea si hay ofertas (activas o eliminadas) //
    public function forceDelete(Request $request, $id)
    {
        Gate::authorize('eliminar');   // solo nivel 3 //

        $categoria = Categoria::withTrashed()->find($id);

        if (!$categoria) {
            return response()->json([
                'message' => 'La categoría solicitada no existe.',
            ], 404);
        }

        // Debe estar soft-deleted primero (doble confirmación) //
        if (!$categoria->trashed()) {
            return response()->json([
                'message' => 'La categoría debe eliminarse lógicamente antes de borrarla definitivamente.',
            ], 409);
        }

        // Cuenta ofertas activas Y eliminadas: si hay cualquiera, la FK del schema impide borrar //
        $total = $categoria->ofertasEmpleo()->withTrashed()->count();

        if ($total > 0) {
            return response()->json([
                'message'      => "No se puede eliminar definitivamente la categoría '{$categoria->nombre}' porque tiene {$total} oferta(s) de empleo asociada(s), activas o eliminadas.",
                'dependencias' => ['ofertas_empleo' => $total],
            ], 409);
        }

        $idEliminado    = $categoria->id;
        $nombreEliminado = $categoria->nombre;

        try {
            $categoria->forceDelete();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar definitivamente la categoría.',
            ], 500);
        }

        // Auditoría: borrado físico de categoría //
        LogService::registrar(
            $request->user()->id,
            'eliminar-definitivo-categoria',
            "Eliminó definitivamente la categoría «{$nombreEliminado}».",
            'categorias',
            $idEliminado
        );

        return response()->json([
            'message' => 'Categoría eliminada definitivamente.',
        ]);
    }

    // ======================= HELPERS ======================= //

    // Validación compartida por store y update //
    private function validar(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:100',
                Rule::unique('categorias', 'nombre')->ignore($id),   // nombre único //
            ],
            'descripcion' => [
                'nullable',   // coincide con la migración (nullable en tabla) //
                'string',
                'min:5',
                'max:255',
            ],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string'   => 'El campo :attribute debe ser texto.',
            'min'      => 'El campo :attribute debe tener al menos :min caracteres.',
            'max'      => 'El campo :attribute no puede tener más de :max caracteres.',
            'unique'   => 'Ya existe una categoría con ese nombre.',
        ], [
            'nombre'      => 'nombre',
            'descripcion' => 'descripción',
        ]);
    }
}
