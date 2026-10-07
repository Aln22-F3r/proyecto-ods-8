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
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
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

        try {
            Categoria::create($datos);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo guardar la categoría. Intenta de nuevo.');
        }

        return redirect('/categorias')->with('exito', 'Categoría creada correctamente.');
    }
}
