<?php

namespace App\Http\Controllers;

use App\Models\Postulacion;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;

class PostulacionController extends Controller
{
    private const RELACIONES = [
        'usuario:id,nombre,apellido,correo',
        'ofertaEmpleo:id,titulo,empresa',
    ];

    public function index()
    {
        return response()->json([
            'data' => Postulacion::with(self::RELACIONES)->paginate(15),
        ]);
    }

    public function eliminados()
    {
        return response()->json([
            'data' => Postulacion::onlyTrashed()
                ->with(self::RELACIONES)
                ->paginate(15),
        ]);
    }

    public function show($id)
    {
        $postulacion = Postulacion::withTrashed()
            ->with(self::RELACIONES)
            ->find($id);

        if (!$postulacion) {
            return response()->json([
                'message' => 'La postulación solicitada no existe.',
            ], 404);
        }

        $zona = 'America/Mexico_City';

        return response()->json([
            'data' => [
                'id' => $postulacion->id,
                'usuario_id' => $postulacion->usuario_id,
                'usuario' => $postulacion->usuario
                    ? "{$postulacion->usuario->nombre} {$postulacion->usuario->apellido}"
                    : null,
                'correo' => $postulacion->usuario?->correo,
                'oferta_empleo_id' => $postulacion->oferta_empleo_id,
                'oferta' => $postulacion->ofertaEmpleo?->titulo,
                'empresa' => $postulacion->ofertaEmpleo?->empresa,
                'fecha_postulacion' => Carbon::parse(
                    $postulacion->fecha_postulacion
                )->format('d/m/Y H:i'),
                'estado' => $postulacion->estado,
                'comentario' => $postulacion->comentario,
                'creado' => $postulacion->created_at
                    ?->timezone($zona)->format('d/m/Y H:i'),
                'actualizado' => $postulacion->updated_at
                    ?->timezone($zona)->format('d/m/Y H:i'),
                'eliminado' => $postulacion->trashed(),
                'fecha_eliminacion' => $postulacion->deleted_at
                    ?->timezone($zona)->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $datos['fecha_postulacion'] = Carbon::parse(
            $datos['fecha_postulacion']
        )->format('Y-m-d H:i:s');

        try {
            $postulacion = Postulacion::create($datos);
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo guardar la postulación.',
            ], 500);
        }

        return response()->json([
            'message' => 'Postulación creada correctamente.',
            'data' => $postulacion->load(self::RELACIONES),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $postulacion = Postulacion::find($id);

        if (!$postulacion) {
            return response()->json([
                'message' => 'La postulación no existe o está eliminada.',
            ], 404);
        }

        $datos = $this->validar($request, $postulacion->id);

        $datos['fecha_postulacion'] = Carbon::parse(
            $datos['fecha_postulacion']
        )->format('Y-m-d H:i:s');

        $postulacion->fill($datos);

        if (!$postulacion->isDirty()) {
            return response()->json([
                'message' => 'No se realizaron cambios en la postulación.',
                'data' => $postulacion->load(self::RELACIONES),
            ]);
        }

        try {
            $postulacion->save();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo actualizar la postulación.',
            ], 500);
        }

        return response()->json([
            'message' => 'Postulación actualizada correctamente.',
            'data' => $postulacion->fresh()->load(self::RELACIONES),
        ]);
    }

    public function destroy($id)
    {
        $postulacion = Postulacion::with(self::RELACIONES)->find($id);

        if (!$postulacion) {
            return response()->json([
                'message' => 'La postulación no existe o ya fue eliminada.',
            ], 404);
        }

        $etiqueta = $this->etiqueta($postulacion);

        try {
            $postulacion->delete();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar la postulación.',
            ], 500);
        }

        return response()->json([
            'message' => "{$etiqueta} se eliminó correctamente. Puedes restaurarla.",
        ]);
    }

    public function restore($id)
    {
        $postulacion = Postulacion::onlyTrashed()
            ->with(self::RELACIONES)
            ->find($id);

        if (!$postulacion) {
            return response()->json([
                'message' => 'La postulación no existe o no está eliminada.',
            ], 404);
        }

        try {
            $postulacion->restore();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo restaurar la postulación.',
            ], 500);
        }

        return response()->json([
            'message' => 'Postulación restaurada correctamente.',
            'data' => $postulacion->fresh()->load(self::RELACIONES),
        ]);
    }

    public function forceDelete($id)
    {
        $postulacion = Postulacion::withTrashed()
            ->with(self::RELACIONES)
            ->find($id);

        if (!$postulacion) {
            return response()->json([
                'message' => 'La postulación solicitada no existe.',
            ], 404);
        }

        if (!$postulacion->trashed()) {
            return response()->json([
                'message' => 'La postulación debe eliminarse lógicamente antes de borrarla definitivamente.',
            ], 409);
        }

        $etiqueta = $this->etiqueta($postulacion);

        try {
            $postulacion->forceDelete();
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar definitivamente la postulación.',
            ], 500);
        }

        return response()->json([
            'message' => "{$etiqueta} se eliminó definitivamente.",
        ]);
    }

    private function etiqueta(Postulacion $postulacion): string
    {
        $usuario = $postulacion->usuario
            ? "{$postulacion->usuario->nombre} {$postulacion->usuario->apellido}"
            : 'un usuario';

        $oferta = $postulacion->ofertaEmpleo?->titulo ?? 'una oferta';

        return "La postulación de «{$usuario}» a «{$oferta}»";
    }

    private function validar(Request $request, $id = null): array
    {
        return $request->validate([
            'usuario_id' => [
                'required',
                Rule::exists('usuarios', 'id')
                    ->whereNull('deleted_at'),
            ],
            'oferta_empleo_id' => [
                'required',
                Rule::exists('ofertas_empleo', 'id')
                    ->whereNull('deleted_at'),
                Rule::unique('postulaciones', 'oferta_empleo_id')
                    ->where('usuario_id', $request->input('usuario_id'))
                    ->ignore($id),
            ],
            'fecha_postulacion' => ['required', 'date'],
            'estado' => [
                'required',
                'in:En revisión,Aceptada,Rechazada',
            ],
            'comentario' => [
                'required',
                'string',
                'min:5',
                'max:1000',
            ],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
            'usuario_id.exists' => 'El usuario seleccionado no existe o está eliminado.',
            'oferta_empleo_id.exists' => 'La oferta seleccionada no existe o está eliminada.',
            'oferta_empleo_id.unique' => 'Este usuario ya se postuló a esa oferta.',
            'date' => 'La fecha de postulación no es válida.',
            'in' => 'El estado seleccionado no es válido.',
        ], [
            'usuario_id' => 'usuario',
            'oferta_empleo_id' => 'oferta de empleo',
            'fecha_postulacion' => 'fecha de postulación',
        ]);
    }
}
