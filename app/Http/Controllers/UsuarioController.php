<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ManejaErroresBD;
use App\Models\Perfil;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\LogService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    use ManejaErroresBD;

    private const ROL_ADMIN = 'Administrador';   // nombre exacto del rol en BD //

    // ======================= LECTURA ======================= //

    // GET /admin/usuarios: lista paginada de usuarios activos //
    public function index(Request $request)
    {
        Gate::authorize('consultar');   // nivel 1, 2 o 3 //

        $query = Usuario::with(['rol:id,nombre', 'perfil']);

        // Nivel 1 y 2 no ven otros admins en el listado //
        if ($request->user()->nivel < 3) {
            $query->whereHas('rol', fn($q) => $q->where('nombre', '!=', self::ROL_ADMIN));
        }

        return response()->json(['data' => $query->paginate(15)]);
    }

    // GET /admin/usuarios/eliminados: lista de usuarios con borrado lógico //
    public function eliminados(Request $request)
    {
        Gate::authorize('consultar');

        $query = Usuario::onlyTrashed()->with(['rol:id,nombre', 'perfil']);

        // Misma regla: nivel <3 no ve admins eliminados //
        if ($request->user()->nivel < 3) {
            $query->whereHas('rol', fn($q) => $q->where('nombre', '!=', self::ROL_ADMIN));
        }

        return response()->json(['data' => $query->paginate(15)]);
    }

    // GET /admin/usuarios/{id}: detalle de un usuario (incluye eliminados) //
    public function show(Request $request, $id)
    {
        Gate::authorize('consultar');

        $actor = $request->user();

        // Trae usuario (aunque esté borrado) + rol + perfil + conteos de dependencias //
        $usuario = Usuario::withTrashed()
            ->with(['rol:id,nombre', 'perfil'])
            ->withCount(['postulaciones', 'listaDeseos', 'notificaciones', 'logs'])
            ->find($id);

        if (!$usuario) {
            return response()->json(['message' => 'El usuario solicitado no existe.'], 404);
        }

        // Nivel <3 no puede consultar a otros admins (sí a sí mismo) //
        if (
            $usuario->rol?->nombre === self::ROL_ADMIN
            && $actor->nivel < 3
            && $actor->id !== $usuario->id
        ) {
            return response()->json(['message' => 'No tienes permiso para consultar a este usuario.'], 403);
        }

        $zona = 'America/Mexico_City';

        return response()->json([
            'data' => [
                'id'                   => $usuario->id,
                'rol_id'               => $usuario->rol_id,
                'rol'                  => $usuario->rol?->nombre,
                'nivel'                => $usuario->nivel,
                'correo'               => $usuario->correo,
                'fecha_registro'       => $usuario->fecha_registro?->timezone($zona)->format('d/m/Y H:i'),
                'estado'               => $usuario->estado,
                'perfil'               => $usuario->perfil,
                'postulaciones_count'  => $usuario->postulaciones_count,
                'lista_deseos_count'   => $usuario->lista_deseos_count,
                'notificaciones_count' => $usuario->notificaciones_count,
                'logs_count'           => $usuario->logs_count,
                'creado'               => $usuario->created_at?->timezone($zona)->format('d/m/Y H:i'),
                'actualizado'          => $usuario->updated_at?->timezone($zona)->format('d/m/Y H:i'),
                'eliminado'            => $usuario->trashed(),
                'fecha_eliminacion'    => $usuario->deleted_at?->timezone($zona)->format('d/m/Y H:i'),
            ],
        ]);
    }

    // ======================= ESCRITURA ======================= //

    // POST /admin/usuarios: crear usuario + perfil (nivel 2 puede crear candidato/empresa; nivel 3 también admins) //
    public function store(Request $request)
    {
        Gate::authorize('crear');   // nivel 2 o 3 //

        $actor = $request->user();

        $datos = $this->validar($request);

        $nuevoRolEsAdmin = $this->rolEsAdmin($datos['rol_id']);

        // Solo nivel 3 puede crear administradores //
        if ($nuevoRolEsAdmin && $actor->nivel < 3) {
            return response()->json(['message' => 'Solo el nivel 3 puede crear administradores.'], 403);
        }

        // Si el rol no es admin, nivel siempre null //
        if (!$nuevoRolEsAdmin) {
            $datos['nivel'] = null;
        }

        $datosPerfil = $this->extraerDatosPerfil($datos);

        // Quita del payload los campos que no van a la tabla usuarios //
        unset(
            $datos['nombre'],
            $datos['apellido'],
            $datos['telefono'],
            $datos['profesion_oficio'],
            $datos['descripcion'],
            $datos['experiencia'],
            $datos['habilidades'],
            $datos['ciudad'],
            $datos['cv'],
            $datos['foto']
        );

        $datos['password'] = Hash::make($datos['password']);

        try {
            // Usuario + perfil atómicamente //
            $usuario = DB::transaction(function () use ($datos, $datosPerfil) {
                $usuario = Usuario::create($datos);
                $usuario->perfil()->create($datosPerfil);
                return $usuario;
            });
        } catch (QueryException $e) {
            return $this->errorBD($e, 'crear el usuario');
        }

        // Auditoría: usuario creado por admin //
        LogService::registrar(
            $actor->id,
            'crear-usuario',
            "Creó al usuario {$usuario->correo} (rol {$usuario->rol?->nombre}).",
            'usuarios',
            $usuario->id
        );

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'data'    => $usuario->load(['rol:id,nombre', 'perfil']),
        ], 201);
    }

    // PUT /admin/usuarios/{id}: actualizar usuario + perfil //
    public function update(Request $request, $id)
    {
        Gate::authorize('editar');   // nivel 2 o 3 //

        $actor = $request->user();

        $usuario = Usuario::with(['rol', 'perfil'])->find($id);

        if (!$usuario) {
            return response()->json(['message' => 'El usuario solicitado no existe o está eliminado.'], 404);
        }

        $targetEsAdmin = $usuario->rol?->nombre === self::ROL_ADMIN;

        // Nivel 2 no edita administradores //
        if ($targetEsAdmin && $actor->nivel < 3) {
            return response()->json(['message' => 'No tienes permiso para editar administradores.'], 403);
        }

        $datos = $this->validar($request, $usuario->id);

        $nuevoRolEsAdmin = $this->rolEsAdmin($datos['rol_id']);

        // Solo nivel 3 puede ascender a admin a alguien que no lo era //
        if ($nuevoRolEsAdmin && !$targetEsAdmin && $actor->nivel < 3) {
            return response()->json(['message' => 'Solo el nivel 3 puede asignar el rol de Administrador.'], 403);
        }

        // Autoprotección: admin nivel 3 no puede quitarse el rol ni cambiarse el nivel //
        if ($actor->id === $usuario->id && $actor->nivel === 3) {
            if (!$nuevoRolEsAdmin) {
                return response()->json(['message' => 'No puedes quitarte a ti mismo el rol de Administrador.'], 403);
            }

            if ((int) $datos['nivel'] !== (int) $actor->nivel) {
                return response()->json(['message' => 'No puedes cambiar tu propio nivel.'], 403);
            }
        }

        // Si el nuevo rol no es admin, nivel null //
        if (!$nuevoRolEsAdmin) {
            $datos['nivel'] = null;
        }

        $datosPerfil = $this->extraerDatosPerfil($datos);

        unset(
            $datos['nombre'],
            $datos['apellido'],
            $datos['telefono'],
            $datos['profesion_oficio'],
            $datos['descripcion'],
            $datos['experiencia'],
            $datos['habilidades'],
            $datos['ciudad'],
            $datos['cv'],
            $datos['foto']
        );

        // Contraseña: si viene vacía, se conserva la actual //
        if (!empty($datos['password'])) {
            $datos['password'] = Hash::make($datos['password']);
        } else {
            unset($datos['password']);
        }

        $usuario->fill($datos);

        // Si por alguna razón no existía el perfil, se crea vacío //
        $perfil = $usuario->perfil ?? new Perfil(['usuario_id' => $usuario->id]);
        $perfil->fill($datosPerfil);

        // Si nada cambió, no hacemos UPDATE //
        if (!$usuario->isDirty() && !$perfil->isDirty()) {
            return response()->json([
                'message' => 'No se realizaron cambios en el usuario.',
                'data'    => $usuario->load(['rol:id,nombre', 'perfil']),
            ]);
        }

        try {
            DB::transaction(function () use ($usuario, $perfil) {
                $usuario->save();
                $perfil->save();
            });
        } catch (QueryException $e) {
            return $this->errorBD($e, 'actualizar el usuario');
        }

        // Auditoría: usuario actualizado //
        LogService::registrar(
            $actor->id,
            'actualizar-usuario',
            "Actualizó al usuario {$usuario->correo}.",
            'usuarios',
            $usuario->id
        );

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'data'    => $usuario->fresh()->load(['rol:id,nombre', 'perfil']),
        ]);
    }

    // ======================= BORRADO ======================= //

    // DELETE /admin/usuarios/{id}: borrado lógico (el perfil queda intacto en BD) //
    public function destroy(Request $request, $id)
    {
        Gate::authorize('eliminar');   // solo nivel 3 //

        $actor = $request->user();

        $usuario = Usuario::with(['perfil', 'rol'])->find($id);

        if (!$usuario) {
            return response()->json(['message' => 'El usuario solicitado no existe o ya fue eliminado.'], 404);
        }

        // Nadie puede eliminarse a sí mismo (evita lockout) //
        if ($actor->id === $usuario->id) {
            return response()->json(['message' => 'No puedes eliminarte a ti mismo.'], 403);
        }

        $nombre = $usuario->perfil?->nombre ?? 'sin nombre';

        try {
            $usuario->delete();   // soft delete del usuario; perfil intacto //
        } catch (QueryException $e) {
            return $this->errorBD($e, 'eliminar el usuario');
        }

        // Auditoría: borrado lógico //
        LogService::registrar(
            $actor->id,
            'eliminar-usuario',
            "Eliminó lógicamente al usuario '{$nombre}'.",
            'usuarios',
            $usuario->id
        );

        return response()->json([
            'message' => "El usuario '{$nombre}' se eliminó lógicamente. Puedes restaurarlo.",
        ]);
    }

    // PATCH /admin/usuarios/{id}/restaurar: revertir soft delete //
    public function restore(Request $request, $id)
    {
        Gate::authorize('eliminar');

        $usuario = Usuario::onlyTrashed()->find($id);

        if (!$usuario) {
            return response()->json(['message' => 'El usuario solicitado no existe o no está eliminado.'], 404);
        }

        try {
            $usuario->restore();
        } catch (QueryException $e) {
            return $this->errorBD($e, 'restaurar el usuario');
        }

        // Auditoría: restaurar usuario //
        LogService::registrar(
            $request->user()->id,
            'restaurar-usuario',
            "Restauró al usuario {$usuario->correo}.",
            'usuarios',
            $usuario->id
        );

        return response()->json([
            'message' => 'Usuario restaurado correctamente.',
            'data'    => $usuario->fresh()->load(['rol:id,nombre', 'perfil']),
        ]);
    }

    // DELETE /admin/usuarios/{id}/definitivo: borrado físico con limpieza de perfil y archivos //
    public function forceDelete(Request $request, $id)
    {
        Gate::authorize('eliminar');

        $actor = $request->user();

        $usuario = Usuario::withTrashed()->with('perfil')->find($id);

        if (!$usuario) {
            return response()->json(['message' => 'El usuario solicitado no existe.'], 404);
        }

        if ($actor->id === $usuario->id) {
            return response()->json(['message' => 'No puedes eliminarte definitivamente a ti mismo.'], 403);
        }

        // Debe estar soft-deleted primero (política: doble confirmación) //
        if (!$usuario->trashed()) {
            return response()->json(['message' => 'El usuario debe eliminarse primero mediante borrado lógico.'], 409);
        }

        // Dependencias que bloquean el borrado (no tienen cascade en BD) //
        $dependencias = [
            'postulaciones'   => [$usuario->postulaciones()->count(), 'postulación', 'postulaciones'],
            'lista de deseos' => [$usuario->listaDeseos()->count(), 'elemento en la lista de deseos', 'elementos en la lista de deseos'],
            'notificaciones'  => [$usuario->notificaciones()->count(), 'notificación', 'notificaciones'],
        ];

        $detalle = [];

        // Arma el mensaje con las dependencias que sí existen //
        foreach ($dependencias as [$total, $singular, $plural]) {
            if ($total > 0) {
                $detalle[] = $total . ' ' . ($total === 1 ? $singular : $plural);
            }
        }

        if (count($detalle) > 0) {
            return response()->json([
                'message'      => 'No se puede eliminar definitivamente al usuario porque tiene registros asociados: '
                    . implode(', ', $detalle) . '.',
                'dependencias' => $detalle,
            ], 409);
        }

        // Capturar datos ANTES del borrado (para el log) //
        $correoEliminado = $usuario->correo;
        $idEliminado     = $usuario->id;

        try {
            DB::transaction(function () use ($usuario) {
                $usuario->ofertasEmpleo()->update(['usuario_id' => null]);

                if ($perfil = $usuario->perfil) {
                    $perfil->delete();
                }

                $usuario->forceDelete();
            });
        } catch (QueryException $e) {
            return $this->errorBD($e, 'eliminar definitivamente el usuario');
        }

        // Auditoría: borrado físico //
        LogService::registrar(
            $actor->id,
            'eliminar-definitivo-usuario',
            "Eliminó definitivamente al usuario '{$correoEliminado}'.",
            'usuarios',
            $idEliminado
        );

        return response()->json([
            'message' => 'El usuario se eliminó definitivamente, junto con su perfil y sus archivos.',
        ]);
    }

    // ======================= HELPERS ======================= //

    // Filtra del array solo los campos que van a la tabla perfiles //
    private function extraerDatosPerfil(array $datos): array
    {
        return array_intersect_key($datos, array_flip([
            'nombre',
            'apellido',
            'telefono',
            'profesion_oficio',
            'descripcion',
            'experiencia',
            'habilidades',
            'ciudad',
            'cv',
            'foto',
        ]));
    }

    // Devuelve true si el rol_id corresponde a Administrador //
    private function rolEsAdmin(?int $rolId): bool
    {
        if (!$rolId) {
            return false;
        }

        return Rol::find($rolId)?->nombre === self::ROL_ADMIN;
    }

    // Validación compartida por store y update //
    private function validar(Request $request, $id = null): array
    {
        // nivel solo se exige cuando el rol destino es Administrador //
        $esAdmin = $this->rolEsAdmin($request->input('rol_id'));

        return $request->validate([
            'rol_id' => ['required', Rule::exists('roles', 'id')],
            'correo' => [
                'required',
                'email',
                'max:150',
                Rule::unique('usuarios', 'correo')->ignore($id),
            ],
            'password' => [
                $id ? 'nullable' : 'required',   // en update es opcional //
                'string',
                'min:8',
                'max:255',
            ],
            'nivel' => $esAdmin
                ? ['required', 'integer', 'in:1,2,3']
                : ['nullable'],
            'fecha_registro' => ['required', 'date'],
            'estado'         => ['required', 'boolean'],

            'nombre'           => ['required', 'string', 'min:2', 'max:100'],
            'apellido'         => ['nullable', 'string', 'min:2', 'max:100'],
            'telefono'         => ['nullable', 'digits_between:10,20'],
            'profesion_oficio' => ['nullable', 'string', 'max:150'],
            'descripcion'      => ['nullable', 'string'],
            'experiencia'      => ['nullable', 'integer', 'min:0'],
            'habilidades'      => ['nullable', 'string'],
            'ciudad'           => ['nullable', 'string', 'max:100'],
            'cv'               => ['nullable', 'string', 'max:255'],
            'foto'             => ['nullable', 'string', 'max:255'],
        ], [
            'required'       => 'El campo :attribute es obligatorio.',
            'string'         => 'El campo :attribute debe ser texto.',
            'min'            => 'El campo :attribute debe tener al menos :min caracteres.',
            'max'            => 'El campo :attribute no puede tener más de :max caracteres.',
            'email'          => 'El correo no tiene un formato válido.',
            'unique'         => 'Ese :attribute ya está registrado.',
            'exists'         => 'El rol seleccionado no existe.',
            'digits_between' => 'El teléfono debe tener solo números, entre :min y :max dígitos.',
            'date'           => 'La fecha de registro no es válida.',
            'boolean'        => 'El estado no es válido.',
            'integer'        => 'El campo :attribute debe ser un número entero.',
            'in'             => 'El campo :attribute debe ser 1, 2 o 3.',
        ], [
            'rol_id'           => 'rol',
            'correo'           => 'correo',
            'password'         => 'contraseña',
            'nivel'            => 'nivel',
            'telefono'         => 'teléfono',
            'fecha_registro'   => 'fecha de registro',
            'nombre'           => 'nombre',
            'apellido'         => 'apellido',
            'profesion_oficio' => 'profesión u oficio',
        ]);
    }
}
