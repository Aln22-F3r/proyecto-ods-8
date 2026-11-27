<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LogController extends Controller
{
    private const ZONA = 'America/Mexico_City';   // zona horaria para formatear fechas //

    // ======================= LECTURA ======================= //

    // GET /admin/logs: lista paginada con filtros opcionales //
    public function index(Request $request)
    {
        Gate::authorize('consultar');   // nivel 1, 2 o 3 //

        $query = Log::with([
            'usuario:id,correo,rol_id',                    // datos del actor (correo y rol) //
            'usuario.perfil:usuario_id,nombre,apellido',   // nombre desde perfiles, no desde usuarios //
        ])->orderByDesc('fecha');

        // Filtros opcionales por query string //
        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        if ($request->filled('accion')) {
            $query->where('accion', 'like', '%' . $request->accion . '%');
        }

        if ($request->filled('tabla_afectada')) {
            $query->where('tabla_afectada', $request->tabla_afectada);
        }

        if ($request->filled('registro_id')) {
            $query->where('registro_id', $request->registro_id);
        }

        if ($request->filled('desde') && $request->filled('hasta')) {
            $query->whereBetween('fecha', [$request->desde, $request->hasta]);
        }

        return response()->json([
            'data' => $query->paginate(15),
        ]);
    }

    // GET /admin/logs/eliminados: lista de logs con borrado lógico //
    public function eliminados(Request $request)
    {
        Gate::authorize('consultar');   // nivel 1, 2 o 3 //

        return response()->json([
            'data' => Log::onlyTrashed()
                ->with([
                    'usuario:id,correo,rol_id',
                    'usuario.perfil:usuario_id,nombre,apellido',
                ])
                ->orderByDesc('fecha')
                ->paginate(15),
        ]);
    }

    // GET /admin/logs/{id}: detalle de un log (incluye eliminados) //
    public function show($id)
    {
        Gate::authorize('consultar');

        $log = Log::withTrashed()
            ->with([
                'usuario:id,correo,rol_id',
                'usuario.perfil:usuario_id,nombre,apellido',
            ])
            ->find($id);

        if (!$log) {
            return response()->json([
                'message' => 'El log solicitado no existe.',
            ], 404);
        }

        return response()->json([
            'data' => $this->formatear($log),
        ]);
    }

    // ======================= BORRADO (solo nivel 3) ======================= //

    // DELETE /admin/logs/{id}: soft delete (limpieza puntual) //
    public function destroy($id)
    {
        Gate::authorize('eliminar');   // solo nivel 3 //

        $log = Log::find($id);

        if (!$log) {
            return response()->json([
                'message' => 'El log solicitado no existe o ya fue eliminado.',
            ], 404);
        }

        try {
            $log->delete();
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar el log. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => "{$this->etiqueta($log)} se eliminó. Puedes restaurarlo.",
        ]);
    }

    // PATCH /admin/logs/{id}/restaurar: revertir soft delete //
    public function restore($id)
    {
        Gate::authorize('eliminar');   // solo nivel 3 //

        $log = Log::onlyTrashed()->find($id);

        if (!$log) {
            return response()->json([
                'message' => 'El log no existe o no está eliminado.',
            ], 404);
        }

        try {
            $log->restore();
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo restaurar el log. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => "{$this->etiqueta($log)} se restauró correctamente.",
            'data'    => $this->formatear($log->fresh()->load([
                'usuario:id,correo,rol_id',
                'usuario.perfil:usuario_id,nombre,apellido',
            ])),
        ]);
    }

    // DELETE /admin/logs/{id}/definitivo: borrado físico //
    public function forceDelete($id)
    {
        Gate::authorize('eliminar');   // solo nivel 3 //

        $log = Log::withTrashed()->find($id);

        if (!$log) {
            return response()->json([
                'message' => 'El log solicitado no existe.',
            ], 404);
        }

        if (!$log->trashed()) {
            return response()->json([
                'message' => 'El log debe eliminarse lógicamente antes de borrarlo definitivamente.',
            ], 409);
        }

        $etiqueta = $this->etiqueta($log);

        try {
            $log->forceDelete();
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar definitivamente el log. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => "{$etiqueta} se eliminó definitivamente.",
        ]);
    }

    // ======================= HELPERS ======================= //

    // Formato uniforme de respuesta //
    private function formatear(Log $log): array
    {
        $perfil = $log->usuario?->perfil;

        return [
            'id'                => $log->id,
            'usuario_id'        => $log->usuario_id,
            'usuario'           => $perfil
                ? trim($perfil->nombre . ' ' . ($perfil->apellido ?? ''))
                : null,                                                    // nombre completo desde perfiles //
            'correo'            => $log->usuario?->correo,
            'accion'            => $log->accion,
            'descripcion'       => $log->descripcion,
            'tabla_afectada'    => $log->tabla_afectada,
            'registro_id'       => $log->registro_id,
            'fecha'             => $log->fecha?->timezone(self::ZONA)->format('d/m/Y H:i'),
            'creado'            => $log->created_at?->timezone(self::ZONA)->format('d/m/Y H:i'),
            'actualizado'       => $log->updated_at?->timezone(self::ZONA)->format('d/m/Y H:i'),
            'eliminado'         => $log->trashed(),
            'fecha_eliminacion' => $log->deleted_at?->timezone(self::ZONA)->format('d/m/Y H:i'),
        ];
    }

    // Texto legible para mensajes de éxito //
    private function etiqueta(Log $log): string
    {
        $perfil = $log->usuario?->perfil;
        $nombre = $perfil
            ? trim($perfil->nombre . ' ' . ($perfil->apellido ?? ''))
            : 'un usuario';

        return "El log «{$log->accion}» de «{$nombre}»";
    }
}
