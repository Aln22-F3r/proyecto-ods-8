<?php

namespace App\Http\Controllers;

use App\Models\LoginSocial;
use Inertia\Inertia;

class LoginSocialController extends Controller
{
    public function index()
    {
        return Inertia::render('LoginSociales/Index', [
            'loginSociales' => LoginSocial::with('usuario:id,nombre,apellido')->paginate(5),
        ]);
    }
}
