<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn() => redirect('/dashboard'));

Route::get('/dashboard', fn() => Inertia::render('Dashboard'));

Route::get('/roles', fn() => Inertia::render('Roles/Index'));
Route::get('/roles/crear', fn() => Inertia::render('Roles/Form'));
Route::get('/roles/{id}', fn() => Inertia::render('Roles/Form'));
Route::get('/roles/{id}/editar', fn() => Inertia::render('Roles/Form'));

Route::get('/usuarios', fn() => Inertia::render('Usuarios/Index'));
Route::get('/usuarios/crear', fn() => Inertia::render('Usuarios/Form'));
Route::get('/usuarios/{id}', fn() => Inertia::render('Usuarios/Form'));
Route::get('/usuarios/{id}/editar', fn() => Inertia::render('Usuarios/Form'));

Route::get('/perfiles', fn() => Inertia::render('Perfiles/Index'));
Route::get('/perfiles/crear', fn() => Inertia::render('Perfiles/Form'));
Route::get('/perfiles/{id}', fn() => Inertia::render('Perfiles/Form'));
Route::get('/perfiles/{id}/editar', fn() => Inertia::render('Perfiles/Form'));

Route::get('/categorias', fn() => Inertia::render('Categorias/Index'));
Route::get('/categorias/crear', fn() => Inertia::render('Categorias/Form'));
Route::get('/categorias/{id}', fn() => Inertia::render('Categorias/Form'));
Route::get('/categorias/{id}/editar', fn() => Inertia::render('Categorias/Form'));

Route::get('/ofertas-empleo', fn() => Inertia::render('OfertasEmpleo/Index'));
Route::get('/ofertas-empleo/crear', fn() => Inertia::render('OfertasEmpleo/Form'));
Route::get('/ofertas-empleo/{id}', fn() => Inertia::render('OfertasEmpleo/Form'));
Route::get('/ofertas-empleo/{id}/editar', fn() => Inertia::render('OfertasEmpleo/Form'));

Route::get('/postulaciones', fn() => Inertia::render('Postulaciones/Index'));
Route::get('/postulaciones/crear', fn() => Inertia::render('Postulaciones/Form'));
Route::get('/postulaciones/{id}', fn() => Inertia::render('Postulaciones/Form'));
Route::get('/postulaciones/{id}/editar', fn() => Inertia::render('Postulaciones/Form'));

Route::get('/transacciones', fn() => Inertia::render('Transacciones/Index'));
Route::get('/transacciones/crear', fn() => Inertia::render('Transacciones/Form'));
Route::get('/transacciones/{id}', fn() => Inertia::render('Transacciones/Form'));
Route::get('/transacciones/{id}/editar', fn() => Inertia::render('Transacciones/Form'));

Route::get('/login-sociales', fn() => Inertia::render('LoginSociales/Index'));
Route::get('/login-sociales/crear', fn() => Inertia::render('LoginSociales/Form'));
Route::get('/login-sociales/{id}', fn() => Inertia::render('LoginSociales/Form'));
Route::get('/login-sociales/{id}/editar', fn() => Inertia::render('LoginSociales/Form'));

Route::get('/logs', fn() => Inertia::render('Logs/Index'));
Route::get('/logs/crear', fn() => Inertia::render('Logs/Form'));
Route::get('/logs/{id}', fn() => Inertia::render('Logs/Form'));
Route::get('/logs/{id}/editar', fn() => Inertia::render('Logs/Form'));

require __DIR__ . '/auth.php';
