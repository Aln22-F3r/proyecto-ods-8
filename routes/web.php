<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\LoginSocialController;
use App\Http\Controllers\OfertaEmpleoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PostulacionController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\TransaccionController;
use App\Http\Controllers\UsuarioController;

Route::get('/', fn() => redirect('/dashboard'));

Route::get('/dashboard', fn() => Inertia::render('Dashboard'));

Route::get('/roles', [RolController::class, 'index']);
Route::get('/roles/crear', fn() => Inertia::render('Roles/Form'));
Route::get('/roles/{id}', fn() => Inertia::render('Roles/Form'));
Route::get('/roles/{id}/editar', [RolController::class, 'edit']);
Route::put('/roles/{id}', [RolController::class, 'update']);
Route::post('/roles', [RolController::class, 'store']);

Route::get('/usuarios', [UsuarioController::class, 'index']);
Route::get('/usuarios/crear', [UsuarioController::class, 'create']);
Route::get('/usuarios/{id}', fn() => Inertia::render('Usuarios/Form'));
Route::get('/usuarios/{id}/editar', [UsuarioController::class, 'edit']);
Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);
Route::post('/usuarios', [UsuarioController::class, 'store']);

Route::get('/perfiles', [PerfilController::class, 'index']);
Route::get('/perfiles/crear', [PerfilController::class, 'create']);
Route::get('/perfiles/{id}', fn() => Inertia::render('Perfiles/Form'));
Route::get('/perfiles/{id}/editar', [PerfilController::class, 'edit']);
Route::put('/perfiles/{id}', [PerfilController::class, 'update']);
Route::post('/perfiles', [PerfilController::class, 'store']);

Route::get('/categorias', [CategoriaController::class, 'index']);
Route::get('/categorias/crear', fn() => Inertia::render('Categorias/Form'));
Route::get('/categorias/{id}', fn() => Inertia::render('Categorias/Form'));
Route::get('/categorias/{id}/editar', [CategoriaController::class, 'edit']);
Route::put('/categorias/{id}', [CategoriaController::class, 'update']);
Route::post('/categorias', [CategoriaController::class, 'store']);

Route::get('/ofertas-empleo', [OfertaEmpleoController::class, 'index']);
Route::get('/ofertas-empleo/crear', [OfertaEmpleoController::class, 'create']);
Route::get('/ofertas-empleo/{id}', fn() => Inertia::render('OfertasEmpleo/Form'));
Route::get('/ofertas-empleo/{id}/editar', [OfertaEmpleoController::class, 'edit']);
Route::put('/ofertas-empleo/{id}', [OfertaEmpleoController::class, 'update']);
Route::post('/ofertas-empleo', [OfertaEmpleoController::class, 'store']);

Route::get('/postulaciones', [PostulacionController::class, 'index']);
Route::get('/postulaciones/crear', [PostulacionController::class, 'create']);
Route::get('/postulaciones/{id}', fn() => Inertia::render('Postulaciones/Form'));
Route::get('/postulaciones/{id}/editar', [PostulacionController::class, 'edit']);
Route::put('/postulaciones/{id}', [PostulacionController::class, 'update']);
Route::post('/postulaciones', [PostulacionController::class, 'store']);

Route::get('/transacciones', [TransaccionController::class, 'index']);
Route::get('/transacciones/crear', [TransaccionController::class, 'create']);
Route::get('/transacciones/{id}', fn() => Inertia::render('Transacciones/Form'));
Route::get('/transacciones/{id}/editar', [TransaccionController::class, 'edit']);
Route::put('/transacciones/{id}', [TransaccionController::class, 'update']);
Route::post('/transacciones', [TransaccionController::class, 'store']);

Route::get('/login-sociales', [LoginSocialController::class, 'index']);
Route::get('/login-sociales/crear', [LoginSocialController::class, 'create']);
Route::get('/login-sociales/{id}', fn() => Inertia::render('LoginSociales/Form'));
Route::get('/login-sociales/{id}/editar', [LoginSocialController::class, 'edit']);
Route::put('/login-sociales/{id}', [LoginSocialController::class, 'update']);
Route::post('/login-sociales', [LoginSocialController::class, 'store']);

Route::get('/logs', [LogController::class, 'index']);
Route::get('/logs/crear', [LogController::class, 'create']);
Route::get('/logs/{id}', fn() => Inertia::render('Logs/Form'));
Route::get('/logs/{id}/editar', [LogController::class, 'edit']);
Route::put('/logs/{id}', [LogController::class, 'update']);
Route::post('/logs', [LogController::class, 'store']);

require __DIR__ . '/auth.php';
