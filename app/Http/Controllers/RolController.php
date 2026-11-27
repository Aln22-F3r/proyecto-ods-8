<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use Illuminate\Support\Facades\Gate;

/**
 * RolController
 *
 * Catálogo de roles. Los 3 roles son inamovibles:
 *   - Candidato
 *   - Empresa
 *   - Administrador
 *
 * No hay store/update/destroy: los roles no se crean ni se borran.
 * Es solo lectura.
 *
 * Rutas:
 *   GET /admin/roles      → protegido (can:es-administrador)
 *   GET /admin/roles/{id} → protegido
 *   GET /roles/registro   → público (solo Candidato y Empresa)
 */
class RolController extends Controller
{
    /** Roles que el formulario de auto-registro puede ofrecer. */
    private const ROLES_REGISTRO = ['Candidato', 'Empresa'];

    /**
     * GET /admin/roles
     * Todos los roles (incluye Administrador). Solo para el panel admin.
     */
    public function index()
    {
        Gate::authorize('consultar');

        return response()->json([
            'data' => Rol::all(),
        ]);
    }

    /**
     * GET /admin/roles/{id}
     * Detalle de un rol.
     */
    public function show($id)
    {
        Gate::authorize('consultar');

        $rol = Rol::find($id);

        if (!$rol) {
            return response()->json([
                'message' => 'Rol no encontrado.',
            ], 404);
        }

        return response()->json([
            'data' => $rol,
        ]);
    }

    /**
     * GET /roles/registro
     * PÚBLICO. Devuelve solo los roles auto-registrables.
     * Se usa para poblar el <select> del formulario de registro.
     * Nunca devuelve "Administrador".
     */
    public function registro()
    {
        return response()->json([
            'data' => Rol::whereIn('nombre', self::ROLES_REGISTRO)
                ->get(['id', 'nombre', 'descripcion']),
        ]);
    }
}
