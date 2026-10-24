<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LogController extends Controller
{
    public function index()
    {
        $logs = Log::with('usuario:id,nombre,apellido')
            ->paginate(15);

        return response()->json($logs);
    }

    public function eliminados()
    {
        $logs = Log::onlyTrashed()
            ->with('usuario:id,nombre,apellido')
            ->paginate(15);

        return response()->json($logs);
    }

    public function show($id)
    {
        $log = Log::withTrashed()
            ->with('usuario:id,nombre,apellido,correo')
            ->find($id);

        if (!$log) {
            return response()->json([
                'message' => 'El log solicitado no existe.',
            ], 404);
        }

        $zona = 'America/Mexico_City';

        return response()->json([
            'data' => [
                'id' => $log->id,
                'usuario_id' => $log->usuario_id,
                'usuario' => $log->usuario
                    ? "{$log->usuario->nombre} {$log->usuario->apellido}"
                    : null,
                'correo' => $log->usuario?->correo,
                'accion' => $log->accion,
                'fecha' => Carbon::parse($log->fecha)
                    ->timezone($zona)
                    ->format('Y-m-d H:i:s'),
                'ip' => $log->ip,
                'created_at' => $log->created_at
                    ?->timezone($zona)->toDateTimeString(),
                'updated_at' => $log->updated_at
                    ?->timezone($zona)->toDateTimeString(),
                'eliminado' => $log->trashed(),
                'deleted_at' => $log->deleted_at
                    ?->timezone($zona)->toDateTimeString(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $datos['fecha'] = Carbon::parse($datos['fecha'])
            ->format('Y-m-d H:i:s');

        try {
            $log = Log::create($datos);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo guardar el log. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => 'Log creado correctamente.',
            'data' => $log->load('usuario:id,nombre,apellido'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $log = Log::find($id);

        if (!$log) {
            return response()->json([
                'message' => 'El log solicitado no existe o está eliminado. No se puede actualizar.',
            ], 404);
        }

        $datos = $this->validar($request);

        $datos['fecha'] = Carbon::parse($datos['fecha'])
            ->format('Y-m-d H:i:s');

        $log->fill($datos);

        if (!$log->isDirty()) {
            return response()->json([
                'message' => 'No se realizaron cambios en el log.',
                'data' => $log->load('usuario:id,nombre,apellido'),
            ]);
        }

        try {
            $log->save();
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo actualizar el log. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => 'Log actualizado correctamente.',
            'data' => $log->fresh()->load('usuario:id,nombre,apellido'),
        ]);
    }

    public function destroy($id)
    {
        $log = Log::with('usuario:id,nombre,apellido')->find($id);

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
            'message' => "{$this->etiqueta($log)} se eliminó. Puedes restaurarlo desde la lista de eliminados.",
        ]);
    }

    public function restore($id)
    {
        $log = Log::onlyTrashed()
            ->with('usuario:id,nombre,apellido')
            ->find($id);

        if (!$log) {
            return response()->json([
                'message' => 'El log solicitado no existe o no está eliminado.',
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
            'data' => $log->fresh()->load('usuario:id,nombre,apellido'),
        ]);
    }

    public function forceDelete($id)
    {
        $log = Log::withTrashed()
            ->with('usuario:id,nombre,apellido')
            ->find($id);

        if (!$log) {
            return response()->json([
                'message' => 'El log solicitado no existe.',
            ], 404);
        }

        if (!$log->trashed()) {
            return response()->json([
                'message' => 'El log debe eliminarse primero de forma lógica antes de borrarlo definitivamente.',
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

    private function etiqueta(Log $log): string
    {
        $usuario = $log->usuario
            ? "{$log->usuario->nombre} {$log->usuario->apellido}"
            : 'un usuario';

        return "El log «{$log->accion}» de «{$usuario}»";
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'usuario_id' => [
                'required',
                'exists:usuarios,id',
            ],
            'accion' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],
            'fecha' => [
                'required',
                'date',
            ],
            'ip' => [
                'required',
                'ip',
                'max:45',
            ],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
            'exists' => 'El usuario seleccionado no es válido.',
            'date' => 'La fecha no es válida.',
            'ip' => 'La IP no tiene un formato válido (IPv4 o IPv6).',
        ], [
            'usuario_id' => 'usuario',
            'accion' => 'acción',
            'ip' => 'IP',
        ]);
    }
}
