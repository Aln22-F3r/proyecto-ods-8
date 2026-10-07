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
            'usuarios' => Usuario::select('id', 'nombre', 'apellido')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
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

        $datos['fecha'] = Carbon::parse($datos['fecha'])->format('Y-m-d H:i:s');

        try {
            Transaccion::create($datos);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo guardar la transacción. Intenta de nuevo.');
        }

        return redirect('/transacciones')->with('exito', 'Transacción creada correctamente.');
    }
}
