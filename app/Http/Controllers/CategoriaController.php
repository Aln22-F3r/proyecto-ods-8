<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoriaController extends Controller
{
    public function index()
    {
        return Inertia::render('Categorias/Index', [
            'categorias' => Categoria::paginate(5),
            'eliminados' => false,
        ]);
    }

    public function eliminados()
    {
        return Inertia::render('Categorias/Index', [
            'categorias' => Categoria::onlyTrashed()->paginate(5),
            'eliminados' => true,
        ]);
    }

    public function show($id)
    {
        $categoria = Categoria::withTrashed()->withCount('ofertasEmpleo')->find($id);

        if (!$categoria) {
            return redirect('/categorias')->with('error', 'La categoría solicitada no existe.');
        }

        $zona = 'America/Mexico_City';

        return Inertia::render('Categorias/Show', [
            'categoria' => [
                'id' => $categoria->id,
                'nombre' => $categoria->nombre,
                'descripcion' => $categoria->descripcion,
                'ofertas_count' => $categoria->ofertas_empleo_count,
                'creado' => $categoria->created_at?->timezone($zona)->format('d/m/Y H:i'),
                'actualizado' => $categoria->updated_at?->timezone($zona)->format('d/m/Y H:i'),
                'eliminado' => $categoria->trashed(),
                'fecha_eliminacion' => $categoria->deleted_at?->timezone($zona)->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        try {
            Categoria::create($datos);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo guardar la categoría. Intenta de nuevo.');
        }

        return redirect('/categorias')->with('exito', 'Categoría creada correctamente.');
    }

    public function edit($id)
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return redirect('/categorias')->with('error', 'La categoría solicitada no existe. No se puede editar.');
        }

        return Inertia::render('Categorias/Form', [
            'categoria' => $categoria,
        ]);
    }

    public function update(Request $request, $id)
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return redirect('/categorias')->with('error', 'La categoría solicitada no existe. No se puede actualizar.');
        }

        $datos = $this->validar($request);

        $categoria->fill($datos);

        if (!$categoria->isDirty()) {
            return redirect('/categorias')->with('advertencia', 'No se realizaron cambios en la categoría.');
        }

        try {
            $categoria->save();
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo actualizar la categoría. Intenta de nuevo.');
        }

        return redirect('/categorias')->with('exito', 'Categoría actualizada correctamente.');
    }

    // Borrado lógico: solo llena deleted_at, el registro se conserva //
    public function destroy($id)
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return redirect('/categorias')->with('error', 'La categoría solicitada no existe o ya fue eliminada.');
        }

        try {
            $categoria->delete();
        } catch (\Throwable $e) {
            return redirect('/categorias')->with('error', 'No se pudo eliminar la categoría. Intenta de nuevo.');
        }

        return redirect('/categorias')->with('exito', "La categoría «{$categoria->nombre}» se eliminó. Puedes restaurarla desde la lista de eliminadas.");
    }

    public function restore($id)
    {
        $categoria = Categoria::onlyTrashed()->find($id);

        if (!$categoria) {
            return redirect('/categorias/eliminados')->with('error', 'La categoría solicitada no existe o no está eliminada.');
        }

        try {
            $categoria->restore();
        } catch (\Throwable $e) {
            return redirect('/categorias/eliminados')->with('error', 'No se pudo restaurar la categoría. Intenta de nuevo.');
        }

        return redirect('/categorias/eliminados')->with('exito', "La categoría «{$categoria->nombre}» se restauró y ya aparece en la lista principal.");
    }

    // Borrado físico: solo para registros ya eliminados y sin dependencias //
    public function forceDelete($id)
    {
        $categoria = Categoria::withTrashed()->find($id);

        if (!$categoria) {
            return redirect('/categorias/eliminados')->with('error', 'La categoría solicitada no existe.');
        }

        if (!$categoria->trashed()) {
            return redirect('/categorias')->with('advertencia', 'La categoría debe eliminarse primero (borrado lógico) antes de poder borrarla definitivamente.');
        }

        // Cuenta también las ofertas eliminadas lógicamente, porque siguen usando la categoría en la base de datos //
        $total = $categoria->ofertasEmpleo()->withTrashed()->count();

        if ($total > 0) {
            $ofertas = $total === 1 ? '1 oferta de empleo' : "{$total} ofertas de empleo";

            return redirect('/categorias/eliminados')->with('error', "No se puede eliminar definitivamente la categoría «{$categoria->nombre}»: está siendo usada por {$ofertas} (activas o eliminadas). Reasigna o elimina primero esas ofertas.");
        }

        try {
            $categoria->forceDelete();
        } catch (\Throwable $e) {
            return redirect('/categorias/eliminados')->with('error', 'No se pudo eliminar definitivamente la categoría. Intenta de nuevo.');
        }

        return redirect('/categorias/eliminados')->with('exito', "La categoría «{$categoria->nombre}» se eliminó definitivamente.");
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
