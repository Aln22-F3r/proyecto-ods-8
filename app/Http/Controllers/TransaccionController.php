<?php

namespace App\Http\Controllers;

use App\Models\Transaccion;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;

class TransaccionController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Transaccion::with('usuario:id,nombre,apellido')
                ->paginate(15),
        ]);
    }

    public function eliminados()
    {
        return response()->json([
            'data' => Transaccion::onlyTrashed()
                ->with('usuario:id,nombre,apellido')
                ->paginate(15),
        ]);
    }

    public function show($id)
    {
        $transaccion = Transaccion::withTrashed()
            ->with('usuario:id,nombre,apellido,correo')
            ->find($id);

        if (!$transaccion) {
            return response()->json([
                'message' => 'La transacción solicitada no existe.',
            ], 404);
        }

        $zona = 'America/Mexico_City';

        return response()->json([
            'data' => [
                'id' => $transaccion->id,
                'usuario_id' => $transaccion->usuario_id,
                'usuario' => $transaccion->usuario
                    ? "{$transaccion->usuario->nombre} {$transaccion->usuario->apellido}"
                    : null,
                'correo' => $transaccion->usuario?->correo,
                'tipo_registro' => $transaccion->tipo_registro,
                'descripcion' => $transaccion->descripcion,
                'fecha' => Carbon::parse($transaccion->fecha)
                    ->timezone($zona)
                    ->format('d/m/Y H:i'),
                'estado' => $transaccion->estado,
                'creado' => $transaccion->created_at?->timezone($zona)->format('d/m/Y H:i'),
                'actualizado' => $transaccion->updated_at?->timezone($zona)->format('d/m/Y H:i'),
                'eliminado' => $transaccion->trashed(),
                'fecha_eliminacion' => $transaccion->deleted_at?->timezone($zona)->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $datos['fecha'] = Carbon::parse($datos['fecha'])
            ->format('Y-m-d H:i:s');

        try {
            $transaccion = Transaccion::create($datos);
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo guardar la transacción. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => 'Transacción creada correctamente.',
            'data' => $transaccion->load('usuario:id,nombre,apellido'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $transaccion = Transaccion::find($id);

        if (!$transaccion) {
            return response()->json([
                'message' => 'La transacción solicitada no existe o está eliminada.',
            ], 404);
        }

        $datos = $this->validar($request, $id);

        $datos['fecha'] = Carbon::parse($datos['fecha'])
            ->format('Y-m-d H:i:s');

        $transaccion->fill($datos);

        if (!$transaccion->isDirty()) {
            return response()->json([
                'message' => 'No se realizaron cambios en la transacción.',
                'data' => $transaccion->load('usuario:id,nombre,apellido'),
            ]);
        }

        try {
            $transaccion->save();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo actualizar la transacción. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => 'Transacción actualizada correctamente.',
            'data' => $transaccion->fresh()->load('usuario:id,nombre,apellido'),
        ]);
    }

    // Borrado lógico //
    public function destroy($id)
    {
        $transaccion = Transaccion::with('usuario:id,nombre,apellido')
            ->find($id);

        if (!$transaccion) {
            return response()->json([
                'message' => 'La transacción solicitada no existe o ya fue eliminada.',
            ], 404);
        }

        $etiqueta = $this->etiqueta($transaccion);

        try {
            $transaccion->delete();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar la transacción. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => "{$etiqueta} se eliminó. Puedes restaurarla desde la lista de eliminadas.",
        ]);
    }

    public function restore($id)
    {
        $transaccion = Transaccion::onlyTrashed()
            ->with('usuario:id,nombre,apellido')
            ->find($id);

        if (!$transaccion) {
            return response()->json([
                'message' => 'La transacción solicitada no existe o no está eliminada.',
            ], 404);
        }

        try {
            $transaccion->restore();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo restaurar la transacción. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => "{$this->etiqueta($transaccion)} se restauró correctamente.",
            'data' => $transaccion->fresh()->load('usuario:id,nombre,apellido'),
        ]);
    }

    // Borrado físico: requiere que la transacción ya esté eliminada lógicamente //
    public function forceDelete($id)
    {
        $transaccion = Transaccion::withTrashed()
            ->with('usuario:id,nombre,apellido')
            ->find($id);

        if (!$transaccion) {
            return response()->json([
                'message' => 'La transacción solicitada no existe.',
            ], 404);
        }

        if (!$transaccion->trashed()) {
            return response()->json([
                'message' => 'La transacción debe eliminarse primero antes de borrarla definitivamente.',
            ], 409);
        }

        $etiqueta = $this->etiqueta($transaccion);

        try {
            $transaccion->forceDelete();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar definitivamente la transacción. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => "{$etiqueta} se eliminó definitivamente.",
        ]);
    }

    private function etiqueta(Transaccion $transaccion): string
    {
        $usuario = $transaccion->usuario
            ? "{$transaccion->usuario->nombre} {$transaccion->usuario->apellido}"
            : 'un usuario';

        return "La transacción «{$transaccion->tipo_registro}» de «{$usuario}»";
    }

    private function validar(Request $request, $id = null): array
    {
        return $request->validate([
            'usuario_id' => [
                'required',
                Rule::exists('usuarios', 'id')->whereNull('deleted_at'),
            ],
            'tipo_registro' => [
                'required',
                'in:Registro,Publicación,Postulación',
            ],
            'descripcion' => ['required', 'string', 'min:5', 'max:255'],
            'fecha' => ['required', 'date'],
            'estado' => ['required', 'in:Pendiente,Completada'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
            'exists' => 'El usuario seleccionado no es válido o está eliminado.',
            'in' => 'El valor seleccionado en :attribute no es válido.',
            'date' => 'La fecha no es válida.',
        ], [
            'usuario_id' => 'usuario',
            'tipo_registro' => 'tipo de registro',
            'descripcion' => 'descripción',
        ]);
    }
}
