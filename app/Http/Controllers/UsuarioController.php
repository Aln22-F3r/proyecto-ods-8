<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Inertia\Inertia;

class UsuarioController extends Controller
{
    public function index()
    {
        return Inertia::render('Usuarios/Index', [
            'usuarios' => Usuario::with('rol:id,nombre')->paginate(5),
        ]);
    }
}
