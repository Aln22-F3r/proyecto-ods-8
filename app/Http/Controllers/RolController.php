<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RolController extends Controller
{
    public function index()
    {
        return Inertia::render('Roles/Index', [
            'roles' => Rol::paginate(5),
            'eliminados' => false,
        ]);
    }

    public function eliminados()
    {
        return Inertia::render('Roles/Index', [
            'roles' => Rol::onlyTrashed()->paginate(5),
            'eliminados' => true,
        ]);
    }

    public function show($id)
    {
        $rol = Rol::withTrashed()->withCount('usuarios')->find($id);

        if (!$rol) {
            return redirect('/roles')->with('error', 'El rol solicitado no existe.');
        }

        $zona = 'America/Mexico_City';

        return Inertia::render('Roles/Show', [
            'rol' => [
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
            Rol::create($datos);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo guardar el rol. Intenta de nuevo.');
        }

        return redirect('/roles')->with('exito', 'Rol creado correctamente.');
    }

    public function edit($id)
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return redirect('/roles')->with('error', 'El rol solicitado no existe. No se puede editar.');
        }

        return Inertia::render('Roles/Form', [
            'rol' => $rol,
        ]);
    }

    public function update(Request $request, $id)
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return redirect('/roles')->with('error', 'El rol solicitado no existe. No se puede actualizar.');
        }

        $datos = $this->validar($request);

        $rol->fill($datos);

        if (!$rol->isDirty()) {
            return redirect('/roles')->with('advertencia', 'No se realizaron cambios en el rol.');
        }

        try {
            $rol->save();
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo actualizar el rol. Intenta de nuevo.');
        }

        return redirect('/roles')->with('exito', 'Rol actualizado correctamente.');
    }

    // Borrado lógico: solo llena deleted_at, el registro se conserva //
    public function destroy($id)
    {
        $rol = Rol::find($id);

        if (!$rol) {
            return redirect('/roles')->with('error', 'El rol solicitado no existe o ya fue eliminado.');
        }

        try {
            $rol->delete();
        } catch (\Throwable $e) {
            return redirect('/roles')->with('error', 'No se pudo eliminar el rol. Intenta de nuevo.');
        }

        return redirect('/roles')->with('exito', "El rol «{$rol->nombre}» se eliminó. Puedes restaurarlo desde la lista de eliminados.");
    }

    public function restore($id)
    {
        $rol = Rol::onlyTrashed()->find($id);

        if (!$rol) {
            return redirect('/roles/eliminados')->with('error', 'El rol solicitado no existe o no está eliminado.');
        }

        try {
            $rol->restore();
        } catch (\Throwable $e) {
            return redirect('/roles/eliminados')->with('error', 'No se pudo restaurar el rol. Intenta de nuevo.');
        }

        return redirect('/roles/eliminados')->with('exito', "El rol «{$rol->nombre}» se restauró y ya aparece en la lista principal.");
    }

    // Borrado físico: solo para registros ya eliminados y sin dependencias //
    public function forceDelete($id)
    {
        $rol = Rol::withTrashed()->find($id);

        if (!$rol) {
            return redirect('/roles/eliminados')->with('error', 'El rol solicitado no existe.');
        }

        if (!$rol->trashed()) {
            return redirect('/roles')->with('advertencia', 'El rol debe eliminarse primero (borrado lógico) antes de poder borrarlo definitivamente.');
        }

        // Cuenta también los usuarios eliminados lógicamente, porque siguen usando el rol en la base de datos //
        $total = $rol->usuarios()->withTrashed()->count();

        if ($total > 0) {
            $usuarios = $total === 1 ? '1 usuario' : "{$total} usuarios";

            return redirect('/roles/eliminados')->with('error', "No se puede eliminar definitivamente el rol «{$rol->nombre}»: está siendo usado por {$usuarios} (activos o eliminados). Reasigna o elimina primero esos usuarios.");
        }

        try {
            $rol->forceDelete();
        } catch (\Throwable $e) {
            return redirect('/roles/eliminados')->with('error', 'No se pudo eliminar definitivamente el rol. Intenta de nuevo.');
        }

        return redirect('/roles/eliminados')->with('exito', "El rol «{$rol->nombre}» se eliminó definitivamente.");
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
