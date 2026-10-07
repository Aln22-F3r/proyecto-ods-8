<?php

namespace App\Http\Controllers;

use App\Models\OfertaEmpleo;
use App\Models\Postulacion;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PostulacionController extends Controller
{
    public function index()
    {
        return Inertia::render('Postulaciones/Index', [
            'postulaciones' => Postulacion::with([
                'usuario:id,nombre,apellido',
                'ofertaEmpleo:id,titulo',
            ])->paginate(5),
        ]);
    }

    public function create()
    {
        return Inertia::render('Postulaciones/Form', [
            'usuarios' => Usuario::select('id', 'nombre', 'apellido')->orderBy('nombre')->get(),
            'ofertas' => OfertaEmpleo::select('id', 'titulo', 'empresa')->orderBy('titulo')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'usuario_id' => ['required', 'exists:usuarios,id'],
            'oferta_empleo_id' => [
                'required',
                'exists:ofertas_empleo,id',
                Rule::unique('postulaciones', 'oferta_empleo_id')
                    ->where('usuario_id', $request->input('usuario_id')),
            ],
            'fecha_postulacion' => ['required', 'date'],
            'estado' => ['required', 'in:En revisión,Aceptada,Rechazada'],
            'comentario' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
            'usuario_id.exists' => 'El usuario seleccionado no es válido.',
            'oferta_empleo_id.exists' => 'La oferta seleccionada no es válida.',
            'oferta_empleo_id.unique' => 'Este usuario ya se postuló a esa oferta.',
            'date' => 'La fecha de postulación no es válida.',
            'in' => 'El estado seleccionado no es válido.',
        ], [
            'usuario_id' => 'usuario',
            'oferta_empleo_id' => 'oferta de empleo',
            'fecha_postulacion' => 'fecha de postulación',
        ]);

        $datos['fecha_postulacion'] = Carbon::parse($datos['fecha_postulacion'])->format('Y-m-d H:i:s');

        try {
            Postulacion::create($datos);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo guardar la postulación. Intenta de nuevo.');
        }

        return redirect('/postulaciones')->with('exito', 'Postulación creada correctamente.');
    }
}
