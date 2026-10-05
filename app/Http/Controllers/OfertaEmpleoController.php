<?php

namespace App\Http\Controllers;

use App\Models\OfertaEmpleo;
use Inertia\Inertia;

class OfertaEmpleoController extends Controller
{
    public function index()
    {
        return Inertia::render('OfertasEmpleo/Index', [
            'ofertas' => OfertaEmpleo::with('categoria:id,nombre')->paginate(5),
        ]);
    }
}
