
<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\LoginSocialController;
use App\Http\Controllers\OfertaEmpleoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PostulacionController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\TransaccionController;
use App\Http\Controllers\UsuarioController;

// Roles
Route::get('/roles/eliminados', [RolController::class, 'eliminados']);
Route::put('/roles/{id}/restaurar', [RolController::class, 'restore']);
Route::delete('/roles/{id}/definitivo', [RolController::class, 'forceDelete']);
Route::apiResource('roles', RolController::class);

// Usuarios
Route::get('/usuarios/eliminados', [UsuarioController::class, 'eliminados']);
Route::put('/usuarios/{id}/restaurar', [UsuarioController::class, 'restore']);
Route::delete('/usuarios/{id}/definitivo', [UsuarioController::class, 'forceDelete']);
Route::apiResource('usuarios', UsuarioController::class);

// Perfiles
Route::get('/perfiles/eliminados', [PerfilController::class, 'eliminados']);
Route::put('/perfiles/{id}/restaurar', [PerfilController::class, 'restore']);
Route::delete('/perfiles/{id}/definitivo', [PerfilController::class, 'forceDelete']);
Route::apiResource('perfiles', PerfilController::class);

// Categorías
Route::get('/categorias/eliminados', [CategoriaController::class, 'eliminados']);
Route::put('/categorias/{id}/restaurar', [CategoriaController::class, 'restore']);
Route::delete('/categorias/{id}/definitivo', [CategoriaController::class, 'forceDelete']);
Route::apiResource('categorias', CategoriaController::class);

// Ofertas de empleo
Route::get('/ofertas-empleo/eliminados', [OfertaEmpleoController::class, 'eliminados']);
Route::put('/ofertas-empleo/{id}/restaurar', [OfertaEmpleoController::class, 'restore']);
Route::delete('/ofertas-empleo/{id}/definitivo', [OfertaEmpleoController::class, 'forceDelete']);
Route::apiResource('ofertas-empleo', OfertaEmpleoController::class);

// Postulaciones
Route::get('/postulaciones/eliminados', [PostulacionController::class, 'eliminados']);
Route::put('/postulaciones/{id}/restaurar', [PostulacionController::class, 'restore']);
Route::delete('/postulaciones/{id}/definitivo', [PostulacionController::class, 'forceDelete']);
Route::apiResource('postulaciones', PostulacionController::class);

// Transacciones
Route::get('/transacciones/eliminados', [TransaccionController::class, 'eliminados']);
Route::put('/transacciones/{id}/restaurar', [TransaccionController::class, 'restore']);
Route::delete('/transacciones/{id}/definitivo', [TransaccionController::class, 'forceDelete']);
Route::apiResource('transacciones', TransaccionController::class);

// Login sociales
Route::get('/login-sociales/eliminados', [LoginSocialController::class, 'eliminados']);
Route::put('/login-sociales/{id}/restaurar', [LoginSocialController::class, 'restore']);
Route::delete('/login-sociales/{id}/definitivo', [LoginSocialController::class, 'forceDelete']);
Route::apiResource('login-sociales', LoginSocialController::class);

// Logs
Route::get('/logs/eliminados', [LogController::class, 'eliminados']);
Route::put('/logs/{id}/restaurar', [LogController::class, 'restore']);
Route::delete('/logs/{id}/definitivo', [LogController::class, 'forceDelete']);
Route::apiResource('logs', LogController::class);
