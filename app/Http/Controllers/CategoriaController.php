<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Inertia\Inertia;

class CategoriaController extends Controller
{
    public function index()
    {
        return Inertia::render('Categorias/Index', [
            'categorias' => Categoria::paginate(5),
        ]);
    }
}
