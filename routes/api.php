
<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\ListaDeseoController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\OfertaEmpleoController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PostulacionController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\PagoController;

// ==================== RUTAS PÚBLICAS (sin autenticación) ==================== //

// Login universal para los 3 roles //
Route::post('/auth/login',    [AuthController::class, 'login']);

// Auto-registro público (solo Candidato y Empresa) //
Route::post('/auth/register', [AuthController::class, 'register']);

// Roles disponibles para el formulario de registro (nunca incluye Administrador) //
Route::get('/roles/registro', [RolController::class, 'registro']);

// Catálogo público de ofertas (sin token) //
Route::get('/ofertas-publicas', [OfertaEmpleoController::class, 'publicIndex']);
Route::get('/ofertas-publicas/{id}', [OfertaEmpleoController::class, 'show']);

// ==================== RUTAS AUTENTICADAS (cualquier rol) ==================== //

Route::middleware(['auth:sanctum', 'usuario.activo'])->group(function () {

    // ==================== CUENTA ==================== //

    Route::get('/auth/me',      [AuthController::class, 'me']);       // datos del usuario autenticado //
    Route::post('/auth/logout', [AuthController::class, 'logout']);   // revoca el token actual //

    // ==================== PERFIL PROPIO ==================== //

    Route::get('/perfil', [PerfilController::class, 'miPerfil']);              // ver mi perfil //
    Route::put('/perfil', [PerfilController::class, 'actualizarMiPerfil']);    // editar mi perfil //

    // ==================== CATÁLOGO COMPARTIDO ==================== //

    Route::get('/categorias/listado', [CategoriaController::class, 'listado']);   // categorías activas para dropdowns //

    // ==================== NOTIFICACIONES (bandeja personal) ==================== //

    // Rutas específicas ANTES de /{id} para que no se confundan con IDs //
    Route::get('/notificaciones/eliminados',         [NotificacionController::class, 'eliminados']);
    Route::patch('/notificaciones/leer-todas',       [NotificacionController::class, 'marcarTodasLeidas']);
    Route::get('/notificaciones',                    [NotificacionController::class, 'index']);          // mi bandeja //
    Route::get('/notificaciones/{id}',               [NotificacionController::class, 'show']);           // ver una //
    Route::patch('/notificaciones/{id}/leer',        [NotificacionController::class, 'marcarLeida']);    // marcar leída //
    Route::patch('/notificaciones/{id}/restaurar',   [NotificacionController::class, 'restore']);         // restaurar //
    Route::delete('/notificaciones/{id}',            [NotificacionController::class, 'destroy']);        // soft delete //
    Route::delete('/notificaciones/{id}/definitivo', [NotificacionController::class, 'forceDelete']);    // borrado físico //
});

// ==================== MÓDULO ADMIN (Administrador niveles 1-3) ==================== //
// El Gate 'es-administrador' protege TODO el bloque. //
// Dentro, cada acción autoriza por nivel con Gate::authorize() o Policy. //

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'usuario.activo', 'can:es-administrador'])
    ->group(function () {

        // ==================== ROLES ==================== //

        Route::get('/roles',      [RolController::class, 'index']);   // listar los 3 roles //
        Route::get('/roles/{id}', [RolController::class, 'show']);    // detalle de un rol //

        // ==================== USUARIOS ==================== //

        // Lista específica ANTES de /{id} //
        Route::get('/usuarios/eliminados',         [UsuarioController::class, 'eliminados']);
        Route::get('/usuarios',                    [UsuarioController::class, 'index']);
        Route::post('/usuarios',                   [UsuarioController::class, 'store']);
        Route::get('/usuarios/{id}',               [UsuarioController::class, 'show']);
        Route::put('/usuarios/{id}',               [UsuarioController::class, 'update']);
        Route::delete('/usuarios/{id}',            [UsuarioController::class, 'destroy']);
        Route::patch('/usuarios/{id}/restaurar',   [UsuarioController::class, 'restore']);
        Route::delete('/usuarios/{id}/definitivo', [UsuarioController::class, 'forceDelete']);

        // ==================== PERFILES ==================== //

        // Sin destroy/restore/forceDelete: el perfil muere con el usuario (cascade + observer) //
        Route::get('/perfiles/eliminados', [PerfilController::class, 'eliminados']);   // perfiles de usuarios borrados //
        Route::get('/perfiles',            [PerfilController::class, 'index']);
        Route::get('/perfiles/{id}',       [PerfilController::class, 'show']);
        Route::put('/perfiles/{id}',       [PerfilController::class, 'update']);

        // ==================== CATEGORÍAS ==================== //

        Route::get('/categorias/eliminados',         [CategoriaController::class, 'eliminados']);
        Route::get('/categorias',                    [CategoriaController::class, 'index']);
        Route::post('/categorias',                   [CategoriaController::class, 'store']);
        Route::get('/categorias/{id}',               [CategoriaController::class, 'show']);
        Route::put('/categorias/{id}',               [CategoriaController::class, 'update']);
        Route::delete('/categorias/{id}',            [CategoriaController::class, 'destroy']);
        Route::patch('/categorias/{id}/restaurar',   [CategoriaController::class, 'restore']);
        Route::delete('/categorias/{id}/definitivo', [CategoriaController::class, 'forceDelete']);

        // ==================== OFERTAS ==================== //

        Route::get('/ofertas/eliminados',         [OfertaEmpleoController::class, 'eliminados']);
        Route::get('/ofertas',                    [OfertaEmpleoController::class, 'index']);
        Route::post('/ofertas',                   [OfertaEmpleoController::class, 'store']);
        Route::get('/ofertas/{id}',               [OfertaEmpleoController::class, 'show']);
        Route::put('/ofertas/{id}',               [OfertaEmpleoController::class, 'update']);
        Route::delete('/ofertas/{id}',            [OfertaEmpleoController::class, 'destroy']);
        Route::patch('/ofertas/{id}/restaurar',   [OfertaEmpleoController::class, 'restore']);
        Route::delete('/ofertas/{id}/definitivo', [OfertaEmpleoController::class, 'forceDelete']);

        // ==================== POSTULACIONES ==================== //

        Route::get('/postulaciones/eliminados',         [PostulacionController::class, 'eliminados']);
        Route::get('/postulaciones',                    [PostulacionController::class, 'index']);
        Route::post('/postulaciones',                   [PostulacionController::class, 'store']);
        Route::get('/postulaciones/{id}',               [PostulacionController::class, 'show']);
        Route::put('/postulaciones/{id}',               [PostulacionController::class, 'update']);
        Route::delete('/postulaciones/{id}',            [PostulacionController::class, 'destroy']);
        Route::patch('/postulaciones/{id}/restaurar',   [PostulacionController::class, 'restore']);
        Route::delete('/postulaciones/{id}/definitivo', [PostulacionController::class, 'forceDelete']);

        // ==================== LISTA DE DESEOS ==================== //

        // Sin restore/forceDelete: la tabla no tiene SoftDeletes (borrado físico directo) //
        Route::get('/lista-deseos',         [ListaDeseoController::class, 'index']);
        Route::post('/lista-deseos',        [ListaDeseoController::class, 'store']);     // agregar para un candidato //
        Route::get('/lista-deseos/{id}',    [ListaDeseoController::class, 'show']);
        Route::delete('/lista-deseos/{id}', [ListaDeseoController::class, 'destroy']);   // solo nivel 3 //

        // ==================== NOTIFICACIONES (admin) ==================== //

        Route::get('/notificaciones/eliminados',         [NotificacionController::class, 'adminEliminados']);
        Route::get('/notificaciones',                    [NotificacionController::class, 'adminIndex']);
        Route::post('/notificaciones',                   [NotificacionController::class, 'adminStore']);
        Route::get('/notificaciones/{id}',               [NotificacionController::class, 'adminShow']);
        Route::delete('/notificaciones/{id}',            [NotificacionController::class, 'adminDestroy']);
        Route::patch('/notificaciones/{id}/restaurar',   [NotificacionController::class, 'adminRestore']);
        Route::delete('/notificaciones/{id}/definitivo', [NotificacionController::class, 'adminForceDelete']);

        // ==================== LOGS (auditoría, solo lectura) ==================== //

        Route::get('/logs/eliminados',         [LogController::class, 'eliminados']);
        Route::get('/logs',                    [LogController::class, 'index']);        // con filtros ?usuario_id, ?accion, ?tabla_afectada, ?registro_id, ?desde, ?hasta //
        Route::get('/logs/{id}',               [LogController::class, 'show']);
        Route::delete('/logs/{id}',            [LogController::class, 'destroy']);      // soft delete (nivel 3) //
        Route::patch('/logs/{id}/restaurar',   [LogController::class, 'restore']);      // (nivel 3) //
        Route::delete('/logs/{id}/definitivo', [LogController::class, 'forceDelete']);  // (nivel 3) //

        // ==================== PAGOS (auditoría) ==================== //

        Route::get('/pagos',      [PagoController::class, 'index']);   // todos los pagos con filtros //
        Route::get('/pagos/{id}', [PagoController::class, 'show']);    // detalle de un pago //
    });

// ==================== MÓDULO EMPRESA (rol Empresa) ==================== //
// La Policy verifica que cada recurso le pertenezca (usuario_id = actor->id). //

Route::prefix('empresa')
    ->middleware(['auth:sanctum', 'usuario.activo', 'can:es-empresa'])
    ->group(function () {

        // ==================== OFERTAS (solo las propias) ==================== //

        Route::get('/ofertas',         [OfertaEmpleoController::class, 'index']);   // solo sus ofertas //
        Route::post('/ofertas',        [OfertaEmpleoController::class, 'store']);   // autoasignándose //
        Route::get('/ofertas/{id}',    [OfertaEmpleoController::class, 'show']);    // solo si es suya //
        Route::put('/ofertas/{id}',    [OfertaEmpleoController::class, 'update']);  // solo si es suya //
        Route::delete('/ofertas/{id}', [OfertaEmpleoController::class, 'destroy']); // solo si es suya //

        // ==================== POSTULACIONES RECIBIDAS (solo sus ofertas) ==================== //

        Route::get('/postulaciones',      [PostulacionController::class, 'index']);   // solo las de sus ofertas //
        Route::get('/postulaciones/{id}', [PostulacionController::class, 'show']);    // solo si es de su oferta //
        Route::put('/postulaciones/{id}', [PostulacionController::class, 'update']);  // cambiar estado del flujo //

        // ==================== PAGOS (destacar oferta) ==================== //

        Route::post('/ofertas/{id}/destacar', [PagoController::class, 'destacar']);   // inicia pago de destacado //
        Route::get('/pagos',                  [PagoController::class, 'index']);      // mis pagos //
        Route::get('/pagos/{id}',             [PagoController::class, 'show']);       // detalle //
        Route::post('/pagos/{id}/confirmar',  [PagoController::class, 'confirmar']);  // confirma tras Stripe.js //
    });

// ==================== MÓDULO CANDIDATO (rol Candidato) ==================== //
// El candidato lee ofertas abiertas y gestiona sus postulaciones y lista de deseos. //

Route::prefix('candidato')
    ->middleware(['auth:sanctum', 'usuario.activo', 'can:es-candidato'])
    ->group(function () {

        // ==================== OFERTAS (solo lectura de abiertas) ==================== //

        Route::get('/ofertas',      [OfertaEmpleoController::class, 'index']);  // solo abiertas (filtro en controlador) //
        Route::get('/ofertas/{id}', [OfertaEmpleoController::class, 'show']);   // solo si está abierta //

        // ==================== MIS POSTULACIONES ==================== //

        Route::get('/postulaciones',         [PostulacionController::class, 'index']);     // solo las mías //
        Route::post('/postulaciones',        [PostulacionController::class, 'store']);     // postularme //
        Route::get('/postulaciones/{id}',    [PostulacionController::class, 'show']);      // solo la mía //
        Route::put('/postulaciones/{id}',    [PostulacionController::class, 'update']);    // solo para marcar Retirada //
        Route::delete('/postulaciones/{id}', [PostulacionController::class, 'destroy']);   // retirar (soft delete) //

        // ==================== MI LISTA DE DESEOS ==================== //

        Route::get('/lista-deseos',         [ListaDeseoController::class, 'index']);     // solo los míos //
        Route::post('/lista-deseos',        [ListaDeseoController::class, 'store']);     // agregar oferta abierta //
        Route::get('/lista-deseos/{id}',    [ListaDeseoController::class, 'show']);      // solo si es mío //
        Route::delete('/lista-deseos/{id}', [ListaDeseoController::class, 'destroy']);   // quitar de mi lista //
    });
