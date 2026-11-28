<?php

namespace App\Http\Controllers;

use App\Models\OfertaEmpleo;
use App\Models\Usuario;
use App\Models\Postulacion;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class OfertaEmpleoController extends Controller
{
    // ======================= LECTURA ======================= //

    // GET: candidato ve abiertas; empresa ve las suyas; admin ve todas con filtros opcionales //
    public function index(Request $request)
    {
        $this->authorize('viewAny', OfertaEmpleo::class);

        $actor = $request->user('sanctum'); // Busca explícitamente usando Sanctum en rutas públicas //
        $rol   = $actor->rol?->nombre;

        $query = OfertaEmpleo::with([
            'categoria:id,nombre',
            'usuario:id,correo,rol_id',
            'usuario.perfil:usuario_id,nombre',
        ])->withCount(['postulaciones', 'listaDeseos']);

        // Candidato: solo ofertas abiertas //
        if ($rol === 'Candidato') {
            $query->where('estado', 'Abierta');
        }

        // Empresa: solo las suyas //
        if ($rol === 'Empresa') {
            $query->where('usuario_id', $actor->id);
        }

        // Filtros comunes (candidato, empresa y admin) //
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);   // todos pueden filtrar por categoría //
        }

        // Filtros exclusivos del admin //
        if ($rol === 'Administrador') {
            if ($request->filled('estado'))     $query->where('estado', $request->estado);
            if ($request->filled('usuario_id')) $query->where('usuario_id', $request->usuario_id);
        }

        return response()->json(
            $query->orderByDesc('fecha_publicacion')->paginate(15)
        );
    }

    public function publicIndex(Request $request)
    {
        $query = OfertaEmpleo::with([
            'categoria:id,nombre',
            'usuario:id,correo,rol_id',
            'usuario.perfil:usuario_id,nombre',
        ])
            ->withCount(['postulaciones', 'listaDeseos'])
            ->where('estado', 'Abierta')                  // Solo ofertas activas //
            ->orderByDesc('destacada')                    // Las de pago aparecen primero //
            ->orderByDesc('fecha_publicacion');

        // Filtro opcional por categoría para la vista pública //
        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        return response()->json($query->paginate(12)); // 12 por página para el grid de React //
    }

    // GET /admin/ofertas/eliminados: solo admin nivel 3, lista soft-deleted //
    public function eliminados(Request $request)
    {
        Gate::authorize('eliminar');   // gate admin nivel 3 ya existente en AppServiceProvider //

        $query = OfertaEmpleo::onlyTrashed()
            ->with([
                'categoria:id,nombre',
                'usuario:id,correo,rol_id',
                'usuario.perfil:usuario_id,nombre',
            ])
            ->withCount(['postulaciones', 'listaDeseos'])
            ->orderByDesc('fecha_publicacion');

        return response()->json($query->paginate(15));
    }

    // GET: detalle de una oferta (soporta invitados, candidatos y empresas) //
    public function show(Request $request, $id)
    {
        $oferta = OfertaEmpleo::withTrashed()
            ->with([
                'categoria:id,nombre',
                'usuario:id,correo,rol_id',
                'usuario.perfil:usuario_id,nombre',
            ])
            ->withCount(['postulaciones', 'listaDeseos'])
            ->find($id);

        if (!$oferta) {
            return response()->json(['message' => 'La oferta de empleo solicitada no existe.'], 404);
        }

        $actor = $request->user('sanctum'); // Extrae el usuario por el token de Sanctum //

        // 1. Verificación de acceso (Policy o Invitado) //
        if ($actor) {
            // Le pasamos el actor explícitamente al Gate para que no busque en el guard 'web' //
            Gate::forUser($actor)->authorize('view', $oferta);
        } else {
            // Si es un invitado (sin token), validamos que esté abierta y no eliminada //
            if ($oferta->trashed() || $oferta->estado !== 'Abierta') {
                return response()->json(['message' => 'La oferta no está disponible.'], 404);
            }
        }

        // 2. Formatear la oferta base //
        $data = $this->formatear($oferta);

        // 3. Agregar contexto si el usuario está logueado //
        if ($actor) {
            $rol = $actor->rol?->nombre;

            // Si es Candidato: verificamos si ya se postuló //
            if ($rol === 'Candidato') {
                $postulacion = Postulacion::withTrashed()
                    ->where('usuario_id', $actor->id)
                    ->where('oferta_empleo_id', $oferta->id)
                    ->first();

                $data['ya_postulado'] = $postulacion && !$postulacion->trashed();
                $data['mi_postulacion_id'] = $postulacion?->id;
                $data['mi_postulacion_estado'] = $postulacion?->estado;
            }

            // Si es Empresa: verificamos si es la dueña de la oferta //
            if ($rol === 'Empresa' && $oferta->usuario_id === $actor->id) {
                $data['es_mi_oferta'] = true;
            }
        }

        return response()->json(['data' => $data]);
    }

    // ======================= ESCRITURA ======================= //

    // POST: empresa crea para sí; admin nivel 2+ crea para cualquier empresa //
    public function store(Request $request)
    {
        $this->authorize('create', OfertaEmpleo::class);

        $actor   = $request->user();
        $esAdmin = $actor->rol?->nombre === 'Administrador';

        $datos = $this->validar($request, $esAdmin);

        // Empresa: forzar usuario_id al propio actor (no puede suplantar) //
        if (!$esAdmin) {
            $datos['usuario_id'] = $actor->id;
        }

        // Admin: verificar que el usuario asignado sea una empresa válida //
        if ($esAdmin && !$this->usuarioEsEmpresa($datos['usuario_id'])) {
            return response()->json(['message' => 'El usuario seleccionado no es una empresa válida.'], 422);
        }

        // El sistema controla estado inicial y fecha de publicación //
        $datos['estado']             = $datos['estado'] ?? 'Abierta';
        $datos['fecha_publicacion']  = now();

        $rutaNueva = null;

        try {
            $oferta = DB::transaction(function () use ($request, $datos, &$rutaNueva) {
                // Crear la oferta sin el logo (se sube después de tener el ID) //
                $oferta = OfertaEmpleo::create(collect($datos)->except('logo')->all());

                // Subir logo si existe //
                if ($request->hasFile('logo')) {
                    $logo   = $request->file('logo');
                    $nombre = 'OfertaEmpleo_' . $oferta->id . '_' . Str::uuid() . '.' . $logo->extension();
                    $rutaNueva = $logo->storeAs('imagenes', $nombre, 'public');

                    if (!$rutaNueva) {
                        throw new \RuntimeException('No se pudo guardar el logo.');
                    }

                    $oferta->logo = Storage::url($rutaNueva);
                    $oferta->save();
                }

                return $oferta;
            });
        } catch (Throwable $e) {
            $this->borrarArchivo($rutaNueva);
            report($e);

            return response()->json([
                'message' => 'No se pudo guardar la oferta de empleo. Intenta de nuevo.',
            ], 500);
        }

        // Auditoría: oferta creada //
        LogService::registrar(
            $actor->id,
            'crear-oferta',
            "Creó la oferta «{$oferta->titulo}».",
            'ofertas_empleo',
            $oferta->id
        );

        return response()->json([
            'message' => 'Oferta de empleo creada correctamente.',
            'data'    => $this->formatear($this->cargarRelaciones($oferta)),
        ], 201);
    }

    // PUT: empresa dueña edita la suya; admin nivel 2+ edita cualquier oferta //
    public function update(Request $request, $id)
    {
        $oferta = OfertaEmpleo::find($id);

        if (!$oferta) {
            return response()->json(['message' => 'La oferta de empleo solicitada no existe o está eliminada.'], 404);
        }

        $this->authorize('update', $oferta);

        $actor   = $request->user();
        $esAdmin = $actor->rol?->nombre === 'Administrador';

        $datos = $this->validar($request, false);   // en update no se acepta usuario_id //

        // No permitir cambio de dueño //
        unset($datos['usuario_id']);

        $oferta->fill(collect($datos)->except('logo')->all());

        if (!$oferta->isDirty() && !$request->hasFile('logo')) {
            return response()->json([
                'message' => 'No se realizaron cambios en la oferta de empleo.',
                'data'    => $this->formatear($this->cargarRelaciones($oferta)),
            ]);
        }

        $rutaNueva    = null;
        $logoAnterior = null;

        try {
            DB::transaction(function () use ($request, $oferta, &$rutaNueva, &$logoAnterior) {
                // Subir logo nuevo (si viene) //
                if ($request->hasFile('logo')) {
                    $logo   = $request->file('logo');
                    $nombre = 'OfertaEmpleo_' . $oferta->id . '_' . Str::uuid() . '.' . $logo->extension();
                    $rutaNueva = $logo->storeAs('imagenes', $nombre, 'public');

                    if (!$rutaNueva) {
                        throw new \RuntimeException('No se pudo guardar el logo.');
                    }

                    $logoAnterior = $oferta->logo;
                    $oferta->logo = Storage::url($rutaNueva);
                }

                $oferta->save();
            });
        } catch (Throwable $e) {
            $this->borrarArchivo($rutaNueva);
            report($e);

            return response()->json([
                'message' => 'No se pudo actualizar la oferta de empleo. Intenta de nuevo.',
            ], 500);
        }

        // Borrar el logo viejo solo tras un save exitoso //
        if ($logoAnterior && $logoAnterior !== $oferta->logo) {
            $this->borrarArchivo($logoAnterior);
        }

        // Auditoría: oferta actualizada //
        LogService::registrar(
            $actor->id,
            'actualizar-oferta',
            "Actualizó la oferta «{$oferta->titulo}».",
            'ofertas_empleo',
            $oferta->id
        );

        return response()->json([
            'message' => 'Oferta de empleo actualizada correctamente.',
            'data'    => $this->formatear($this->cargarRelaciones($oferta->fresh())),
        ]);
    }

    // ======================= BORRADO ======================= //

    // DELETE: empresa dueña o admin nivel 3 hacen soft delete //
    public function destroy(Request $request, $id)
    {
        $oferta = OfertaEmpleo::find($id);

        if (!$oferta) {
            return response()->json(['message' => 'La oferta de empleo solicitada no existe o ya fue eliminada.'], 404);
        }

        $this->authorize('delete', $oferta);

        try {
            $oferta->delete();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar la oferta de empleo. Intenta de nuevo.',
            ], 500);
        }

        // Auditoría: borrado lógico de oferta //
        LogService::registrar(
            $request->user()->id,
            'eliminar-oferta',
            "Eliminó lógicamente la oferta «{$oferta->titulo}».",
            'ofertas_empleo',
            $oferta->id
        );

        return response()->json([
            'message' => "{$this->etiqueta($oferta)} se eliminó. Puedes restaurarla.",
        ]);
    }

    // PATCH /admin/ofertas/{id}/restaurar: solo admin nivel 3 //
    public function restore(Request $request, $id)
    {
        $oferta = OfertaEmpleo::onlyTrashed()->find($id);

        if (!$oferta) {
            return response()->json([
                'message' => 'La oferta de empleo solicitada no existe o no está eliminada.',
            ], 404);
        }

        $this->authorize('restore', $oferta);

        try {
            $oferta->restore();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo restaurar la oferta de empleo. Intenta de nuevo.',
            ], 500);
        }

        // Auditoría: restaurar oferta //
        LogService::registrar(
            $request->user()->id,
            'restaurar-oferta',
            "Restauró la oferta «{$oferta->titulo}».",
            'ofertas_empleo',
            $oferta->id
        );

        return response()->json([
            'message' => "{$this->etiqueta($oferta)} se restauró correctamente.",
            'data'    => $this->formatear($this->cargarRelaciones($oferta->fresh())),
        ]);
    }

    // DELETE /admin/ofertas/{id}/definitivo: solo admin nivel 3, borra logo del disco //
    public function forceDelete(Request $request, $id)
    {
        $oferta = OfertaEmpleo::withTrashed()->find($id);

        if (!$oferta) {
            return response()->json(['message' => 'La oferta de empleo solicitada no existe.'], 404);
        }

        $this->authorize('forceDelete', $oferta);

        // Debe estar soft-deleted primero (doble confirmación) //
        if (!$oferta->trashed()) {
            return response()->json([
                'message' => 'La oferta debe eliminarse primero de forma lógica antes de borrarla definitivamente.',
            ], 409);
        }

        // Bloqueo por dependencias (postulaciones y lista de deseos no tienen cascade en BD) //
        $dependencias = [
            [$oferta->postulaciones()->withTrashed()->count(), 'postulación', 'postulaciones'],
            [$oferta->listaDeseos()->count(), 'elemento en la lista de deseos', 'elementos en la lista de deseos'],
        ];

        $detalle = [];

        foreach ($dependencias as [$total, $singular, $plural]) {
            if ($total > 0) {
                $detalle[] = $total . ' ' . ($total === 1 ? $singular : $plural);
            }
        }

        if (count($detalle) > 0) {
            return response()->json([
                'message'      => "No se puede eliminar definitivamente la oferta «{$oferta->titulo}»: está siendo usada por "
                    . implode(' y ', $detalle) . '. Elimina primero esos registros dependientes.',
                'dependencias' => $detalle,
            ], 409);
        }

        $etiqueta       = $this->etiqueta($oferta);
        $logo           = $oferta->logo;
        $idEliminado    = $oferta->id;
        $tituloEliminado = $oferta->titulo;

        try {
            $oferta->forceDelete();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar definitivamente la oferta. Intenta de nuevo.',
            ], 500);
        }

        $this->borrarArchivo($logo);

        // Auditoría: borrado físico de oferta //
        LogService::registrar(
            $request->user()->id,
            'eliminar-definitivo-oferta',
            "Eliminó definitivamente la oferta «{$tituloEliminado}».",
            'ofertas_empleo',
            $idEliminado
        );

        return response()->json([
            'message' => "{$etiqueta} se eliminó definitivamente, junto con su logo.",
        ]);
    }

    // ======================= HELPERS ======================= //

    // Recarga las relaciones y conteos tras un save para formatear uniforme //
    private function cargarRelaciones(OfertaEmpleo $oferta): OfertaEmpleo
    {
        return $oferta->fresh()
            ->load([
                'categoria:id,nombre',
                'usuario:id,correo,rol_id',
                'usuario.perfil:usuario_id,nombre',
            ])
            ->loadCount(['postulaciones', 'listaDeseos']);
    }

    // Verifica que un usuario sea empresa activa (para admin al crear a nombre de una empresa) //
    private function usuarioEsEmpresa(?int $usuarioId): bool
    {
        if (!$usuarioId) {
            return false;
        }

        $usuario = Usuario::with('rol')->find($usuarioId);

        return $usuario
            && $usuario->estado
            && $usuario->rol?->nombre === 'Empresa';
    }

    // Respuesta uniforme de una oferta //
    private function formatear(OfertaEmpleo $oferta): array
    {
        $zona = 'America/Mexico_City';

        return [
            'id'                   => $oferta->id,
            'usuario_id'           => $oferta->usuario_id,
            'empresa'              => $oferta->usuario?->perfil?->nombre,   // nombre de la empresa (perfil.nombre) //
            'categoria_id'         => $oferta->categoria_id,
            'categoria'            => $oferta->categoria?->nombre,
            'titulo'               => $oferta->titulo,
            'descripcion'          => $oferta->descripcion,
            'ubicacion'            => $oferta->ubicacion,
            'salario'              => $oferta->salario,
            'tipo_empleo'          => $oferta->tipo_empleo,
            'fecha_publicacion'    => $oferta->fecha_publicacion?->toDateTimeString(),
            'estado'               => $oferta->estado,
            'logo'                 => $this->urlSiExiste($oferta->logo),
            'postulaciones_count'  => $oferta->postulaciones_count ?? 0,
            'lista_deseos_count'   => $oferta->lista_deseos_count ?? 0,
            'created_at'           => $oferta->created_at?->timezone($zona)->toDateTimeString(),
            'updated_at'           => $oferta->updated_at?->timezone($zona)->toDateTimeString(),
            'eliminado'            => $oferta->trashed(),
            'deleted_at'           => $oferta->deleted_at?->timezone($zona)->toDateTimeString(),
        ];
    }

    // Texto legible para mensajes de éxito //
    private function etiqueta(OfertaEmpleo $oferta): string
    {
        return "La oferta «{$oferta->titulo}»";
    }

    // /storage/imagenes/x.png → imagenes/x.png //
    private function relativa(?string $ruta): ?string
    {
        return $ruta ? Str::after($ruta, '/storage/') : null;
    }

    // Devuelve URL pública si el archivo existe, si no null //
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
        if (!$ruta) {
            return;
        }

        $relativa = str_contains($ruta, '/storage/') ? $this->relativa($ruta) : $ruta;

        if ($relativa) {
            Storage::disk('public')->delete($relativa);
        }
    }

    // Validación compartida; usuario_id solo se exige al admin en store //
    private function validar(Request $request, bool $esAdmin): array
    {
        $reglas = [
            'categoria_id' => [
                'required',
                'integer',
                Rule::exists('categorias', 'id')->whereNull('deleted_at'),
            ],
            'titulo'       => ['required', 'string', 'min:3', 'max:150'],
            'descripcion'  => ['required', 'string', 'min:10'],
            'ubicacion'    => ['required', 'string', 'min:3', 'max:150'],
            'salario'      => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'tipo_empleo'  => ['required', 'in:Tiempo completo,Medio tiempo,Por obra'],
            'estado'       => ['sometimes', 'in:Abierta,Cerrada'],
            'logo'         => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        // Solo admin puede asignar usuario_id al crear; empresa se auto-asigna //
        if ($esAdmin) {
            $reglas['usuario_id'] = [
                'required',
                'integer',
                Rule::exists('usuarios', 'id')->whereNull('deleted_at')->where('estado', true),
            ];
        }

        return $request->validate($reglas, [
            'required'       => 'El campo :attribute es obligatorio.',
            'string'         => 'El campo :attribute debe ser texto.',
            'min'            => 'El campo :attribute debe tener al menos :min caracteres.',
            'max'            => 'El campo :attribute no puede tener más de :max caracteres.',
            'exists'         => 'El registro seleccionado no existe, está eliminado o no es válido.',
            'numeric'        => 'El salario debe ser un número.',
            'salario.min'    => 'El salario no puede ser negativo.',
            'salario.max'    => 'El salario no puede ser mayor a :max.',
            'in'             => 'El valor seleccionado en :attribute no es válido.',
            'date'           => 'La fecha de publicación no es válida.',
            'image'          => 'El logo debe ser una imagen.',
            'logo.mimes'     => 'El logo debe ser de tipo jpg, jpeg, png o webp.',
            'logo.max'       => 'El logo no puede pesar más de 2 MB.',
        ], [
            'usuario_id'   => 'empresa',
            'categoria_id' => 'categoría',
            'titulo'       => 'título',
            'descripcion'  => 'descripción',
            'ubicacion'    => 'ubicación',
            'tipo_empleo'  => 'tipo de empleo',
        ]);
    }
}
