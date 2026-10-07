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
}
