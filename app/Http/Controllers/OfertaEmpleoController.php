<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\OfertaEmpleo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OfertaEmpleoController extends Controller
{
    public function index()
    {
        return Inertia::render('OfertasEmpleo/Index', [
            'ofertas' => OfertaEmpleo::with('categoria:id,nombre')->paginate(5),
            'eliminados' => false,
        ]);
    }

    public function eliminados()
    {
        return Inertia::render('OfertasEmpleo/Index', [
            'ofertas' => OfertaEmpleo::onlyTrashed()->with('categoria:id,nombre')->paginate(5),
            'eliminados' => true,
        ]);
    }

    public function show($id)
    {
        $oferta = OfertaEmpleo::withTrashed()
            ->with('categoria:id,nombre')
            ->withCount(['postulaciones', 'listaDeseos'])
            ->find($id);

        if (!$oferta) {
            return redirect('/ofertas-empleo')->with('error', 'La oferta de empleo solicitada no existe.');
        }

        $zona = 'America/Mexico_City';

        return Inertia::render('OfertasEmpleo/Show', [
            'oferta' => [
                'id' => $oferta->id,
                'categoria' => $oferta->categoria?->nombre,
                'titulo' => $oferta->titulo,
                'empresa' => $oferta->empresa,
                'descripcion' => $oferta->descripcion,
                'ubicacion' => $oferta->ubicacion,
                'salario' => '$' . number_format((float) $oferta->salario, 2),
                'tipo_empleo' => $oferta->tipo_empleo,
                'fecha_publicacion' => Carbon::parse($oferta->fecha_publicacion)->format('d/m/Y H:i'),
                'estado' => $oferta->estado,
                // Solo se envía la URL si el archivo realmente existe en el disco //
                'logo' => $this->urlSiExiste($oferta->logo),
                'postulaciones_count' => $oferta->postulaciones_count,
                'lista_deseos_count' => $oferta->lista_deseos_count,
                'creado' => $oferta->created_at?->timezone($zona)->format('d/m/Y H:i'),
                'actualizado' => $oferta->updated_at?->timezone($zona)->format('d/m/Y H:i'),
                'eliminado' => $oferta->trashed(),
                'fecha_eliminacion' => $oferta->deleted_at?->timezone($zona)->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('OfertasEmpleo/Form', [
            'categorias' => $this->categorias(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $datos['fecha_publicacion'] = Carbon::parse($datos['fecha_publicacion'])->format('Y-m-d H:i:s');

        try {
            DB::transaction(function () use ($request, $datos) {
                // Se crea el registro para obtener su id //
                $oferta = OfertaEmpleo::create(collect($datos)->except('logo')->all());

                // Se guarda el logo con el id y se actualiza la ruta en la base de datos //
                if ($request->hasFile('logo')) {
                    $logo = $request->file('logo');
                    $ruta = $logo->storeAs('imagenes', "OfertaEmpleo_{$oferta->id}_1." . $logo->extension(), 'public');
                    $oferta->logo = Storage::url($ruta);
                    $oferta->save();
                }
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo guardar la oferta de empleo. Intenta de nuevo.');
        }

        return redirect('/ofertas-empleo')->with('exito', 'Oferta de empleo creada correctamente.');
    }

    public function edit($id)
    {
        $oferta = OfertaEmpleo::find($id);

        if (!$oferta) {
            return redirect('/ofertas-empleo')->with('error', 'La oferta de empleo solicitada no existe. No se puede editar.');
        }

        return Inertia::render('OfertasEmpleo/Form', [
            'oferta' => [
                'id' => $oferta->id,
                'categoria_id' => $oferta->categoria_id,
                'titulo' => $oferta->titulo,
                'empresa' => $oferta->empresa,
                'descripcion' => $oferta->descripcion,
                'ubicacion' => $oferta->ubicacion,
                'salario' => $oferta->salario,
                'tipo_empleo' => $oferta->tipo_empleo,
                'fecha_publicacion' => Carbon::parse($oferta->fecha_publicacion)->format('Y-m-d\TH:i:s'),
                'estado' => $oferta->estado,
                'logo' => $oferta->logo,
            ],
            // Se incluye la categoría actual aunque esté eliminada, para que no desaparezca del select //
            'categorias' => Categoria::withTrashed()
                ->where(function ($q) use ($oferta) {
                    $q->whereNull('deleted_at')->orWhere('id', $oferta->categoria_id);
                })
                ->select('id', 'nombre')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $oferta = OfertaEmpleo::find($id);

        if (!$oferta) {
            return redirect('/ofertas-empleo')->with('error', 'La oferta de empleo solicitada no existe. No se puede actualizar.');
        }

        $datos = $this->validar($request);

        $datos['fecha_publicacion'] = Carbon::parse($datos['fecha_publicacion'])->format('Y-m-d H:i:s');

        // El logo se maneja aparte; aquí solo los datos de texto //
        $oferta->fill(collect($datos)->except('logo')->all());

        if (!$oferta->isDirty() && !$request->hasFile('logo')) {
            return redirect('/ofertas-empleo')->with('advertencia', 'No se realizaron cambios en la oferta de empleo.');
        }

        $logoAnterior = null;

        try {
            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $ruta = $logo->storeAs('imagenes', "OfertaEmpleo_{$oferta->id}_1." . $logo->extension(), 'public');
                $url = Storage::url($ruta);
                if ($oferta->logo !== $url) {
                    $logoAnterior = $oferta->logo;
                }
                $oferta->logo = $url;
            }

            $oferta->save();
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo actualizar la oferta de empleo. Intenta de nuevo.');
        }

        // Solo se borra el logo viejo cuando todo se guardó bien y su nombre cambió //
        $this->borrarArchivo($logoAnterior);

        return redirect('/ofertas-empleo')->with('exito', 'Oferta de empleo actualizada correctamente.');
    }

    // Borrado lógico: solo llena deleted_at, el registro y su logo se conservan //
    public function destroy($id)
    {
        $oferta = OfertaEmpleo::find($id);

        if (!$oferta) {
            return redirect('/ofertas-empleo')->with('error', 'La oferta de empleo solicitada no existe o ya fue eliminada.');
        }

        try {
            $oferta->delete();
        } catch (\Throwable $e) {
            return redirect('/ofertas-empleo')->with('error', 'No se pudo eliminar la oferta de empleo. Intenta de nuevo.');
        }

        return redirect('/ofertas-empleo')->with('exito', "{$this->etiqueta($oferta)} se eliminó. Puedes restaurarla desde la lista de eliminadas.");
    }

    public function restore($id)
    {
        $oferta = OfertaEmpleo::onlyTrashed()->find($id);

        if (!$oferta) {
            return redirect('/ofertas-empleo/eliminados')->with('error', 'La oferta de empleo solicitada no existe o no está eliminada.');
        }

        try {
            $oferta->restore();
        } catch (\Throwable $e) {
            return redirect('/ofertas-empleo/eliminados')->with('error', 'No se pudo restaurar la oferta de empleo. Intenta de nuevo.');
        }

        return redirect('/ofertas-empleo/eliminados')->with('exito', "{$this->etiqueta($oferta)} se restauró y ya aparece en la lista principal.");
    }

    // Borrado físico: solo para registros ya eliminados y sin dependencias //
    public function forceDelete($id)
    {
        $oferta = OfertaEmpleo::withTrashed()->find($id);

        if (!$oferta) {
            return redirect('/ofertas-empleo/eliminados')->with('error', 'La oferta de empleo solicitada no existe.');
        }

        if (!$oferta->trashed()) {
            return redirect('/ofertas-empleo')->with('advertencia', 'La oferta de empleo debe eliminarse primero (borrado lógico) antes de poder borrarla definitivamente.');
        }

        // Se cuentan también las postulaciones eliminadas lógicamente, porque siguen apuntando a la oferta en la base de datos. //
        // La lista de deseos no tiene borrado lógico, por eso se cuenta sin withTrashed(). //
        $dependencias = [
            [$oferta->postulaciones()->withTrashed()->count(), 'postulación', 'postulaciones'],
            [$oferta->listaDeseos()->count(), 'elemento en la lista de deseos', 'elementos en la lista de deseos'],
        ];

        $detalle = [];
        foreach ($dependencias as [$total, $singular, $plural]) {
            if ($total > 0) {
                $detalle[] = $total . ' ' . ($total === 1 ? $singular : $plural);
            }
        }

        if (count($detalle) > 0) {
            return redirect('/ofertas-empleo/eliminados')->with('error', "No se puede eliminar definitivamente la oferta «{$oferta->titulo}»: está siendo usada por " . implode(' y ', $detalle) . ' (activos o eliminados). Elimina primero esos registros.');
        }

        $etiqueta = $this->etiqueta($oferta);
        $logo = $oferta->logo;

        try {
            $oferta->forceDelete();
        } catch (\Throwable $e) {
            return redirect('/ofertas-empleo/eliminados')->with('error', 'No se pudo eliminar definitivamente la oferta de empleo. Intenta de nuevo.');
        }

        // El registro ya no existe: se elimina también su logo para no dejarlo huérfano //
        $this->borrarArchivo($logo);

        return redirect('/ofertas-empleo/eliminados')->with('exito', "{$etiqueta} se eliminó definitivamente, junto con su logo.");
    }

    private function etiqueta(OfertaEmpleo $oferta): string
    {
        return "La oferta «{$oferta->titulo}» de «{$oferta->empresa}»";
    }

    private function relativa(?string $ruta): ?string
    {
        return $ruta ? Str::after($ruta, '/storage/') : null;
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
        $relativa = $this->relativa($ruta);

        if ($relativa) {
            Storage::disk('public')->delete($relativa);
        }
    }

    private function categorias()
    {
        return Categoria::select('id', 'nombre')->orderBy('nombre')->get();
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'categoria_id' => ['required', 'exists:categorias,id'],
            'titulo' => ['required', 'string', 'min:3', 'max:150'],
            'empresa' => ['required', 'string', 'min:2', 'max:150'],
            'descripcion' => ['required', 'string', 'min:10'],
            'ubicacion' => ['required', 'string', 'min:3', 'max:150'],
            'salario' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'tipo_empleo' => ['required', 'in:Tiempo completo,Medio tiempo,Por obra'],
            'fecha_publicacion' => ['required', 'date'],
            'estado' => ['required', 'in:Abierta,Cerrada'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
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
