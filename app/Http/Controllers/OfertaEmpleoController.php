<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\OfertaEmpleo;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class OfertaEmpleoController extends Controller
{
    public function index()
    {
        return Inertia::render('OfertasEmpleo/Index', [
            'ofertas' => OfertaEmpleo::with('categoria:id,nombre')->paginate(5),
        ]);
    }

    public function create()
    {
        return Inertia::render('OfertasEmpleo/Form', [
            'categorias' => Categoria::select('id', 'nombre')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
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

        $datos['fecha_publicacion'] = Carbon::parse($datos['fecha_publicacion'])->format('Y-m-d H:i:s');

        try {
            DB::transaction(function () use ($request, $datos) {
                $oferta = OfertaEmpleo::create(collect($datos)->except('logo')->all());

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
}
