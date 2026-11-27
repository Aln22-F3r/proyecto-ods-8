<?php

namespace App\Http\Controllers;

use App\Models\ListaDeseo;
use App\Models\OfertaEmpleo;
use App\Models\Usuario;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class ListaDeseoController extends Controller
{
    // Relaciones precargadas para respuestas uniformes //
    private const RELACIONES = [
        'ofertaEmpleo:id,usuario_id,categoria_id,titulo,ubicacion,salario,tipo_empleo,estado,logo,fecha_publicacion',
        'ofertaEmpleo.categoria:id,nombre',           // categoría de la oferta //
        'ofertaEmpleo.usuario:id,correo,rol_id',      // empresa dueña de la oferta //
        'ofertaEmpleo.usuario.perfil:usuario_id,nombre', // nombre de la empresa desde perfiles //
    ];

    // ======================= LECTURA ======================= //

    // GET: candidato ve su propia lista; admin ve todas con filtros //
    public function index(Request $request)
    {
        $this->authorize('viewAny', ListaDeseo::class);

        $actor = $request->user();
        $rol   = $actor->rol?->nombre;

        $query = ListaDeseo::with(self::RELACIONES);

        // Candidato: solo los suyos //
        if ($rol === 'Candidato') {
            $query->where('usuario_id', $actor->id);
        }

        // Admin: filtros opcionales por query string //
        if ($rol === 'Administrador') {
            if ($request->filled('usuario_id')) {
                $query->where('usuario_id', $request->usuario_id);
            }

            if ($request->filled('oferta_empleo_id')) {
                $query->where('oferta_empleo_id', $request->oferta_empleo_id);
            }
        }

        return response()->json(
            $query->orderByDesc('fecha_agregado')->paginate(15)
        );
    }

    // GET: detalle de un elemento (candidato dueño o admin) //
    public function show($id)
    {
        $item = ListaDeseo::with(self::RELACIONES)->find($id);

        if (!$item) {
            return response()->json(['message' => 'El elemento de la lista de deseos no existe.'], 404);
        }

        $this->authorize('view', $item);

        return response()->json(['data' => $this->formatear($item)]);
    }

    // ======================= ESCRITURA ======================= //

    // POST: candidato agrega oferta abierta a su lista //
    public function store(Request $request)
    {
        $this->authorize('create', ListaDeseo::class);

        $actor   = $request->user();
        $esAdmin = $actor->rol?->nombre === 'Administrador';

        // Reglas dinámicas: admin debe indicar usuario_id; candidato no (se autoasigna) //
        $reglas = [
            'oferta_empleo_id' => [
                'required',
                'integer',
                Rule::exists('ofertas_empleo', 'id')->whereNull('deleted_at'),
            ],
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
        ], [
            'usuario_id'       => 'candidato',
            'oferta_empleo_id' => 'oferta de empleo',
        ]);

        // Candidato: forzar usuario_id al propio actor (no puede suplantar) //
        if (!$esAdmin) {
            $datos['usuario_id'] = $actor->id;
        }

        // Verificar candidato válido //
        $candidato = Usuario::with('rol')->find($datos['usuario_id']);

        if (!$candidato || $candidato->rol?->nombre !== 'Candidato' || !$candidato->estado) {
            return response()->json([
                'message' => 'El usuario seleccionado no es un candidato válido.',
            ], 422);
        }

        // Verificar oferta abierta (no tiene sentido guardar una cerrada) //
        $oferta = OfertaEmpleo::find($datos['oferta_empleo_id']);

        if (!$oferta || $oferta->estado !== 'Abierta') {
            return response()->json([
                'message' => 'La oferta no existe, está cerrada o está eliminada.',
            ], 422);
        }

        try {
            // El unique constraint (usuario_id, oferta_empleo_id) evita duplicados //
            $item = ListaDeseo::create([
                'usuario_id'       => $datos['usuario_id'],
                'oferta_empleo_id' => $datos['oferta_empleo_id'],
                'fecha_agregado'   => now(),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Ya estaba en la lista → 409 para que el frontend muestre "ya guardada" //
            return response()->json([
                'message' => 'Esta oferta ya está en tu lista de deseos.',
            ], 409);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo agregar la oferta a la lista de deseos.',
            ], 500);
        }

        // Auditoría: agregó a lista de deseos //
        LogService::registrar(
            $actor->id,
            'agregar-lista-deseos',
            "Agregó la oferta «{$item->ofertaEmpleo?->titulo}» a su lista de deseos.",
            'lista_deseos',
            $item->id
        );

        return response()->json([
            'message' => 'Oferta agregada a la lista de deseos.',
            'data'    => $this->formatear($item->fresh()->load(self::RELACIONES)),
        ], 201);
    }

    // ======================= BORRADO ======================= //

    // DELETE: candidato dueño quita su favorito; admin nivel 3 puede limpiar cualquiera //
    public function destroy(Request $request, $id)
    {
        $item = ListaDeseo::with(self::RELACIONES)->find($id);

        if (!$item) {
            return response()->json([
                'message' => 'El elemento de la lista de deseos no existe.',
            ], 404);
        }

        $this->authorize('delete', $item);

        $titulo = $item->ofertaEmpleo?->titulo ?? 'la oferta';

        try {
            $item->delete();   // borrado físico: no hay SoftDeletes en esta tabla //
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo quitar la oferta de la lista de deseos.',
            ], 500);
        }

        // Auditoría: quitó de lista de deseos //
        LogService::registrar(
            $request->user()->id,
            'quitar-lista-deseos',
            "Quitó la oferta «{$titulo}» de su lista de deseos.",
            'lista_deseos',
            $id
        );

        return response()->json([
            'message' => "Se quitó «{$titulo}» de la lista de deseos.",
        ]);
    }

    // ======================= HELPERS ======================= //

    // Formato uniforme de respuesta //
    private function formatear(ListaDeseo $item): array
    {
        $zona   = 'America/Mexico_City';
        $oferta = $item->ofertaEmpleo;

        return [
            'id'                => $item->id,
            'usuario_id'        => $item->usuario_id,
            'oferta_empleo_id'  => $item->oferta_empleo_id,
            'oferta'            => $oferta ? [
                'id'                => $oferta->id,
                'titulo'            => $oferta->titulo,
                'ubicacion'         => $oferta->ubicacion,
                'salario'           => $oferta->salario,
                'tipo_empleo'       => $oferta->tipo_empleo,
                'estado'            => $oferta->estado,
                'categoria'         => $oferta->categoria?->nombre,
                'empresa'           => $oferta->usuario?->perfil?->nombre,
                'logo'              => $this->urlSiExiste($oferta->logo),
                'fecha_publicacion' => $oferta->fecha_publicacion?->timezone($zona)->format('d/m/Y H:i'),
            ] : null,
            'fecha_agregado'    => $item->fecha_agregado?->timezone($zona)->format('d/m/Y H:i'),
            'creado'            => $item->created_at?->timezone($zona)->format('d/m/Y H:i'),
            'actualizado'       => $item->updated_at?->timezone($zona)->format('d/m/Y H:i'),
        ];
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
}
