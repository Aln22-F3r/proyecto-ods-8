<?php

namespace App\Http\Controllers;

use App\Models\Log;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class LogController extends Controller
{
    public function index()
    {
        return Inertia::render('Logs/Index', [
            'logs' => Log::with('usuario:id,nombre,apellido')->paginate(5),
        ]);
    }

    public function create()
    {
        return Inertia::render('Logs/Form', [
            'usuarios' => $this->usuarios(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $datos['fecha'] = Carbon::parse($datos['fecha'])->format('Y-m-d H:i:s');

        try {
            Log::create($datos);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo guardar el log. Intenta de nuevo.');
        }

        return redirect('/logs')->with('exito', 'Log creado correctamente.');
    }

    public function edit($id)
    {
        $log = Log::find($id);

        if (!$log) {
            return redirect('/logs')->with('error', 'El log solicitado no existe. No se puede editar.');
        }

        return Inertia::render('Logs/Form', [
            'log' => [
                'id' => $log->id,
                'usuario_id' => $log->usuario_id,
                'accion' => $log->accion,
                'fecha' => Carbon::parse($log->fecha)->format('Y-m-d\TH:i:s'),
                'ip' => $log->ip,
            ],
            'usuarios' => $this->usuarios(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $log = Log::find($id);

        if (!$log) {
            return redirect('/logs')->with('error', 'El log solicitado no existe. No se puede actualizar.');
        }

        $datos = $this->validar($request);

        $datos['fecha'] = Carbon::parse($datos['fecha'])->format('Y-m-d H:i:s');

        $log->fill($datos);

        if (!$log->isDirty()) {
            return redirect('/logs')->with('advertencia', 'No se realizaron cambios en el log.');
        }

        try {
            $log->save();
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo actualizar el log. Intenta de nuevo.');
        }

        return redirect('/logs')->with('exito', 'Log actualizado correctamente.');
    }

    private function usuarios()
    {
        return Usuario::select('id', 'nombre', 'apellido')->orderBy('nombre')->get();
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'usuario_id' => ['required', 'exists:usuarios,id'],
            'accion' => ['required', 'string', 'min:3', 'max:255'],
            'fecha' => ['required', 'date'],
            'ip' => ['required', 'ip', 'max:45'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
            'exists' => 'El usuario seleccionado no es válido.',
            'date' => 'La fecha no es válida.',
            'ip' => 'La IP no tiene un formato válido (IPv4 o IPv6).',
        ], [
            'usuario_id' => 'usuario',
            'accion' => 'acción',
            'ip' => 'IP',
        ]);
    }
}
