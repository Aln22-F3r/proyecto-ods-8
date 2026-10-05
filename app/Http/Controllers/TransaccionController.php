<?php

namespace App\Http\Controllers;

use App\Models\Transaccion;
use Inertia\Inertia;

class TransaccionController extends Controller
{
    public function index()
    {
        return Inertia::render('Transacciones/Index', [
            'transacciones' => Transaccion::with('usuario:id,nombre,apellido')->paginate(5),
        ]);
    }
}
