<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UsuarioController extends Controller
{
    public function index()
    {
        return Inertia::render('Usuarios/Index', [
            'usuarios' => Usuario::with('rol:id,nombre')->paginate(5),
            'eliminados' => false,
        ]);
    }

    public function eliminados()
    {
        return Inertia::render('Usuarios/Index', [
            'usuarios' => Usuario::onlyTrashed()->with('rol:id,nombre')->paginate(5),
            'eliminados' => true,
        ]);
    }

    public function show($id)
    {
        $usuario = Usuario::withTrashed()
            ->with('rol:id,nombre')
            ->withCount(['postulaciones', 'listaDeseos', 'transacciones', 'loginSociales', 'logs'])
            ->find($id);

        if (!$usuario) {
            return redirect('/usuarios')->with('error', 'El usuario solicitado no existe.');
        }

        $zona = 'America/Mexico_City';

        return Inertia::render('Usuarios/Show', [
            'usuario' => [
                'id' => $usuario->id,
                'rol' => $usuario->rol?->nombre,
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'correo' => $usuario->correo,
                'telefono' => $usuario->telefono,
                'fecha_registro' => Carbon::parse($usuario->fecha_registro)->format('d/m/Y H:i'),
                'estado' => $usuario->estado,
                'perfil' => $usuario->perfil?->profesion_oficio,
                'postulaciones_count' => $usuario->postulaciones_count,
                'lista_deseos_count' => $usuario->lista_deseos_count,
                'transacciones_count' => $usuario->transacciones_count,
                'login_sociales_count' => $usuario->login_sociales_count,
                'logs_count' => $usuario->logs_count,
                'creado' => $usuario->created_at?->timezone($zona)->format('d/m/Y H:i'),
                'actualizado' => $usuario->updated_at?->timezone($zona)->format('d/m/Y H:i'),
                'eliminado' => $usuario->trashed(),
                'fecha_eliminacion' => $usuario->deleted_at?->timezone($zona)->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Usuarios/Form', [
            'roles' => $this->roles(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $datos['password'] = Hash::make($datos['password']);
        $datos['fecha_registro'] = Carbon::parse($datos['fecha_registro'])->format('Y-m-d H:i:s');

        try {
            Usuario::create($datos);
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo guardar el usuario. Intenta de nuevo.');
        }

        return redirect('/usuarios')->with('exito', 'Usuario creado correctamente.');
    }

    public function edit($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return redirect('/usuarios')->with('error', 'El usuario solicitado no existe. No se puede editar.');
        }

        return Inertia::render('Usuarios/Form', [
            'usuario' => [
                'id' => $usuario->id,
                'rol_id' => $usuario->rol_id,
                'nombre' => $usuario->nombre,
                'apellido' => $usuario->apellido,
                'correo' => $usuario->correo,
                'telefono' => $usuario->telefono,
                'fecha_registro' => Carbon::parse($usuario->fecha_registro)->format('Y-m-d\TH:i:s'),
                'estado' => $usuario->estado,
            ],
            // Roles activos, más el rol actual del usuario aunque esté eliminado, para que no desaparezca del select //
            'roles' => Rol::withTrashed()
                ->where(function ($q) use ($usuario) {
                    $q->whereNull('deleted_at')->orWhere('id', $usuario->rol_id);
                })
                ->select('id', 'nombre')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return redirect('/usuarios')->with('error', 'El usuario solicitado no existe. No se puede actualizar.');
        }

        $datos = $this->validar($request, $usuario->id);

        $datos['fecha_registro'] = Carbon::parse($datos['fecha_registro'])->format('Y-m-d H:i:s');

        // Contraseña vacía = se conserva la actual //
        if (!empty($datos['password'])) {
            $datos['password'] = Hash::make($datos['password']);
        } else {
            unset($datos['password']);
        }

        $usuario->fill($datos);

        if (!$usuario->isDirty()) {
            return redirect('/usuarios')->with('advertencia', 'No se realizaron cambios en el usuario.');
        }

        try {
            $usuario->save();
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo actualizar el usuario. Intenta de nuevo.');
        }

        return redirect('/usuarios')->with('exito', 'Usuario actualizado correctamente.');
    }

    // Borrado lógico: solo llena deleted_at, el registro se conserva //
    public function destroy($id)
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return redirect('/usuarios')->with('error', 'El usuario solicitado no existe o ya fue eliminado.');
        }

        try {
            $usuario->delete();
        } catch (\Throwable $e) {
            return redirect('/usuarios')->with('error', 'No se pudo eliminar el usuario. Intenta de nuevo.');
        }

        return redirect('/usuarios')->with('exito', "El usuario «{$usuario->nombre} {$usuario->apellido}» se eliminó. Puedes restaurarlo desde la lista de eliminados.");
    }

    public function restore($id)
    {
        $usuario = Usuario::onlyTrashed()->find($id);

        if (!$usuario) {
            return redirect('/usuarios/eliminados')->with('error', 'El usuario solicitado no existe o no está eliminado.');
        }

        try {
            $usuario->restore();
        } catch (\Throwable $e) {
            return redirect('/usuarios/eliminados')->with('error', 'No se pudo restaurar el usuario. Intenta de nuevo.');
        }

        return redirect('/usuarios/eliminados')->with('exito', "El usuario «{$usuario->nombre} {$usuario->apellido}» se restauró y ya aparece en la lista principal.");
    }

    // Borrado físico: solo para registros ya eliminados y sin dependencias //
    public function forceDelete($id)
    {
        $usuario = Usuario::withTrashed()->find($id);

        if (!$usuario) {
            return redirect('/usuarios/eliminados')->with('error', 'El usuario solicitado no existe.');
        }

        if (!$usuario->trashed()) {
            return redirect('/usuarios')->with('advertencia', 'El usuario debe eliminarse primero (borrado lógico) antes de poder borrarlo definitivamente.');
        }

        // Se cuentan también los registros eliminados lógicamente, porque siguen apuntando al usuario en la base de datos. //
        // La lista de deseos no tiene borrado lógico, por eso se cuenta sin withTrashed(). //
        $dependencias = [
            'perfil' => [$usuario->perfil()->withTrashed()->count(), 'perfil', 'perfiles'],
            'postulaciones' => [$usuario->postulaciones()->withTrashed()->count(), 'postulación', 'postulaciones'],
            'lista de deseos' => [$usuario->listaDeseos()->count(), 'elemento en la lista de deseos', 'elementos en la lista de deseos'],
            'transacciones' => [$usuario->transacciones()->withTrashed()->count(), 'transacción', 'transacciones'],
            'login sociales' => [$usuario->loginSociales()->withTrashed()->count(), 'login social', 'login sociales'],
            'logs' => [$usuario->logs()->withTrashed()->count(), 'log', 'logs'],
        ];

        $detalle = [];
        foreach ($dependencias as [$total, $singular, $plural]) {
            if ($total > 0) {
                $detalle[] = $total . ' ' . ($total === 1 ? $singular : $plural);
            }
        }

        if (count($detalle) > 0) {
            return redirect('/usuarios/eliminados')->with('error', "No se puede eliminar definitivamente al usuario «{$usuario->nombre} {$usuario->apellido}»: está siendo usado por " . implode(', ', $detalle) . ' (activos o eliminados). Elimina primero esos registros.');
        }

        try {
            $usuario->forceDelete();
        } catch (\Throwable $e) {
            return redirect('/usuarios/eliminados')->with('error', 'No se pudo eliminar definitivamente el usuario. Intenta de nuevo.');
        }

        return redirect('/usuarios/eliminados')->with('exito', "El usuario «{$usuario->nombre} {$usuario->apellido}» se eliminó definitivamente.");
    }

    private function roles()
    {
        return Rol::select('id', 'nombre')->orderBy('nombre')->get();
    }

    private function validar(Request $request, $id = null): array
    {
        return $request->validate([
            'rol_id' => ['required', 'exists:roles,id'],
            'nombre' => ['required', 'string', 'min:2', 'max:100'],
            'apellido' => ['required', 'string', 'min:2', 'max:100'],
            'correo' => [
                'required',
                'email',
                'max:150',
                Rule::unique('usuarios', 'correo')->ignore($id),
            ],
            'password' => [$id ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
            'telefono' => ['required', 'digits_between:10,20'],
            'fecha_registro' => ['required', 'date'],
            'estado' => ['required', 'boolean'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
            'email' => 'El correo no tiene un formato válido.',
            'unique' => 'Ese :attribute ya está registrado.',
            'exists' => 'El rol seleccionado no es válido.',
            'digits_between' => 'El teléfono debe tener solo números, entre :min y :max dígitos.',
            'date' => 'La fecha de registro no es válida.',
            'boolean' => 'El estado no es válido.',
        ], [
            'rol_id' => 'rol',
            'correo' => 'correo',
            'password' => 'contraseña',
            'telefono' => 'teléfono',
            'fecha_registro' => 'fecha de registro',
        ]);
    }
}
