<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManejaErroresBD;
use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class NotificacionController extends Controller
{
    use ManejaErroresBD;

    private const ZONA = 'America/Mexico_City';   // zona horaria para formatear fechas //

    // =========================================================================
    // BANDEJA PERSONAL (cualquier rol autenticado)
    // =========================================================================

    // GET /notificaciones: bandeja del usuario con filtro opcional de lectura //
    public function index(Request $request)
    {
        $query = $request->user()
            ->notificaciones()
            ->orderByDesc('fecha');

        // Filtro opcional: ?leida=true | ?leida=false //
        if ($request->filled('leida')) {
            $query->where('leida', filter_var($request->leida, FILTER_VALIDATE_BOOLEAN));
        }

        $noLeidas = $request->user()->notificaciones()->where('leida', false)->count();

        return response()->json([
            'no_leidas' => $noLeidas,   // conteo para badge del frontend //
            'data'      => $query->paginate(15),
        ]);
    }

    // GET /notificaciones/{id}: ver una notificación propia //
    public function show(Request $request, $id)
    {
        $notificacion = $request->user()
            ->notificaciones()
            ->withTrashed()
            ->find($id);

        if (!$notificacion) {
            return response()->json([
                'message' => 'La notificación solicitada no existe.',
            ], 404);
        }

        return response()->json([
            'data' => $this->formatear($notificacion),
        ]);
    }

    // PATCH /notificaciones/{id}/leer: marcar una como leída //
    public function marcarLeida(Request $request, $id)
    {
        $notificacion = $request->user()
            ->notificaciones()
            ->find($id);

        if (!$notificacion) {
            return response()->json([
                'message' => 'La notificación solicitada no existe.',
            ], 404);
        }

        // Idempotente: si ya estaba leída, no falla //
        if ($notificacion->leida) {
            return response()->json([
                'message' => 'La notificación ya estaba marcada como leída.',
                'data'    => $this->formatear($notificacion),
            ]);
        }

        try {
            $notificacion->update(['leida' => true]);
        } catch (QueryException $e) {
            return $this->errorBD($e, 'marcar la notificación como leída');
        }

        return response()->json([
            'message' => 'Notificación marcada como leída.',
            'data'    => $this->formatear($notificacion->fresh()),
        ]);
    }

    // PATCH /notificaciones/leer-todas: marcar todas las no leídas //
    public function marcarTodasLeidas(Request $request)
    {
        try {
            $cantidad = $request->user()
                ->notificaciones()
                ->where('leida', false)
                ->update(['leida' => true]);
        } catch (QueryException $e) {
            return $this->errorBD($e, 'marcar las notificaciones como leídas');
        }

        return response()->json([
            'message'      => 'Todas las notificaciones se marcaron como leídas.',
            'actualizadas' => $cantidad,
        ]);
    }

    // DELETE /notificaciones/{id}: soft delete de una propia //
    public function destroy(Request $request, $id)
    {
        $notificacion = $request->user()
            ->notificaciones()
            ->find($id);

        if (!$notificacion) {
            return response()->json([
                'message' => 'La notificación solicitada no existe.',
            ], 404);
        }

        try {
            $notificacion->delete();
        } catch (QueryException $e) {
            return $this->errorBD($e, 'eliminar la notificación');
        }

        return response()->json([
            'message' => 'Notificación eliminada. Puedes restaurarla.',
        ]);
    }

    // GET /notificaciones/eliminados: papelera del usuario //
    public function eliminados(Request $request)
    {
        return response()->json([
            'data' => $request->user()
                ->notificaciones()
                ->onlyTrashed()
                ->orderByDesc('fecha')
                ->paginate(15),
        ]);
    }

    // PATCH /notificaciones/{id}/restaurar: recuperar una eliminada //
    public function restore(Request $request, $id)
    {
        $notificacion = $request->user()
            ->notificaciones()
            ->onlyTrashed()
            ->find($id);

        if (!$notificacion) {
            return response()->json([
                'message' => 'La notificación no existe o no está eliminada.',
            ], 404);
        }

        try {
            $notificacion->restore();
        } catch (QueryException $e) {
            return $this->errorBD($e, 'restaurar la notificación');
        }

        return response()->json([
            'message' => 'Notificación restaurada correctamente.',
            'data'    => $this->formatear($notificacion->fresh()),
        ]);
    }

    // DELETE /notificaciones/{id}/definitivo: borrado físico de una propia //
    public function forceDelete(Request $request, $id)
    {
        $notificacion = $request->user()
            ->notificaciones()
            ->withTrashed()
            ->find($id);

        if (!$notificacion) {
            return response()->json([
                'message' => 'La notificación solicitada no existe.',
            ], 404);
        }

        if (!$notificacion->trashed()) {
            return response()->json([
                'message' => 'La notificación debe eliminarse lógicamente antes de borrarla definitivamente.',
            ], 409);
        }

        try {
            $notificacion->forceDelete();
        } catch (QueryException $e) {
            return $this->errorBD($e, 'eliminar definitivamente la notificación');
        }

        return response()->json([
            'message' => 'Notificación eliminada definitivamente.',
        ]);
    }

    // =========================================================================
    // ADMIN: AUDITORÍA Y GESTIÓN
    // =========================================================================

    // GET /admin/notificaciones: ver todas con filtros (usuario, tipo, leída) //
    public function adminIndex(Request $request)
    {
        Gate::authorize('consultar');   // nivel 1, 2 o 3 //

        $query = Notificacion::with([
            'usuario:id,correo,rol_id',
            'usuario.perfil:usuario_id,nombre,apellido',
        ])->orderByDesc('fecha');

        // Filtros opcionales //
        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('leida')) {
            $query->where('leida', filter_var($request->leida, FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json([
            'data' => $query->paginate(15),
        ]);
    }

    // GET /admin/notificaciones/eliminados: ver todas las eliminadas //
    public function adminEliminados(Request $request)
    {
        Gate::authorize('consultar');

        return response()->json([
            'data' => Notificacion::onlyTrashed()
                ->with([
                    'usuario:id,correo,rol_id',
                    'usuario.perfil:usuario_id,nombre,apellido',
                ])
                ->orderByDesc('fecha')
                ->paginate(15),
        ]);
    }

    // GET /admin/notificaciones/{id}: ver cualquiera (incluye eliminadas) //
    public function adminShow($id)
    {
        Gate::authorize('consultar');

        $notificacion = Notificacion::withTrashed()
            ->with([
                'usuario:id,correo,rol_id',
                'usuario.perfil:usuario_id,nombre,apellido',
            ])
            ->find($id);

        if (!$notificacion) {
            return response()->json([
                'message' => 'La notificación solicitada no existe.',
            ], 404);
        }

        return response()->json([
            'data' => $this->formatear($notificacion),
        ]);
    }

    // POST /admin/notificaciones: crear manualmente (avisos del sistema) //
    public function adminStore(Request $request)
    {
        Gate::authorize('crear');   // nivel 2 o 3 //

        $datos = $request->validate([
            'usuario_id' => [
                'required',
                'integer',
                Rule::exists('usuarios', 'id')->whereNull('deleted_at')->where('estado', true),
            ],
            'titulo'  => ['required', 'string', 'min:3', 'max:150'],
            'mensaje' => ['required', 'string', 'min:5'],
            'tipo'    => ['required', 'string', 'min:3', 'max:50'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'integer'  => 'El campo :attribute debe ser un número entero.',
            'exists'   => 'El usuario seleccionado no existe o no está activo.',
            'string'   => 'El campo :attribute debe ser texto.',
            'min'      => 'El campo :attribute debe tener al menos :min caracteres.',
            'max'      => 'El campo :attribute no puede tener más de :max caracteres.',
        ], [
            'usuario_id' => 'destinatario',
            'titulo'     => 'título',
            'mensaje'    => 'mensaje',
            'tipo'       => 'tipo',
        ]);

        try {
            $notificacion = Notificacion::create([
                'usuario_id' => $datos['usuario_id'],
                'titulo'     => $datos['titulo'],
                'mensaje'    => $datos['mensaje'],
                'tipo'       => $datos['tipo'],
                'leida'      => false,
                'fecha'      => now(),   // la pone el sistema, no el cliente //
            ]);
        } catch (QueryException $e) {
            return $this->errorBD($e, 'crear la notificación');
        }

        return response()->json([
            'message' => 'Notificación creada correctamente.',
            'data'    => $this->formatear($notificacion->load([
                'usuario:id,correo,rol_id',
                'usuario.perfil:usuario_id,nombre,apellido',
            ])),
        ], 201);
    }

    // DELETE /admin/notificaciones/{id}: soft delete de cualquiera //
    public function adminDestroy($id)
    {
        Gate::authorize('eliminar');   // solo nivel 3 //

        $notificacion = Notificacion::find($id);

        if (!$notificacion) {
            return response()->json([
                'message' => 'La notificación no existe o ya fue eliminada.',
            ], 404);
        }

        try {
            $notificacion->delete();
        } catch (QueryException $e) {
            return $this->errorBD($e, 'eliminar la notificación');
        }

        return response()->json([
            'message' => 'Notificación eliminada. Puedes restaurarla.',
        ]);
    }

    // PATCH /admin/notificaciones/{id}/restaurar: solo nivel 3 //
    public function adminRestore($id)
    {
        Gate::authorize('eliminar');

        $notificacion = Notificacion::onlyTrashed()->find($id);

        if (!$notificacion) {
            return response()->json([
                'message' => 'La notificación no existe o no está eliminada.',
            ], 404);
        }

        try {
            $notificacion->restore();
        } catch (QueryException $e) {
            return $this->errorBD($e, 'restaurar la notificación');
        }

        return response()->json([
            'message' => 'Notificación restaurada correctamente.',
        ]);
    }

    // DELETE /admin/notificaciones/{id}/definitivo: solo nivel 3 //
    public function adminForceDelete($id)
    {
        Gate::authorize('eliminar');

        $notificacion = Notificacion::withTrashed()->find($id);

        if (!$notificacion) {
            return response()->json([
                'message' => 'La notificación solicitada no existe.',
            ], 404);
        }

        if (!$notificacion->trashed()) {
            return response()->json([
                'message' => 'La notificación debe eliminarse lógicamente antes de borrarla definitivamente.',
            ], 409);
        }

        try {
            $notificacion->forceDelete();
        } catch (QueryException $e) {
            return $this->errorBD($e, 'eliminar definitivamente la notificación');
        }

        return response()->json([
            'message' => 'Notificación eliminada definitivamente.',
        ]);
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    // Formato uniforme de respuesta //
    private function formatear(Notificacion $n): array
    {
        return [
            'id'                => $n->id,
            'usuario_id'        => $n->usuario_id,
            'usuario'           => $n->usuario?->perfil
                ? trim($n->usuario->perfil->nombre . ' ' . ($n->usuario->perfil->apellido ?? ''))
                : null,   // nombre completo desde el perfil //
            'correo'            => $n->usuario?->correo,
            'titulo'            => $n->titulo,
            'mensaje'           => $n->mensaje,
            'tipo'              => $n->tipo,
            'leida'             => (bool) $n->leida,
            'fecha'             => $n->fecha?->timezone(self::ZONA)->format('d/m/Y H:i'),
            'creado'            => $n->created_at?->timezone(self::ZONA)->format('d/m/Y H:i'),
            'actualizado'       => $n->updated_at?->timezone(self::ZONA)->format('d/m/Y H:i'),
            'eliminado'         => $n->trashed(),
            'fecha_eliminacion' => $n->deleted_at?->timezone(self::ZONA)->format('d/m/Y H:i'),
        ];
    }
}
