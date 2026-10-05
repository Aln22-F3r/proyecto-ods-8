<?php

namespace App\Http\Controllers;

use App\Models\Postulacion;
use Inertia\Inertia;

class PostulacionController extends Controller
{
    public function index()
    {
        return Inertia::render('Postulaciones/Index', [
            'postulaciones' => Postulacion::with([
                'usuario:id,nombre,apellido',
                'ofertaEmpleo:id,titulo',
            ])->paginate(5),
        ]);
    }
}
