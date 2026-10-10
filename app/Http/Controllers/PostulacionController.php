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
            'usuarios' => $this->usuarios(),
            'ofertas' => $this->ofertas(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $datos['fecha_postulacion'] = Carbon::parse($datos['fecha_postulacion'])->format('Y-m-d H:i:s');

        try {
            Postulacion::create($datos);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo guardar la postulación. Intenta de nuevo.');
        }

        return redirect('/postulaciones')->with('exito', 'Postulación creada correctamente.');
    }

    public function edit($id)
    {
        $postulacion = Postulacion::find($id);

        if (!$postulacion) {
            return redirect('/postulaciones')->with('error', 'La postulación solicitada no existe. No se puede editar.');
        }

        return Inertia::render('Postulaciones/Form', [
            'postulacion' => [
                'id' => $postulacion->id,
                'usuario_id' => $postulacion->usuario_id,
                'oferta_empleo_id' => $postulacion->oferta_empleo_id,
                'fecha_postulacion' => Carbon::parse($postulacion->fecha_postulacion)->format('Y-m-d\TH:i:s'),
                'estado' => $postulacion->estado,
                'comentario' => $postulacion->comentario,
            ],
            'usuarios' => $this->usuarios(),
            'ofertas' => $this->ofertas(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $postulacion = Postulacion::find($id);

        if (!$postulacion) {
            return redirect('/postulaciones')->with('error', 'La postulación solicitada no existe. No se puede actualizar.');
        }

        $datos = $this->validar($request, $postulacion->id);

        $datos['fecha_postulacion'] = Carbon::parse($datos['fecha_postulacion'])->format('Y-m-d H:i:s');

        $postulacion->fill($datos);

        if (!$postulacion->isDirty()) {
            return redirect('/postulaciones')->with('advertencia', 'No se realizaron cambios en la postulación.');
        }

        try {
            $postulacion->save();
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo actualizar la postulación. Intenta de nuevo.');
        }

        return redirect('/postulaciones')->with('exito', 'Postulación actualizada correctamente.');
    }

    private function usuarios()
    {
        return Usuario::select('id', 'nombre', 'apellido')->orderBy('nombre')->get();
    }

    private function ofertas()
    {
        return OfertaEmpleo::select('id', 'titulo', 'empresa')->orderBy('titulo')->get();
    }

    private function validar(Request $request, $id = null): array
    {
        return $request->validate([
            'usuario_id' => ['required', 'exists:usuarios,id'],
            'oferta_empleo_id' => [
                'required',
                'exists:ofertas_empleo,id',
                Rule::unique('postulaciones', 'oferta_empleo_id')
                    ->where('usuario_id', $request->input('usuario_id'))
                    ->ignore($id),
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
    }
}
