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
            'eliminados' => false,
        ]);
    }

    public function eliminados()
    {
        return Inertia::render('Transacciones/Index', [
            'transacciones' => Transaccion::onlyTrashed()->with('usuario:id,nombre,apellido')->paginate(5),
            'eliminados' => true,
        ]);
    }

    public function show($id)
    {
        $transaccion = Transaccion::withTrashed()
            ->with('usuario:id,nombre,apellido,correo')
            ->find($id);

        if (!$transaccion) {
            return redirect('/transacciones')->with('error', 'La transacción solicitada no existe.');
        }

        $zona = 'America/Mexico_City';

        return Inertia::render('Transacciones/Show', [
            'transaccion' => [
                'id' => $transaccion->id,
                'usuario' => $transaccion->usuario
                    ? "{$transaccion->usuario->nombre} {$transaccion->usuario->apellido}"
                    : null,
                'correo' => $transaccion->usuario?->correo,
                'tipo_registro' => $transaccion->tipo_registro,
                'descripcion' => $transaccion->descripcion,
                'fecha' => Carbon::parse($transaccion->fecha)->format('d/m/Y H:i'),
                'estado' => $transaccion->estado,
                'creado' => $transaccion->created_at?->timezone($zona)->format('d/m/Y H:i'),
                'actualizado' => $transaccion->updated_at?->timezone($zona)->format('d/m/Y H:i'),
                'eliminado' => $transaccion->trashed(),
                'fecha_eliminacion' => $transaccion->deleted_at?->timezone($zona)->format('d/m/Y H:i'),
            ],
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
            // Se incluye el usuario actual aunque esté eliminado, para que no desaparezca del select //
            'usuarios' => Usuario::withTrashed()
                ->where(function ($q) use ($transaccion) {
                    $q->whereNull('deleted_at')->orWhere('id', $transaccion->usuario_id);
                })
                ->select('id', 'nombre', 'apellido')
                ->orderBy('nombre')
                ->get(),
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

    // Borrado lógico: solo llena deleted_at, el registro se conserva //
    public function destroy($id)
    {
        $transaccion = Transaccion::with('usuario:id,nombre,apellido')->find($id);

        if (!$transaccion) {
            return redirect('/transacciones')->with('error', 'La transacción solicitada no existe o ya fue eliminada.');
        }

        try {
            $transaccion->delete();
        } catch (\Throwable $e) {
            return redirect('/transacciones')->with('error', 'No se pudo eliminar la transacción. Intenta de nuevo.');
        }

        return redirect('/transacciones')->with('exito', "{$this->etiqueta($transaccion)} se eliminó. Puedes restaurarla desde la lista de eliminadas.");
    }

    public function restore($id)
    {
        $transaccion = Transaccion::onlyTrashed()->with('usuario:id,nombre,apellido')->find($id);

        if (!$transaccion) {
            return redirect('/transacciones/eliminados')->with('error', 'La transacción solicitada no existe o no está eliminada.');
        }

        try {
            $transaccion->restore();
        } catch (\Throwable $e) {
            return redirect('/transacciones/eliminados')->with('error', 'No se pudo restaurar la transacción. Intenta de nuevo.');
        }

        return redirect('/transacciones/eliminados')->with('exito', "{$this->etiqueta($transaccion)} se restauró y ya aparece en la lista principal.");
    }

    // Borrado físico: solo para registros ya eliminados. Ninguna otra tabla depende de las transacciones. //
    public function forceDelete($id)
    {
        $transaccion = Transaccion::withTrashed()->with('usuario:id,nombre,apellido')->find($id);

        if (!$transaccion) {
            return redirect('/transacciones/eliminados')->with('error', 'La transacción solicitada no existe.');
        }

        if (!$transaccion->trashed()) {
            return redirect('/transacciones')->with('advertencia', 'La transacción debe eliminarse primero (borrado lógico) antes de poder borrarla definitivamente.');
        }

        $etiqueta = $this->etiqueta($transaccion);

        try {
            $transaccion->forceDelete();
        } catch (\Throwable $e) {
            return redirect('/transacciones/eliminados')->with('error', 'No se pudo eliminar definitivamente la transacción. Intenta de nuevo.');
        }

        return redirect('/transacciones/eliminados')->with('exito', "{$etiqueta} se eliminó definitivamente.");
    }

    private function etiqueta(Transaccion $transaccion): string
    {
        $usuario = $transaccion->usuario
            ? "{$transaccion->usuario->nombre} {$transaccion->usuario->apellido}"
            : 'un usuario';

        return "La transacción «{$transaccion->tipo_registro}» de «{$usuario}»";
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
