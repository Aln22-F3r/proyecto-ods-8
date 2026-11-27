<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\LogService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const ROLES_REGISTRO = ['Candidato', 'Empresa'];   // roles permitidos en auto-registro //

    // POST /auth/login: login universal para los 3 roles //
    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'correo'   => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'correo.required'   => 'El correo electrónico es obligatorio.',
            'correo.email'      => 'El correo electrónico no tiene un formato válido.',
            'correo.max'        => 'El correo no puede tener más de :max caracteres.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.max'      => 'La contraseña no puede tener más de :max caracteres.',
        ]);

        $usuario = Usuario::with(['rol:id,nombre', 'perfil'])
            ->where('correo', $credenciales['correo'])
            ->first();

        // Credenciales inválidas: mensaje genérico para no filtrar si el correo existe //
        if (!$usuario || !Hash::check($credenciales['password'], $usuario->password)) {
            throw ValidationException::withMessages([
                'correo' => ['El correo o la contraseña son incorrectos.'],
            ]);
        }

        if (!$usuario->estado) {
            return response()->json([
                'message' => 'Tu cuenta está deshabilitada. Contacta al administrador del sistema.',
            ], 403);
        }

        if (!$usuario->rol) {
            return response()->json([
                'message' => 'Tu cuenta no tiene un rol asignado. Contacta al administrador del sistema.',
            ], 403);
        }

        $token = $usuario->createToken('auth-token')->plainTextToken;   // token Sanctum plano //

        // Auditoría: sesión iniciada //
        LogService::registrar(
            $usuario->id,
            'login',
            "Inicio de sesión con rol {$usuario->rol->nombre}.",
            'usuarios',
            $usuario->id
        );

        return response()->json([
            'message' => 'Inicio de sesión exitoso.',
            'token'   => $token,
            'usuario' => $this->formatearUsuario($usuario),
        ], 200);
    }

    // POST /auth/register: auto-registro de Candidato o Empresa //
    public function register(Request $request)
    {
        $correo = $request->input('correo');

        // Opción B: si el correo pertenece a cuenta soft-deleted, mensaje específico //
        if (is_string($correo) && Usuario::onlyTrashed()->where('correo', $correo)->exists()) {
            return response()->json([
                'message' => 'Ese correo pertenece a una cuenta eliminada. Contacta al administrador del sistema.',
                'errors'  => [
                    'correo' => ['Ese correo pertenece a una cuenta eliminada. Contacta al administrador del sistema.'],
                ],
            ], 422);
        }

        $datos = $request->validate([
            // rol_id solo acepta IDs de Candidato o Empresa (nunca Administrador) //
            'rol_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(
                    fn($q) => $q->whereIn('nombre', self::ROLES_REGISTRO)
                ),
            ],

            'correo'   => ['required', 'email', 'max:150', 'unique:usuarios,correo'],
            'password' => ['required', 'string', 'min:8', 'max:255'],

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
            'rol_id.required'         => 'Debes seleccionar si te registras como candidato o empresa.',
            'rol_id.exists'           => 'El rol seleccionado no es válido para auto-registro.',
            'correo.required'         => 'El correo es obligatorio.',
            'correo.email'            => 'El correo no tiene un formato válido.',
            'correo.unique'           => 'Ese correo ya está registrado.',
            'password.required'       => 'La contraseña es obligatoria.',
            'password.min'            => 'La contraseña debe tener al menos 8 caracteres.',
            'nombre.required'         => 'El nombre es obligatorio.',
            'telefono.digits_between' => 'El teléfono debe tener entre 10 y 20 dígitos.',
            'experiencia.integer'     => 'La experiencia debe ser un número entero.',
        ]);

        // Extrae solo los campos que van a la tabla perfiles //
        $datosPerfil = array_intersect_key($datos, array_flip([
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

        // nivel=null y estado=true porque solo admin tiene nivel y el registro nace activo //
        $datosUsuario = [
            'rol_id'         => $datos['rol_id'],
            'correo'         => $datos['correo'],
            'password'       => Hash::make($datos['password']),
            'nivel'          => null,
            'estado'         => true,
            'fecha_registro' => now(),
        ];

        try {
            // Usuario + perfil en una transacción (rollback si falla el perfil) //
            $usuario = DB::transaction(function () use ($datosUsuario, $datosPerfil) {
                $usuario = Usuario::create($datosUsuario);
                $usuario->perfil()->create($datosPerfil);

                return $usuario;
            });
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'No se pudo completar el registro. Intenta de nuevo.',
            ], 500);
        }

        $usuario->load(['rol:id,nombre', 'perfil']);

        $token = $usuario->createToken('auth-token')->plainTextToken;   // login automático tras registrarse //

        // Auditoría: nuevo registro público //
        LogService::registrar(
            $usuario->id,
            'registro',
            "Nuevo registro como {$usuario->rol->nombre}.",
            'usuarios',
            $usuario->id
        );

        return response()->json([
            'message' => 'Registro exitoso.',
            'token'   => $token,
            'usuario' => $this->formatearUsuario($usuario),
        ], 201);
    }

    // GET /auth/me: datos del usuario autenticado //
    public function me(Request $request)
    {
        $usuario = $request->user();

        // Doble check por si la cuenta fue deshabilitada después de emitir el token //
        if (!$usuario->estado || !$usuario->rol) {
            return response()->json([
                'message' => 'Tu cuenta no está autorizada para acceder a esta información.',
            ], 403);
        }

        $usuario->loadMissing(['rol:id,nombre', 'perfil']);   // carga si no venían precargadas //

        return response()->json([
            'usuario' => $this->formatearUsuario($usuario),
        ], 200);
    }

    // POST /auth/logout: revoca solo el token actual //
    public function logout(Request $request)
    {
        $actor = $request->user();

        // Auditoría: cierre de sesión (antes de borrar el token para tener el ID) //
        LogService::registrar(
            $actor->id,
            'logout',
            'Cerró su sesión.',
            'usuarios',
            $actor->id
        );

        $actor->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ], 200);
    }

    // Formato uniforme de usuario para login/register/me //
    private function formatearUsuario(Usuario $usuario): array
    {
        return [
            'id'     => $usuario->id,
            'correo' => $usuario->correo,
            'rol'    => $usuario->rol?->nombre,
            'nivel'  => $usuario->nivel,
            'estado' => $usuario->estado,
            'perfil' => $usuario->perfil,
        ];
    }
}
