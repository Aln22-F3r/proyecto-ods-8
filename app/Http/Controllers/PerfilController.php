<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use Inertia\Inertia;

class PerfilController extends Controller
{
    public function index()
    {
        return Inertia::render('Perfiles/Index', [
            'perfiles' => Perfil::with('usuario:id,nombre,apellido')->paginate(5),
        ]);
    }
}
