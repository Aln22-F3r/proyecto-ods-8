<?php

namespace App\Http\Controllers;

use App\Models\Transaccion;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class TransaccionController extends Controller
{
    public function index()
    {
        return Inertia::render('Transacciones/Index', [
            'transacciones' => Transaccion::with('usuario:id,nombre,apellido')->paginate(5),
        ]);
    }

    public function create()
    {
        return Inertia::render('Transacciones/Form', [
            'usuarios' => $this->usuarios(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $datos['fecha'] = Carbon::parse($datos['fecha'])->format('Y-m-d H:i:s');

        try {
            Transaccion::create($datos);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo guardar la transacción. Intenta de nuevo.');
        }

        return redirect('/transacciones')->with('exito', 'Transacción creada correctamente.');
    }

    public function edit($id)
    {
        $transaccion = Transaccion::find($id);

        if (!$transaccion) {
            return redirect('/transacciones')->with('error', 'La transacción solicitada no existe. No se puede editar.');
        }

        return Inertia::render('Transacciones/Form', [
            'transaccion' => [
                'id' => $transaccion->id,
                'usuario_id' => $transaccion->usuario_id,
                'tipo_registro' => $transaccion->tipo_registro,
                'descripcion' => $transaccion->descripcion,
                'fecha' => Carbon::parse($transaccion->fecha)->format('Y-m-d\TH:i:s'),
                'estado' => $transaccion->estado,
            ],
            'usuarios' => $this->usuarios(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $transaccion = Transaccion::find($id);

        if (!$transaccion) {
            return redirect('/transacciones')->with('error', 'La transacción solicitada no existe. No se puede actualizar.');
        }

        $datos = $this->validar($request);

        $datos['fecha'] = Carbon::parse($datos['fecha'])->format('Y-m-d H:i:s');

        $transaccion->fill($datos);

        if (!$transaccion->isDirty()) {
            return redirect('/transacciones')->with('advertencia', 'No se realizaron cambios en la transacción.');
        }

        try {
            $transaccion->save();
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo actualizar la transacción. Intenta de nuevo.');
        }

        return redirect('/transacciones')->with('exito', 'Transacción actualizada correctamente.');
    }

    private function usuarios()
    {
        return Usuario::select('id', 'nombre', 'apellido')->orderBy('nombre')->get();
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'usuario_id' => ['required', 'exists:usuarios,id'],
            'tipo_registro' => ['required', 'in:Registro,Publicación,Postulación'],
            'descripcion' => ['required', 'string', 'min:5', 'max:255'],
            'fecha' => ['required', 'date'],
            'estado' => ['required', 'in:Pendiente,Completada'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
            'exists' => 'El usuario seleccionado no es válido.',
            'in' => 'El valor seleccionado en :attribute no es válido.',
            'date' => 'La fecha no es válida.',
        ], [
            'usuario_id' => 'usuario',
            'tipo_registro' => 'tipo de registro',
            'descripcion' => 'descripción',
        ]);
    }
}
