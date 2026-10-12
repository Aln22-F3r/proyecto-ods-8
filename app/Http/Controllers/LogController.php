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
            'eliminados' => false,
        ]);
    }

    public function eliminados()
    {
        return Inertia::render('Logs/Index', [
            'logs' => Log::onlyTrashed()->with('usuario:id,nombre,apellido')->paginate(5),
            'eliminados' => true,
        ]);
    }

    public function show($id)
    {
        $log = Log::withTrashed()
            ->with('usuario:id,nombre,apellido,correo')
            ->find($id);

        if (!$log) {
            return redirect('/logs')->with('error', 'El log solicitado no existe.');
        }

        $zona = 'America/Mexico_City';

        return Inertia::render('Logs/Show', [
            'log' => [
                'id' => $log->id,
                'usuario' => $log->usuario
                    ? "{$log->usuario->nombre} {$log->usuario->apellido}"
                    : null,
                'correo' => $log->usuario?->correo,
                'accion' => $log->accion,
                'fecha' => Carbon::parse($log->fecha)->format('d/m/Y H:i:s'),
                'ip' => $log->ip,
                'creado' => $log->created_at?->timezone($zona)->format('d/m/Y H:i'),
                'actualizado' => $log->updated_at?->timezone($zona)->format('d/m/Y H:i'),
                'eliminado' => $log->trashed(),
                'fecha_eliminacion' => $log->deleted_at?->timezone($zona)->format('d/m/Y H:i'),
            ],
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
            // Se incluye el usuario actual aunque esté eliminado, para que no desaparezca del select //
            'usuarios' => Usuario::withTrashed()
                ->where(function ($q) use ($log) {
                    $q->whereNull('deleted_at')->orWhere('id', $log->usuario_id);
                })
                ->select('id', 'nombre', 'apellido')
                ->orderBy('nombre')
                ->get(),
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

    // Borrado lógico: solo llena deleted_at, el registro se conserva //
    public function destroy($id)
    {
        $log = Log::with('usuario:id,nombre,apellido')->find($id);

        if (!$log) {
            return redirect('/logs')->with('error', 'El log solicitado no existe o ya fue eliminado.');
        }

        try {
            $log->delete();
        } catch (\Throwable $e) {
            return redirect('/logs')->with('error', 'No se pudo eliminar el log. Intenta de nuevo.');
        }

        return redirect('/logs')->with('exito', "{$this->etiqueta($log)} se eliminó. Puedes restaurarlo desde la lista de eliminados.");
    }

    public function restore($id)
    {
        $log = Log::onlyTrashed()->with('usuario:id,nombre,apellido')->find($id);

        if (!$log) {
            return redirect('/logs/eliminados')->with('error', 'El log solicitado no existe o no está eliminado.');
        }

        try {
            $log->restore();
        } catch (\Throwable $e) {
            return redirect('/logs/eliminados')->with('error', 'No se pudo restaurar el log. Intenta de nuevo.');
        }

        return redirect('/logs/eliminados')->with('exito', "{$this->etiqueta($log)} se restauró y ya aparece en la lista principal.");
    }

    // Borrado físico: solo para registros ya eliminados. Ninguna otra tabla depende de los logs. //
    public function forceDelete($id)
    {
        $log = Log::withTrashed()->with('usuario:id,nombre,apellido')->find($id);

        if (!$log) {
            return redirect('/logs/eliminados')->with('error', 'El log solicitado no existe.');
        }

        if (!$log->trashed()) {
            return redirect('/logs')->with('advertencia', 'El log debe eliminarse primero (borrado lógico) antes de poder borrarlo definitivamente.');
        }

        $etiqueta = $this->etiqueta($log);

        try {
            $log->forceDelete();
        } catch (\Throwable $e) {
            return redirect('/logs/eliminados')->with('error', 'No se pudo eliminar definitivamente el log. Intenta de nuevo.');
        }

        return redirect('/logs/eliminados')->with('exito', "{$etiqueta} se eliminó definitivamente.");
    }

    private function etiqueta(Log $log): string
    {
        $usuario = $log->usuario
            ? "{$log->usuario->nombre} {$log->usuario->apellido}"
            : 'un usuario';

        return "El log «{$log->accion}» de «{$usuario}»";
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
