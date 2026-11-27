<?php

namespace App\Http\Controllers;

use App\Models\OfertaEmpleo;
use App\Models\Pago;
use App\Services\LogService;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PagoController extends Controller
{
    private const ZONA = 'America/Mexico_City';   // zona horaria para fechas //

    // ======================= EMPRESA: INICIAR PAGO ======================= //

    // POST /empresa/ofertas/{id}/destacar: crea Pago pendiente + PaymentIntent en Stripe //
    public function destacar(Request $request, $ofertaId)
    {
        $oferta = OfertaEmpleo::find($ofertaId);

        if (!$oferta) {
            return response()->json(['message' => 'La oferta no existe.'], 404);
        }

        $this->authorize('destacar', $oferta);

        $actor    = $request->user();
        $precio   = config('services.destacado.precio');
        $dias     = config('services.destacado.dias');
        $concepto = "Destacar oferta «{$oferta->titulo}» por {$dias} días";

        // Cancelar cualquier pago pendiente previo de esta oferta por este usuario //
        Pago::where('usuario_id', $actor->id)
            ->where('oferta_empleo_id', $oferta->id)
            ->where('estado', Pago::ESTADO_PENDIENTE)
            ->update(['estado' => Pago::ESTADO_CANCELADO]);

        $stripe = app(StripeService::class);

        try {
            $intent = $stripe->crearPaymentIntent(
                $oferta->id,
                $actor->id,
                $precio,
                'MXN',
                $concepto
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Stripe no respondió. Intenta de nuevo.',
            ], 500);
        }

        // Persistir el pago con el ID de Stripe ya obtenido //
        try {
            $pago = Pago::create([
                'usuario_id'         => $actor->id,
                'oferta_empleo_id'   => $oferta->id,
                'monto'              => $precio,
                'moneda'             => 'MXN',
                'concepto'           => $concepto,
                'metodo_pago'        => 'tarjeta',
                'estado'             => Pago::ESTADO_PENDIENTE,
                'referencia_externa' => $intent->id,
                'metadata'           => $stripe->aArray($intent),
            ]);
        } catch (Throwable $e) {
            report($e);

            // Rollback: cancelar el intent en Stripe para no dejar huérfanos //
            try {
                $stripe->cancelarPaymentIntent($intent->id);
            } catch (Throwable $e2) {
                report($e2);
            }

            return response()->json([
                'message' => 'No se pudo registrar el pago.',
            ], 500);
        }

        LogService::registrar(
            $actor->id,
            'crear-pago-destacado',
            "Inició pago para destacar la oferta «{$oferta->titulo}».",
            'pagos',
            $pago->id
        );

        return response()->json([
            'message' => 'Pago iniciado. Confirma con Stripe.js en el frontend.',
            'data'    => [
                'pago_id'         => $pago->id,
                'client_secret'   => $intent->client_secret,
                'publishable_key' => config('services.stripe.public'),
                'monto'           => $pago->monto,
                'moneda'          => $pago->moneda,
                'concepto'        => $pago->concepto,
                'oferta'          => [
                    'id'     => $oferta->id,
                    'titulo' => $oferta->titulo,
                ],
            ],
        ], 201);
    }

    // ======================= EMPRESA: CONFIRMAR PAGO ======================= //

    // POST /empresa/pagos/{id}/confirmar: verifica en Stripe y marca el pago como aprobado //
    public function confirmar(Request $request, $id)
    {
        $pago = Pago::with(['ofertaEmpleo'])->find($id);

        if (!$pago) {
            return response()->json(['message' => 'El pago no existe.'], 404);
        }

        $this->authorize('confirmar', $pago);

        $request->validate([
            'payment_intent' => ['required', 'string'],
        ]);

        // El frontend debe enviar el mismo pi_xxx que guardamos al crear el pago //
        if ($request->payment_intent !== $pago->referencia_externa) {
            return response()->json([
                'message' => 'El payment_intent no coincide con el pago.',
            ], 422);
        }

        $stripe = app(StripeService::class);

        try {
            $intent = $stripe->obtenerPaymentIntent($pago->referencia_externa);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo verificar con Stripe.',
            ], 500);
        }

        // Refrescar el pago por si otro request lo confirmó antes (race condition) //
        $pago = $pago->fresh();

        if (!$pago->estaPendiente()) {
            return response()->json([
                'message' => 'Este pago ya fue procesado previamente.',
            ], 409);
        }

        // Stripe no aprobó el cobro: marcar rechazado y salir //
        if (!$stripe->estaPagado($intent)) {
            $pago->update([
                'estado'   => Pago::ESTADO_RECHAZADO,
                'metadata' => $stripe->aArray($intent),
            ]);

            return response()->json([
                'message'       => 'El pago fue rechazado por Stripe.',
                'stripe_status' => $intent->status,
            ], 422);
        }

        // Éxito: marcar pago aprobado + activar destacado en la oferta (transacción) //
        try {
            DB::transaction(function () use ($pago, $intent, $stripe) {
                $pago->update([
                    'estado'     => Pago::ESTADO_APROBADO,
                    'fecha_pago' => now(),
                    'metadata'   => $stripe->aArray($intent),
                ]);

                $oferta = $pago->ofertaEmpleo;
                $dias   = config('services.destacado.dias');

                // Si aún estaba vigente, extender desde la fecha actual de expiración //
                $base = $oferta->destacada_hasta && $oferta->destacada_hasta->isFuture()
                    ? $oferta->destacada_hasta
                    : now();

                $oferta->update([
                    'destacada'       => true,
                    'destacada_hasta' => $base->copy()->addDays($dias),
                ]);
            });
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'El pago se cobró pero no se pudo actualizar la oferta.',
            ], 500);
        }

        LogService::registrar(
            $pago->usuario_id,
            'confirmar-pago-destacado',
            "Pagó para destacar la oferta «{$pago->ofertaEmpleo->titulo}».",
            'pagos',
            $pago->id
        );

        $pago->refresh();

        return response()->json([
            'message' => '¡Pago aprobado! La oferta está destacada.',
            'data'    => $this->formatear($pago),
        ]);
    }

    // ======================= LECTURA ======================= //

    // GET /empresa/pagos   o   GET /admin/pagos: listado con filtros por rol //
    public function index(Request $request)
    {
        $this->authorize('viewAny', Pago::class);

        $actor = $request->user();
        $rol   = $actor->rol?->nombre;

        $query = Pago::with([
            'ofertaEmpleo:id,titulo,usuario_id',
            'usuario:id,correo,rol_id',
            'usuario.perfil:usuario_id,nombre',
        ])->orderByDesc('created_at');

        // Empresa: solo sus propios pagos //
        if ($rol === 'Empresa') {
            $query->where('usuario_id', $actor->id);
        }

        // Admin: filtros opcionales //
        if ($rol === 'Administrador') {
            if ($request->filled('estado')) {
                $query->where('estado', $request->estado);
            }
            if ($request->filled('usuario_id')) {
                $query->where('usuario_id', $request->usuario_id);
            }
            if ($request->filled('oferta_empleo_id')) {
                $query->where('oferta_empleo_id', $request->oferta_empleo_id);
            }
        }

        return response()->json([
            'data' => $query->paginate(15),
        ]);
    }

    // GET /empresa/pagos/{id}   o   GET /admin/pagos/{id}: detalle de un pago //
    public function show(Request $request, $id)
    {
        $pago = Pago::with([
            'ofertaEmpleo:id,titulo,usuario_id',
            'usuario:id,correo,rol_id',
            'usuario.perfil:usuario_id,nombre',
        ])->find($id);

        if (!$pago) {
            return response()->json(['message' => 'El pago no existe.'], 404);
        }

        $this->authorize('view', $pago);

        return response()->json([
            'data' => $this->formatear($pago),
        ]);
    }

    // ======================= HELPER ======================= //

    // Formato uniforme de respuesta de un pago //
    private function formatear(Pago $pago): array
    {
        return [
            'id'                 => $pago->id,
            'usuario_id'         => $pago->usuario_id,
            'empresa'            => $pago->usuario?->perfil?->nombre,
            'oferta_empleo_id'   => $pago->oferta_empleo_id,
            'oferta_titulo'      => $pago->ofertaEmpleo?->titulo,
            'monto'              => $pago->monto,
            'moneda'             => $pago->moneda,
            'concepto'           => $pago->concepto,
            'metodo_pago'        => $pago->metodo_pago,
            'estado'             => $pago->estado,
            'referencia_externa' => $pago->referencia_externa,
            'fecha_pago'         => $pago->fecha_pago?->timezone(self::ZONA)->format('d/m/Y H:i'),
            'creado'             => $pago->created_at?->timezone(self::ZONA)->format('d/m/Y H:i'),
        ];
    }
}
