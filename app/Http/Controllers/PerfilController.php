<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PerfilController extends Controller
{
    private const ROL_ADMIN = 'Administrador';        // nombre exacto del rol admin //
    private const ZONA      = 'America/Mexico_City';  // zona horaria para fechas //

    // ================== ENDPOINTS DEL DUEÑO ================== //

    // GET /perfil: devuelve el perfil del usuario autenticado (cualquier rol) //
    public function miPerfil(Request $request): JsonResponse
    {
        $perfil = Perfil::with(['usuario.rol:id,nombre'])
            ->where('usuario_id', $request->user()->id)
            ->first();

        if (!$perfil) {
            return response()->json(['message' => 'No tienes un perfil registrado.'], 404);
        }

        return response()->json(['data' => $this->formatearPerfil($perfil)]);
    }

    // PUT /perfil: actualiza el perfil del usuario autenticado //
    public function actualizarMiPerfil(Request $request): JsonResponse
    {
        $perfil = Perfil::with(['usuario.rol:id,nombre'])
            ->where('usuario_id', $request->user()->id)
            ->first();

        if (!$perfil) {
            return response()->json(['message' => 'No tienes un perfil registrado.'], 404);
        }

        return $this->procesarActualizacion($request, $perfil);
    }

    // ================== ADMIN: LECTURA ================== //

    // GET /admin/perfiles: lista perfiles de usuarios activos //
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('consultar');   // nivel 1, 2 o 3 //

        // whereHas('usuario') oculta perfiles cuyos usuarios fueron soft-deleted //
        $query = Perfil::with(['usuario.rol:id,nombre'])->whereHas('usuario');

        // Nivel <3 no ve perfiles de admins //
        if ($request->user()->nivel < 3) {
            $query->whereHas('usuario.rol', fn($q) => $q->where('nombre', '!=', self::ROL_ADMIN));
        }

        return response()->json(['data' => $query->paginate(15)]);
    }

    // GET /admin/perfiles/eliminados: perfiles cuyos usuarios están soft-deleted (solo nivel 3) //
    public function eliminados(Request $request): JsonResponse
    {
        Gate::authorize('eliminar');   // solo nivel 3 //

        $query = Perfil::with([
            // withTrashed para poder ver los datos del usuario borrado //
            'usuario' => fn($q) => $q->withTrashed()->select('id', 'correo', 'rol_id', 'estado', 'deleted_at'),
            'usuario.rol:id,nombre',
        ])
            ->whereHas('usuario', fn($q) => $q->onlyTrashed());   // solo perfiles de usuarios borrados //

        return response()->json(['data' => $query->paginate(15)]);
    }

    // GET /admin/perfiles/{id}: detalle de un perfil activo //
    public function show(Request $request, $id): JsonResponse
    {
        $actor = $request->user();

        $perfil = Perfil::with(['usuario.rol:id,nombre'])->find($id);

        if (!$perfil) {
            return response()->json(['message' => 'El perfil solicitado no existe o su usuario está eliminado.'], 404);
        }

        if (!$this->puedeVer($actor, $perfil)) {
            return response()->json(['message' => 'No tienes permiso para consultar este perfil.'], 403);
        }

        return response()->json(['data' => $this->formatearPerfil($perfil)]);
    }

    // ================== ADMIN: ESCRITURA ================== //

    // PUT /admin/perfiles/{id}: editar perfil de otro usuario con reglas por nivel //
    public function update(Request $request, $id): JsonResponse
    {
        $perfil = Perfil::with(['usuario.rol:id,nombre'])->find($id);

        if (!$perfil) {
            return response()->json(['message' => 'El perfil solicitado no existe o su usuario está eliminado.'], 404);
        }

        return $this->procesarActualizacion($request, $perfil);
    }

    // ================== LÓGICA COMPARTIDA ================== //

    // Actualización compartida entre 'update' y 'actualizarMiPerfil' //
    private function procesarActualizacion(Request $request, Perfil $perfil): JsonResponse
    {
        $actor = $request->user();

        if (!$this->puedeEditar($actor, $perfil)) {
            return response()->json(['message' => 'No tienes permiso para modificar este perfil.'], 403);
        }

        $datos = $this->validar($request);

        // Solo campos de texto; cv y foto se manejan aparte por ser archivos //
        $perfil->fill(collect($datos)->except(['cv', 'foto'])->all());

        $archivoNuevo = $request->hasFile('cv') || $request->hasFile('foto');

        // Sin cambios de texto ni archivos → no hacer UPDATE //
        if (!$perfil->isDirty() && !$archivoNuevo) {
            return response()->json([
                'message' => 'No se realizaron cambios en el perfil.',
                'data'    => $this->formatearPerfil($perfil->fresh(['usuario.rol:id,nombre'])),
            ]);
        }

        $archivosNuevos = [];      // para rollback si falla el save //
        $archivosAnteriores = [];  // para borrar tras guardar con éxito //

        try {
            // CV nuevo → guardar y encolar borrado del anterior //
            if ($request->hasFile('cv')) {
                $cv = $request->file('cv');
                $nombreCv = 'Perfil_' . $perfil->id . '_cv_' . Str::uuid() . '.' . $cv->extension();
                $rutaCv = $cv->storeAs('cv', $nombreCv, 'public');
                $archivosNuevos[] = $rutaCv;
                $archivosAnteriores[] = $perfil->cv;
                $perfil->cv = Storage::url($rutaCv);
            }

            // Foto nueva → mismo patrón //
            if ($request->hasFile('foto')) {
                $foto = $request->file('foto');
                $nombreFoto = 'Perfil_' . $perfil->id . '_' . Str::uuid() . '.' . $foto->extension();
                $rutaFoto = $foto->storeAs('imagenes', $nombreFoto, 'public');
                $archivosNuevos[] = $rutaFoto;
                $archivosAnteriores[] = $perfil->foto;
                $perfil->foto = Storage::url($rutaFoto);
            }

            $perfil->save();
        } catch (Throwable $e) {
            report($e);

            // Rollback: borrar archivos recién guardados //
            foreach ($archivosNuevos as $archivo) {
                Storage::disk('public')->delete($archivo);
            }

            return response()->json(['message' => 'No se pudo actualizar el perfil. Intenta de nuevo.'], 500);
        }

        // Borrar archivos anteriores solo si el save fue exitoso //
        foreach ($archivosAnteriores as $archivo) {
            $this->borrarArchivo($archivo);
        }

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'data'    => $this->formatearPerfil($perfil->fresh(['usuario.rol:id,nombre'])),
        ]);
    }

    // ================== AUTORIZACIÓN ================== //

    // Reglas de quién puede VER un perfil (dueño o admin con nivel) //
    private function puedeVer(Usuario $actor, Perfil $perfil): bool
    {
        if ($actor->id === $perfil->usuario_id) {
            return true;   // dueño siempre ve el suyo //
        }

        if ($actor->rol?->nombre !== self::ROL_ADMIN) {
            return false;  // no admin no ve perfiles ajenos //
        }

        if ($actor->nivel >= 3) {
            return true;   // nivel 3 ve todo //
        }

        // Nivel 1 y 2 no ven perfiles de admins //
        return $perfil->usuario?->rol?->nombre !== self::ROL_ADMIN;
    }

    // Reglas de quién puede EDITAR un perfil (dueño o admin con nivel) //
    private function puedeEditar(Usuario $actor, Perfil $perfil): bool
    {
        if ($actor->id === $perfil->usuario_id) {
            return true;
        }

        if ($actor->rol?->nombre !== self::ROL_ADMIN) {
            return false;
        }

        if ($actor->nivel < 2) {
            return false;  // nivel 1 no edita //
        }

        // Nivel 2 no edita perfiles de admins //
        $targetEsAdmin = $perfil->usuario?->rol?->nombre === self::ROL_ADMIN;

        return !($targetEsAdmin && $actor->nivel < 3);
    }

    // ================== HELPERS ================== //

    // Estructura uniforme de respuesta para un perfil //
    private function formatearPerfil(Perfil $perfil): array
    {
        return [
            'id'                => $perfil->id,
            'usuario_id'        => $perfil->usuario_id,
            'correo'            => $perfil->usuario?->correo,
            'rol'               => $perfil->usuario?->rol?->nombre,
            'usuario_eliminado' => (bool) $perfil->usuario?->trashed(),  // flag útil en admin //
            'nombre'            => $perfil->nombre,
            'apellido'          => $perfil->apellido,
            'telefono'          => $perfil->telefono,
            'profesion_oficio'  => $perfil->profesion_oficio,
            'descripcion'       => $perfil->descripcion,
            'experiencia'       => $perfil->experiencia,
            'habilidades'       => $perfil->habilidades,
            'ciudad'            => $perfil->ciudad,
            'cv'                => $this->urlSiExiste($perfil->cv),
            'foto'              => $this->urlSiExiste($perfil->foto),
            'creado'            => $perfil->created_at?->timezone(self::ZONA)->format('d/m/Y H:i'),
            'actualizado'       => $perfil->updated_at?->timezone(self::ZONA)->format('d/m/Y H:i'),
        ];
    }

    // /storage/cv/x.pdf → cv/x.pdf //
    private function relativa(?string $ruta): ?string
    {
        return $ruta ? Str::after($ruta, '/storage/') : null;
    }

    // Devuelve URL pública si el archivo existe en disco, si no null //
    private function urlSiExiste(?string $ruta): ?string
    {
        $relativa = $this->relativa($ruta);

        return ($relativa && Storage::disk('public')->exists($relativa))
            ? Storage::url($relativa)
            : null;
    }

    // Borra un archivo del disco 'public' si existe //
    private function borrarArchivo(?string $ruta): void
    {
        $relativa = $this->relativa($ruta);

        if ($relativa) {
            Storage::disk('public')->delete($relativa);
        }
    }

    // Validación de actualización: todos 'sometimes' porque PUT/PATCH permiten parciales //
    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre'           => ['sometimes', 'required', 'string', 'min:2', 'max:100'],
            'apellido'         => ['sometimes', 'nullable', 'string', 'min:2', 'max:100'],
            'telefono'         => ['sometimes', 'nullable', 'digits_between:10,20'],
            'profesion_oficio' => ['sometimes', 'nullable', 'string', 'min:3', 'max:100'],
            'descripcion'      => ['sometimes', 'nullable', 'string', 'min:10'],
            'experiencia'      => ['sometimes', 'nullable', 'integer', 'min:0', 'max:60'],
            'habilidades'      => ['sometimes', 'nullable', 'string', 'min:3'],
            'ciudad'           => ['sometimes', 'nullable', 'string', 'min:2', 'max:100'],
            'cv'               => ['sometimes', 'nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'foto'             => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'required'        => 'El campo :attribute es obligatorio.',
            'string'          => 'El campo :attribute debe ser texto.',
            'min'             => 'El campo :attribute debe tener al menos :min caracteres.',
            'max'             => 'El campo :attribute no puede tener más de :max caracteres.',
            'integer'         => 'La experiencia debe ser un número entero.',
            'experiencia.min' => 'La experiencia no puede ser negativa.',
            'experiencia.max' => 'La experiencia no puede ser mayor a :max años.',
            'file'            => 'El campo :attribute debe ser un archivo.',
            'image'           => 'La foto debe ser una imagen.',
            'cv.mimes'        => 'El CV debe ser un archivo PDF o Word (pdf, doc, docx).',
            'cv.max'          => 'El CV no puede pesar más de 5 MB.',
            'foto.mimes'      => 'La foto debe ser de tipo jpg, jpeg, png o webp.',
            'foto.max'        => 'La foto no puede pesar más de 2 MB.',
            'digits_between'  => 'El teléfono debe tener entre :min y :max dígitos.',
        ], [
            'nombre'           => 'nombre',
            'apellido'         => 'apellido',
            'telefono'         => 'teléfono',
            'profesion_oficio' => 'profesión u oficio',
            'descripcion'      => 'descripción',
            'experiencia'      => 'experiencia',
            'ciudad'           => 'ciudad',
            'cv'               => 'CV',
            'foto'             => 'foto',
        ]);
    }
}
