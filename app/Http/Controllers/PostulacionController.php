<?php

namespace App\Http\Controllers;

use App\Models\Postulacion;
use App\Models\Usuario;
use App\Models\OfertaEmpleo;
use App\Models\Notificacion;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class PostulacionController extends Controller
{
    // Estados válidos del flujo //
    private const ESTADOS = ['Pendiente', 'En revisión', 'Aceptada', 'Rechazada', 'Retirada'];

    // Relaciones precargadas para respuestas uniformes //
    private const RELACIONES = [
        'usuario:id,correo,rol_id',                    // el candidato (correo y rol) //
        'usuario.perfil:usuario_id,nombre,apellido',   // nombre/apellido desde perfiles //
        'ofertaEmpleo:id,usuario_id,titulo,estado',    // la oferta (título y estado) //
        'ofertaEmpleo.usuario:id,correo,rol_id',       // la empresa dueña de la oferta //
        'ofertaEmpleo.usuario.perfil:usuario_id,nombre', // nombre de la empresa desde perfiles //
    ];

    // ======================= LECTURA ======================= //

    // GET: candidato ve sus postulaciones; empresa las recibidas; admin todas con filtros //
    public function index(Request $request)
    {
        $this->authorize('viewAny', Postulacion::class);

        $actor = $request->user();
        $rol   = $actor->rol?->nombre;

        $query = Postulacion::with(self::RELACIONES);

        // Candidato: solo las propias //
        if ($rol === 'Candidato') {
            $query->where('usuario_id', $actor->id);
        }

        // Empresa: solo las postulaciones a sus ofertas NO eliminadas //
        if ($rol === 'Empresa') {
            $query->whereHas(
                'ofertaEmpleo',
                fn($q) =>
                $q->where('usuario_id', $actor->id)->whereNull('deleted_at')
            );
        }

        // Filtros opcionales (admin y también empresa/candidato por comodidad) //
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('oferta_empleo_id')) {
            $query->where('oferta_empleo_id', $request->oferta_empleo_id);
        }

        // Solo el admin puede filtrar por usuario_id (para auditar a un candidato) //
        if ($rol === 'Administrador' && $request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }

        return response()->json(
            $query->orderByDesc('fecha_postulacion')->paginate(15)
        );
    }

    // GET /admin/postulaciones/eliminados: solo admin nivel 3, lista soft-deleted //
    public function eliminados(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('eliminar');   // gate admin nivel 3 //

        return response()->json(
            Postulacion::onlyTrashed()
                ->with(self::RELACIONES)
                ->orderByDesc('fecha_postulacion')
                ->paginate(15)
        );
    }

    // GET: detalle de una postulación (admin ve eliminadas también) //
    public function show($id)
    {
        $postulacion = Postulacion::withTrashed()
            ->with(self::RELACIONES)
            ->find($id);

        if (!$postulacion) {
            return response()->json(['message' => 'La postulación solicitada no existe.'], 404);
        }

        $this->authorize('view', $postulacion);

        return response()->json(['data' => $this->formatear($postulacion)]);
    }

    // ======================= ESCRITURA ======================= //

    // POST: candidato se postula; admin puede crear para un candidato específico //
    public function store(Request $request)
    {
        $this->authorize('create', Postulacion::class);

        $actor   = $request->user();
        $esAdmin = $actor->rol?->nombre === 'Administrador';

        $reglas = [
            'oferta_empleo_id' => [
                'required',
                'integer',
                Rule::exists('ofertas_empleo', 'id')->whereNull('deleted_at'),
            ],
            'comentario' => ['nullable', 'string', 'min:5', 'max:1000'],
        ];

        if ($esAdmin) {
            $reglas['usuario_id'] = [
                'required',
                'integer',
                Rule::exists('usuarios', 'id')->whereNull('deleted_at')->where('estado', true),
            ];
        }

        $datos = $request->validate($reglas, [
            'required' => 'El campo :attribute es obligatorio.',
            'integer'  => 'El campo :attribute debe ser un número entero.',
            'exists'   => 'El registro seleccionado no existe o está eliminado.',
            'string'   => 'El campo :attribute debe ser texto.',
            'min'      => 'El campo :attribute debe tener al menos :min caracteres.',
            'max'      => 'El campo :attribute no puede tener más de :max caracteres.',
        ], [
            'usuario_id'       => 'candidato',
            'oferta_empleo_id' => 'oferta de empleo',
            'comentario'       => 'comentario',
        ]);

        // Candidato: forzar usuario_id al propio actor (no puede suplantar) //
        if (!$esAdmin) {
            $datos['usuario_id'] = $actor->id;
        }

        // Verificar candidato válido con CV //
        $candidato = Usuario::with(['rol', 'perfil'])->find($datos['usuario_id']);

        if (!$candidato || $candidato->rol?->nombre !== 'Candidato' || !$candidato->estado) {
            return response()->json([
                'message' => 'El usuario seleccionado no es un candidato válido.',
            ], 422);
        }

        if (!$candidato->perfil?->cv) {
            return response()->json([
                'message' => 'El candidato no tiene un CV registrado en su perfil.',
            ], 422);
        }

        // Verificar oferta abierta //
        $oferta = OfertaEmpleo::with('usuario')->find($datos['oferta_empleo_id']);

        if (!$oferta || $oferta->estado !== 'Abierta') {
            return response()->json([
                'message' => 'La oferta seleccionada no existe, está cerrada o está eliminada.',
            ], 422);
        }

        try {
            // Si existe una postulación (incluso eliminada) → restaurar en vez de insertar //
            $postulacion = DB::transaction(function () use ($datos, $candidato) {
                $existente = Postulacion::withTrashed()
                    ->where('usuario_id', $datos['usuario_id'])
                    ->where('oferta_empleo_id', $datos['oferta_empleo_id'])
                    ->first();

                if ($existente && !$existente->trashed()) {
                    throw new \DomainException(
                        'El candidato ya tiene una postulación registrada para esta oferta.'
                    );
                }

                if ($existente) {
                    $existente->restore();
                    $existente->fill([
                        'cv'                => $candidato->perfil->cv,
                        'fecha_postulacion' => now(),
                        'estado'            => 'Pendiente',
                        'comentario'        => $datos['comentario'] ?? null,
                    ]);
                    $existente->save();

                    return $existente;
                }

                return Postulacion::create([
                    'usuario_id'        => $datos['usuario_id'],
                    'oferta_empleo_id'  => $datos['oferta_empleo_id'],
                    'cv'                => $candidato->perfil->cv,
                    'fecha_postulacion' => now(),
                    'estado'            => 'Pendiente',
                    'comentario'        => $datos['comentario'] ?? null,
                ]);
            });
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'No se pudo crear la postulación.'], 500);
        }

        // Carga relaciones una sola vez para formatear y notificar //
        $postulacion->load(self::RELACIONES);

        // Notificar a la empresa dueña de la oferta (si no es el actor mismo) //
        $empresa = $postulacion->ofertaEmpleo?->usuario;

        if ($empresa && $empresa->id !== $actor->id) {
            $nombreCandidato = trim(
                ($candidato->perfil->nombre ?? 'Un candidato') . ' ' . ($candidato->perfil->apellido ?? '')
            );

            $this->crearNotificacion(
                $empresa->id,
                'Nueva postulación recibida',
                "{$nombreCandidato} se ha postulado a tu oferta «{$postulacion->ofertaEmpleo->titulo}».",
                'postulacion_nueva'
            );
        }

        // Auditoría: postulación creada //
        LogService::registrar(
            $actor->id,
            'crear-postulacion',
            "Se postuló a la oferta «{$postulacion->ofertaEmpleo->titulo}».",
            'postulaciones',
            $postulacion->id
        );

        return response()->json([
            'message' => 'Postulación creada correctamente.',
            'data'    => $this->formatear($postulacion),
        ], 201);
    }

    // PUT: candidato solo puede marcar 'Retirada'; empresa cambia estado del flujo; admin cualquiera //
    public function update(Request $request, $id)
    {
        $postulacion = Postulacion::find($id);

        if (!$postulacion) {
            return response()->json(['message' => 'La postulación no existe o está eliminada.'], 404);
        }

        $this->authorize('update', $postulacion);

        $actor = $request->user();
        $rol   = $actor->rol?->nombre;

        $datos = $request->validate([
            'estado'     => ['sometimes', 'required', Rule::in(self::ESTADOS)],
            'comentario' => ['sometimes', 'nullable', 'string', 'min:5', 'max:1000'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'in'       => 'El estado seleccionado no es válido.',
            'string'   => 'El campo :attribute debe ser texto.',
            'min'      => 'El campo :attribute debe tener al menos :min caracteres.',
            'max'      => 'El campo :attribute no puede tener más de :max caracteres.',
        ], [
            'estado'     => 'estado',
            'comentario' => 'comentario',
        ]);

        // Reglas de negocio por rol sobre el nuevo estado //
        if (isset($datos['estado'])) {
            if ($rol === 'Candidato' && $datos['estado'] !== 'Retirada') {
                return response()->json([
                    'message' => 'Como candidato solo puedes marcar tu postulación como "Retirada".',
                ], 403);
            }

            if ($rol === 'Empresa' && $datos['estado'] === 'Retirada') {
                return response()->json([
                    'message' => 'La empresa no puede marcar una postulación como "Retirada".',
                ], 403);
            }
        }

        $postulacion->fill($datos);

        if (!$postulacion->isDirty()) {
            return response()->json([
                'message' => 'No se realizaron cambios en la postulación.',
                'data'    => $this->formatear($postulacion->load(self::RELACIONES)),
            ]);
        }

        // Detectar si el estado cambió para decidir la notificación //
        $estadoAnterior  = $postulacion->getOriginal('estado');
        $estadoNuevo     = $postulacion->estado;
        $cambioDeEstado  = $postulacion->isDirty('estado') && $estadoAnterior !== $estadoNuevo;

        try {
            $postulacion->save();
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'No se pudo actualizar la postulación.'], 500);
        }

        // Recargar relaciones para formatear y notificar //
        $postulacion->load(self::RELACIONES);

        // Notificar al "otro lado" cuando cambia el estado //
        if ($cambioDeEstado) {
            $this->notificarCambioEstado($actor, $postulacion, $estadoNuevo);
        }

        // Auditoría: cambio de postulación //
        LogService::registrar(
            $actor->id,
            'actualizar-postulacion',
            "Cambió la postulación #{$postulacion->id} a estado «{$postulacion->estado}».",
            'postulaciones',
            $postulacion->id
        );

        return response()->json([
            'message' => 'Postulación actualizada correctamente.',
            'data'    => $this->formatear($postulacion),
        ]);
    }

    // ======================= BORRADO ======================= //

    // DELETE: candidato dueño (retira) o admin nivel 3 (borra). Soft delete //
    public function destroy(Request $request, $id)
    {
        $postulacion = Postulacion::with(self::RELACIONES)->find($id);

        if (!$postulacion) {
            return response()->json(['message' => 'La postulación no existe o ya fue eliminada.'], 404);
        }

        $this->authorize('delete', $postulacion);

        $etiqueta = $this->etiqueta($postulacion);

        try {
            $postulacion->delete();
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'No se pudo eliminar la postulación.'], 500);
        }

        // Auditoría: borrado lógico de postulación //
        LogService::registrar(
            $request->user()->id,
            'eliminar-postulacion',
            "Eliminó lógicamente la postulación #{$postulacion->id}.",
            'postulaciones',
            $postulacion->id
        );

        return response()->json([
            'message' => "{$etiqueta} se eliminó correctamente. Puedes restaurarla.",
        ]);
    }

    // PATCH /admin/postulaciones/{id}/restaurar: solo admin nivel 3 //
    public function restore(Request $request, $id)
    {
        $postulacion = Postulacion::onlyTrashed()->with(self::RELACIONES)->find($id);

        if (!$postulacion) {
            return response()->json([
                'message' => 'La postulación no existe o no está eliminada.',
            ], 404);
        }

        $this->authorize('restore', $postulacion);

        try {
            $postulacion->restore();
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'No se pudo restaurar la postulación.'], 500);
        }

        // Auditoría: restaurar postulación //
        LogService::registrar(
            $request->user()->id,
            'restaurar-postulacion',
            "Restauró la postulación #{$postulacion->id}.",
            'postulaciones',
            $postulacion->id
        );

        return response()->json([
            'message' => 'Postulación restaurada correctamente.',
            'data'    => $this->formatear($postulacion->fresh()->load(self::RELACIONES)),
        ]);
    }

    // DELETE /admin/postulaciones/{id}/definitivo: solo admin nivel 3, borra el CV copia del disco //
    public function forceDelete(Request $request, $id)
    {
        $postulacion = Postulacion::withTrashed()->with(self::RELACIONES)->find($id);

        if (!$postulacion) {
            return response()->json(['message' => 'La postulación solicitada no existe.'], 404);
        }

        $this->authorize('forceDelete', $postulacion);

        // Debe estar soft-deleted primero (doble confirmación) //
        if (!$postulacion->trashed()) {
            return response()->json([
                'message' => 'La postulación debe eliminarse lógicamente antes de borrarla definitivamente.',
            ], 409);
        }

        $etiqueta        = $this->etiqueta($postulacion);
        $cv              = $postulacion->cv;
        $idEliminado     = $postulacion->id;
        $usuarioIdOriginal = $postulacion->usuario_id;

        try {
            $postulacion->forceDelete();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar definitivamente la postulación.',
            ], 500);
        }

        $this->borrarCvSiHuerfano($cv, $usuarioIdOriginal);

        // Auditoría: borrado físico de postulación //
        LogService::registrar(
            $request->user()->id,
            'eliminar-definitivo-postulacion',
            "Eliminó definitivamente la postulación #{$idEliminado}.",
            'postulaciones',
            $idEliminado
        );

        return response()->json([
            'message' => "{$etiqueta} se eliminó definitivamente.",
        ]);
    }

    // ======================= HELPERS ======================= //

    // Borra el archivo del disco solo si NO coincide con el CV actual del candidato //
    private function borrarCvSiHuerfano(?string $cv, ?int $usuarioId): void
    {
        if (!$cv) {
            return;
        }

        // Si el candidato sigue teniendo ese mismo CV en su perfil, NO borrar el archivo //
        $perfilCv = $usuarioId
            ? Usuario::find($usuarioId)?->perfil?->cv
            : null;

        if ($perfilCv === $cv) {
            return;
        }

        $relativa = str_contains($cv, '/storage/') ? Str::after($cv, '/storage/') : $cv;

        if ($relativa && Storage::disk('public')->exists($relativa)) {
            Storage::disk('public')->delete($relativa);
        }
    }

    // Nombre + apellido vienen del perfil, NO del usuario //
    private function nombreCompletoCandidato(Postulacion $p): ?string
    {
        $perfil = $p->usuario?->perfil;

        if (!$perfil) {
            return null;
        }

        return trim($perfil->nombre . ' ' . ($perfil->apellido ?? ''));
    }

    // Formato uniforme de respuesta //
    private function formatear(Postulacion $postulacion): array
    {
        $zona = 'America/Mexico_City';

        return [
            'id'                 => $postulacion->id,
            'usuario_id'         => $postulacion->usuario_id,
            'usuario'            => $this->nombreCompletoCandidato($postulacion),   // nombre desde perfil //
            'correo'             => $postulacion->usuario?->correo,
            'oferta_empleo_id'   => $postulacion->oferta_empleo_id,
            'oferta'             => $postulacion->ofertaEmpleo?->titulo,
            'oferta_estado'      => $postulacion->ofertaEmpleo?->estado,
            'empresa'            => $postulacion->ofertaEmpleo?->usuario?->perfil?->nombre,   // empresa desde perfil //
            'cv'                 => $this->urlSiExiste($postulacion->cv),
            'fecha_postulacion'  => $postulacion->fecha_postulacion?->timezone($zona)->format('d/m/Y H:i'),
            'estado'             => $postulacion->estado,
            'comentario'         => $postulacion->comentario,
            'creado'             => $postulacion->created_at?->timezone($zona)->format('d/m/Y H:i'),
            'actualizado'        => $postulacion->updated_at?->timezone($zona)->format('d/m/Y H:i'),
            'eliminado'          => $postulacion->trashed(),
            'fecha_eliminacion'  => $postulacion->deleted_at?->timezone($zona)->format('d/m/Y H:i'),
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

    // Texto legible para mensajes de éxito //
    private function etiqueta(Postulacion $postulacion): string
    {
        $usuario = $this->nombreCompletoCandidato($postulacion) ?? 'un usuario';
        $oferta  = $postulacion->ofertaEmpleo?->titulo ?? 'una oferta';

        return "La postulación de «{$usuario}» a «{$oferta}»";
    }

    // Inserta una notificación; nunca rompe el flujo principal si falla //
    private function crearNotificacion(
        ?int $destinatarioId,
        string $titulo,
        string $mensaje,
        string $tipo
    ): void {
        if (!$destinatarioId) {
            return;
        }

        try {
            Notificacion::create([
                'usuario_id' => $destinatarioId,
                'titulo'     => $titulo,
                'mensaje'    => $mensaje,
                'tipo'       => $tipo,
                'leida'      => false,
                'fecha'      => now(),
            ]);
        } catch (Throwable $e) {
            report($e);   // se loguea pero no se interrumpe la operación principal //
        }
    }

    // Decide quién recibe la notificación según quién cambió el estado //
    private function notificarCambioEstado(
        Usuario $actor,
        Postulacion $postulacion,
        string $estadoNuevo
    ): void {
        $oferta   = $postulacion->ofertaEmpleo;
        $empresa  = $oferta?->usuario;
        $candidato = $postulacion->usuario;

        // Candidato retiró su postulación → avisar a la empresa //
        if ($actor->rol?->nombre === 'Candidato') {
            if ($empresa && $empresa->id !== $actor->id) {
                $nombreCandidato = $this->nombreCompletoCandidato($postulacion) ?? 'El candidato';

                $this->crearNotificacion(
                    $empresa->id,
                    'Postulación retirada',
                    "{$nombreCandidato} retiró su postulación a «{$oferta?->titulo}».",
                    'postulacion_retirada'
                );
            }

            return;
        }

        // Empresa o admin cambió el estado → avisar al candidato //
        if ($candidato && $candidato->id !== $actor->id) {
            $this->crearNotificacion(
                $candidato->id,
                'Tu postulación fue actualizada',
                "Tu postulación a «{$oferta?->titulo}» ahora está en estado: {$estadoNuevo}.",
                'postulacion_estado'
            );
        }
    }
}
