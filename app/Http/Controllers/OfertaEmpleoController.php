<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\OfertaEmpleo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OfertaEmpleoController extends Controller
{
    public function index()
    {
        $ofertas = OfertaEmpleo::with('categoria:id,nombre')
            ->withCount(['postulaciones', 'listaDeseos'])
            ->paginate(15);

        return response()->json($ofertas);
    }

    public function eliminados()
    {
        $ofertas = OfertaEmpleo::onlyTrashed()
            ->with('categoria:id,nombre')
            ->withCount(['postulaciones', 'listaDeseos'])
            ->paginate(15);

        return response()->json($ofertas);
    }

    public function show($id)
    {
        $oferta = OfertaEmpleo::withTrashed()
            ->with('categoria:id,nombre')
            ->withCount(['postulaciones', 'listaDeseos'])
            ->find($id);

        if (!$oferta) {
            return response()->json([
                'message' => 'La oferta de empleo solicitada no existe.',
            ], 404);
        }

        $zona = 'America/Mexico_City';

        return response()->json([
            'data' => [
                'id' => $oferta->id,
                'categoria_id' => $oferta->categoria_id,
                'categoria' => $oferta->categoria?->nombre,
                'titulo' => $oferta->titulo,
                'empresa' => $oferta->empresa,
                'descripcion' => $oferta->descripcion,
                'ubicacion' => $oferta->ubicacion,
                'salario' => $oferta->salario,
                'tipo_empleo' => $oferta->tipo_empleo,
                'fecha_publicacion' => $oferta->fecha_publicacion,
                'estado' => $oferta->estado,
                'logo' => $this->urlSiExiste($oferta->logo),
                'postulaciones_count' => $oferta->postulaciones_count,
                'lista_deseos_count' => $oferta->lista_deseos_count,
                'created_at' => $oferta->created_at?->timezone($zona)->toDateTimeString(),
                'updated_at' => $oferta->updated_at?->timezone($zona)->toDateTimeString(),
                'eliminado' => $oferta->trashed(),
                'deleted_at' => $oferta->deleted_at?->timezone($zona)->toDateTimeString(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $datos['fecha_publicacion'] = Carbon::parse(
            $datos['fecha_publicacion']
        )->format('Y-m-d H:i:s');

        $rutaNueva = null;

        try {
            $oferta = DB::transaction(function () use (
                $request,
                $datos,
                &$rutaNueva
            ) {
                // Crear primero el registro sin el logo. //
                $oferta = OfertaEmpleo::create(
                    collect($datos)->except('logo')->all()
                );

                if ($request->hasFile('logo')) {
                    $logo = $request->file('logo');

                    // Nombre único para evitar sobrescribir archivos existentes. //
                    $nombre = 'OfertaEmpleo_' . $oferta->id . '_'
                        . Str::uuid() . '.' . $logo->extension();

                    $rutaNueva = $logo->storeAs(
                        'imagenes',
                        $nombre,
                        'public'
                    );

                    if (!$rutaNueva) {
                        throw new \RuntimeException(
                            'No se pudo guardar el logo.'
                        );
                    }

                    $oferta->logo = Storage::url($rutaNueva);
                    $oferta->save();
                }

                return $oferta;
            });
        } catch (\Throwable $e) {
            $this->borrarArchivo($rutaNueva);

            report($e);

            return response()->json([
                'message' => 'No se pudo guardar la oferta de empleo. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => 'Oferta de empleo creada correctamente.',
            'data' => $oferta->load('categoria:id,nombre'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $oferta = OfertaEmpleo::find($id);

        if (!$oferta) {
            return response()->json([
                'message' => 'La oferta de empleo solicitada no existe o está eliminada.',
            ], 404);
        }

        $datos = $this->validar($request);

        $datos['fecha_publicacion'] = Carbon::parse(
            $datos['fecha_publicacion']
        )->format('Y-m-d H:i:s');

        // El logo se gestiona por separado. //
        $oferta->fill(collect($datos)->except('logo')->all());

        if (!$oferta->isDirty() && !$request->hasFile('logo')) {
            return response()->json([
                'message' => 'No se realizaron cambios en la oferta de empleo.',
                'data' => $oferta->load('categoria:id,nombre'),
            ]);
        }

        $rutaNueva = null;
        $logoAnterior = null;

        try {
            DB::transaction(function () use (
                $request,
                $oferta,
                &$rutaNueva,
                &$logoAnterior
            ) {
                if ($request->hasFile('logo')) {
                    $logo = $request->file('logo');

                    $nombre = 'OfertaEmpleo_' . $oferta->id . '_'
                        . Str::uuid() . '.' . $logo->extension();

                    $rutaNueva = $logo->storeAs(
                        'imagenes',
                        $nombre,
                        'public'
                    );

                    if (!$rutaNueva) {
                        throw new \RuntimeException(
                            'No se pudo guardar el logo.'
                        );
                    }

                    $logoAnterior = $oferta->logo;
                    $oferta->logo = Storage::url($rutaNueva);
                }

                $oferta->save();
            });
        } catch (\Throwable $e) {
            $this->borrarArchivo($rutaNueva);

            report($e);

            return response()->json([
                'message' => 'No se pudo actualizar la oferta de empleo. Intenta de nuevo.',
            ], 500);
        }

        // Borrar el logo anterior solamente después de guardar los cambios. //
        if ($logoAnterior && $logoAnterior !== $oferta->logo) {
            $this->borrarArchivo($logoAnterior);
        }

        return response()->json([
            'message' => 'Oferta de empleo actualizada correctamente.',
            'data' => $oferta->fresh()->load('categoria:id,nombre'),
        ]);
    }

    public function destroy($id)
    {
        $oferta = OfertaEmpleo::find($id);

        if (!$oferta) {
            return response()->json([
                'message' => 'La oferta de empleo solicitada no existe o ya fue eliminada.',
            ], 404);
        }

        try {
            $oferta->delete();
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar la oferta de empleo. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => "{$this->etiqueta($oferta)} se eliminó. Puedes restaurarla.",
        ]);
    }

    public function restore($id)
    {
        $oferta = OfertaEmpleo::onlyTrashed()->find($id);

        if (!$oferta) {
            return response()->json([
                'message' => 'La oferta de empleo solicitada no existe o no está eliminada.',
            ], 404);
        }

        try {
            $oferta->restore();
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo restaurar la oferta de empleo. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => "{$this->etiqueta($oferta)} se restauró correctamente.",
            'data' => $oferta->fresh()->load('categoria:id,nombre'),
        ]);
    }

    public function forceDelete($id)
    {
        $oferta = OfertaEmpleo::withTrashed()->find($id);

        if (!$oferta) {
            return response()->json([
                'message' => 'La oferta de empleo solicitada no existe.',
            ], 404);
        }

        if (!$oferta->trashed()) {
            return response()->json([
                'message' => 'La oferta debe eliminarse primero de forma lógica antes de borrarla definitivamente.',
            ], 409);
        }

        // Contar también las postulaciones que estén eliminadas lógicamente. //
        $dependencias = [
            [
                $oferta->postulaciones()->withTrashed()->count(),
                'postulación',
                'postulaciones',
            ],
            [
                $oferta->listaDeseos()->count(),
                'elemento en la lista de deseos',
                'elementos en la lista de deseos',
            ],
        ];

        $detalle = [];

        foreach ($dependencias as [$total, $singular, $plural]) {
            if ($total > 0) {
                $detalle[] = $total . ' '
                    . ($total === 1 ? $singular : $plural);
            }
        }

        if (count($detalle) > 0) {
            return response()->json([
                'message' => "No se puede eliminar definitivamente la oferta «{$oferta->titulo}»: está siendo usada por "
                    . implode(' y ', $detalle)
                    . '. Elimina primero esos registros dependientes.',
                'dependencias' => $detalle,
            ], 409);
        }

        $etiqueta = $this->etiqueta($oferta);
        $logo = $oferta->logo;

        try {
            $oferta->forceDelete();
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar definitivamente la oferta. Intenta de nuevo.',
            ], 500);
        }

        // Eliminar el archivo solamente después de borrar el registro. //
        $this->borrarArchivo($logo);

        return response()->json([
            'message' => "{$etiqueta} se eliminó definitivamente, junto con su logo.",
        ]);
    }

    private function etiqueta(OfertaEmpleo $oferta): string
    {
        return "La oferta «{$oferta->titulo}» de «{$oferta->empresa}»";
    }

    private function relativa(?string $ruta): ?string
    {
        if (!$ruta) {
            return null;
        }

        return Str::after($ruta, '/storage/');
    }

    private function urlSiExiste(?string $ruta): ?string
    {
        $relativa = $this->relativa($ruta);

        return ($relativa && Storage::disk('public')->exists($relativa))
            ? Storage::url($relativa)
            : null;
    }

    private function borrarArchivo(?string $ruta): void
    {
        if (!$ruta) {
            return;
        }

        $relativa = str_contains($ruta, '/storage/')
            ? $this->relativa($ruta)
            : $ruta;

        if ($relativa) {
            Storage::disk('public')->delete($relativa);
        }
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'categoria_id' => [
                'required',
                'exists:categorias,id',
            ],
            'titulo' => [
                'required',
                'string',
                'min:3',
                'max:150',
            ],
            'empresa' => [
                'required',
                'string',
                'min:2',
                'max:150',
            ],
            'descripcion' => [
                'required',
                'string',
                'min:10',
            ],
            'ubicacion' => [
                'required',
                'string',
                'min:3',
                'max:150',
            ],
            'salario' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
            'tipo_empleo' => [
                'required',
                'in:Tiempo completo,Medio tiempo,Por obra',
            ],
            'fecha_publicacion' => [
                'required',
                'date',
            ],
            'estado' => [
                'required',
                'in:Abierta,Cerrada',
            ],
            'logo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
            'exists' => 'La categoría seleccionada no es válida.',
            'numeric' => 'El salario debe ser un número.',
            'salario.min' => 'El salario no puede ser negativo.',
            'salario.max' => 'El salario no puede ser mayor a :max.',
            'in' => 'El valor seleccionado en :attribute no es válido.',
            'date' => 'La fecha de publicación no es válida.',
            'image' => 'El logo debe ser una imagen.',
            'logo.mimes' => 'El logo debe ser de tipo jpg, jpeg, png o webp.',
            'logo.max' => 'El logo no puede pesar más de 2 MB.',
        ], [
            'categoria_id' => 'categoría',
            'titulo' => 'título',
            'descripcion' => 'descripción',
            'ubicacion' => 'ubicación',
            'tipo_empleo' => 'tipo de empleo',
            'fecha_publicacion' => 'fecha de publicación',
        ]);
    }
}
