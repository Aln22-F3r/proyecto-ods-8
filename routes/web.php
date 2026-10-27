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
use App\Http\Controllers\AdminAuthController;

Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

Route::middleware('auth:admin')->group(function () {

    Route::get('/', fn() => redirect('/dashboard'));

    Route::get('/dashboard', fn() => Inertia::render('Dashboard'));

    Route::get('/roles', [RolController::class, 'index']);
    Route::get('/roles/crear', fn() => Inertia::render('Roles/Form'));
    Route::get('/roles/eliminados', [RolController::class, 'eliminados']);
    Route::get('/roles/{id}', [RolController::class, 'show']);
    Route::get('/roles/{id}/editar', [RolController::class, 'edit']);
    Route::post('/roles', [RolController::class, 'store']);
    Route::put('/roles/{id}', [RolController::class, 'update']);
    Route::put('/roles/{id}/restaurar', [RolController::class, 'restore']);
    Route::delete('/roles/{id}', [RolController::class, 'destroy']);
    Route::delete('/roles/{id}/definitivo', [RolController::class, 'forceDelete']);


    Route::get('/usuarios', [UsuarioController::class, 'index']);
    Route::get('/usuarios/crear', [UsuarioController::class, 'create']);
    Route::get('/usuarios/eliminados', [UsuarioController::class, 'eliminados']);
    Route::get('/usuarios/{id}', [UsuarioController::class, 'show']);
    Route::get('/usuarios/{id}/editar', [UsuarioController::class, 'edit']);
    Route::post('/usuarios', [UsuarioController::class, 'store']);
    Route::put('/usuarios/{id}', [UsuarioController::class, 'update']);
    Route::put('/usuarios/{id}/restaurar', [UsuarioController::class, 'restore']);
    Route::delete('/usuarios/{id}', [UsuarioController::class, 'destroy']);
    Route::delete('/usuarios/{id}/definitivo', [UsuarioController::class, 'forceDelete']);

    Route::get('/perfiles', [PerfilController::class, 'index']);
    Route::get('/perfiles/crear', [PerfilController::class, 'create']);
    Route::get('/perfiles/eliminados', [PerfilController::class, 'eliminados']);
    Route::get('/perfiles/{id}', [PerfilController::class, 'show']);
    Route::get('/perfiles/{id}/editar', [PerfilController::class, 'edit']);
    Route::post('/perfiles', [PerfilController::class, 'store']);
    Route::put('/perfiles/{id}', [PerfilController::class, 'update']);
    Route::put('/perfiles/{id}/restaurar', [PerfilController::class, 'restore']);
    Route::delete('/perfiles/{id}', [PerfilController::class, 'destroy']);
    Route::delete('/perfiles/{id}/definitivo', [PerfilController::class, 'forceDelete']);

    Route::get('/categorias', [CategoriaController::class, 'index']);
    Route::get('/categorias/crear', fn() => Inertia::render('Categorias/Form'));
    Route::get('/categorias/eliminados', [CategoriaController::class, 'eliminados']);
    Route::get('/categorias/{id}', [CategoriaController::class, 'show']);
    Route::get('/categorias/{id}/editar', [CategoriaController::class, 'edit']);
    Route::post('/categorias', [CategoriaController::class, 'store']);
    Route::put('/categorias/{id}', [CategoriaController::class, 'update']);
    Route::put('/categorias/{id}/restaurar', [CategoriaController::class, 'restore']);
    Route::delete('/categorias/{id}', [CategoriaController::class, 'destroy']);
    Route::delete('/categorias/{id}/definitivo', [CategoriaController::class, 'forceDelete']);

    Route::get('/ofertas-empleo', [OfertaEmpleoController::class, 'index']);
    Route::get('/ofertas-empleo/crear', [OfertaEmpleoController::class, 'create']);
    Route::get('/ofertas-empleo/eliminados', [OfertaEmpleoController::class, 'eliminados']);
    Route::get('/ofertas-empleo/{id}', [OfertaEmpleoController::class, 'show']);
    Route::get('/ofertas-empleo/{id}/editar', [OfertaEmpleoController::class, 'edit']);
    Route::post('/ofertas-empleo', [OfertaEmpleoController::class, 'store']);
    Route::put('/ofertas-empleo/{id}', [OfertaEmpleoController::class, 'update']);
    Route::put('/ofertas-empleo/{id}/restaurar', [OfertaEmpleoController::class, 'restore']);
    Route::delete('/ofertas-empleo/{id}', [OfertaEmpleoController::class, 'destroy']);
    Route::delete('/ofertas-empleo/{id}/definitivo', [OfertaEmpleoController::class, 'forceDelete']);

    Route::get('/postulaciones', [PostulacionController::class, 'index']);
    Route::get('/postulaciones/crear', [PostulacionController::class, 'create']);
    Route::get('/postulaciones/eliminados', [PostulacionController::class, 'eliminados']);
    Route::get('/postulaciones/{id}', [PostulacionController::class, 'show']);
    Route::get('/postulaciones/{id}/editar', [PostulacionController::class, 'edit']);
    Route::post('/postulaciones', [PostulacionController::class, 'store']);
    Route::put('/postulaciones/{id}', [PostulacionController::class, 'update']);
    Route::put('/postulaciones/{id}/restaurar', [PostulacionController::class, 'restore']);
    Route::delete('/postulaciones/{id}', [PostulacionController::class, 'destroy']);
    Route::delete('/postulaciones/{id}/definitivo', [PostulacionController::class, 'forceDelete']);

    Route::get('/transacciones', [TransaccionController::class, 'index']);
    Route::get('/transacciones/crear', [TransaccionController::class, 'create']);
    Route::get('/transacciones/eliminados', [TransaccionController::class, 'eliminados']);
    Route::get('/transacciones/{id}', [TransaccionController::class, 'show']);
    Route::get('/transacciones/{id}/editar', [TransaccionController::class, 'edit']);
    Route::post('/transacciones', [TransaccionController::class, 'store']);
    Route::put('/transacciones/{id}', [TransaccionController::class, 'update']);
    Route::put('/transacciones/{id}/restaurar', [TransaccionController::class, 'restore']);
    Route::delete('/transacciones/{id}', [TransaccionController::class, 'destroy']);
    Route::delete('/transacciones/{id}/definitivo', [TransaccionController::class, 'forceDelete']);

    Route::get('/login-sociales', [LoginSocialController::class, 'index']);
    Route::get('/login-sociales/crear', [LoginSocialController::class, 'create']);
    Route::get('/login-sociales/eliminados', [LoginSocialController::class, 'eliminados']);
    Route::get('/login-sociales/{id}', [LoginSocialController::class, 'show']);
    Route::get('/login-sociales/{id}/editar', [LoginSocialController::class, 'edit']);
    Route::post('/login-sociales', [LoginSocialController::class, 'store']);
    Route::put('/login-sociales/{id}', [LoginSocialController::class, 'update']);
    Route::put('/login-sociales/{id}/restaurar', [LoginSocialController::class, 'restore']);
    Route::delete('/login-sociales/{id}', [LoginSocialController::class, 'destroy']);
    Route::delete('/login-sociales/{id}/definitivo', [LoginSocialController::class, 'forceDelete']);

    Route::get('/logs', [LogController::class, 'index']);
    Route::get('/logs/crear', [LogController::class, 'create']);
    Route::get('/logs/eliminados', [LogController::class, 'eliminados']);
    Route::get('/logs/{id}', [LogController::class, 'show']);
    Route::get('/logs/{id}/editar', [LogController::class, 'edit']);
    Route::post('/logs', [LogController::class, 'store']);
    Route::put('/logs/{id}', [LogController::class, 'update']);
    Route::put('/logs/{id}/restaurar', [LogController::class, 'restore']);
    Route::delete('/logs/{id}', [LogController::class, 'destroy']);
    Route::delete('/logs/{id}/definitivo', [LogController::class, 'forceDelete']);
});

require __DIR__ . '/auth.php';
