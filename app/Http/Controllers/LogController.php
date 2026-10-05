<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Inertia\Inertia;

class LogController extends Controller
{
    public function index()
    {
        return Inertia::render('Logs/Index', [
            'logs' => Log::with('usuario:id,nombre,apellido')->paginate(5),
        ]);
    }
}
