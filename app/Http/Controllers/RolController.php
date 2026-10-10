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
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
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
