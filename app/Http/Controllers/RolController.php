<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Inertia\Inertia;

class RolController extends Controller
{
    public function index()
    {
        return Inertia::render('Roles/Index', [
            'roles' => Rol::paginate(5),
        ]);
    }
}
