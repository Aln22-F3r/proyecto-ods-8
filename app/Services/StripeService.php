<?php

namespace App\Services;

use App\Models\Pago;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class StripeService
{
    // Configura el SDK de Stripe con la clave secreta del .env //
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    // Crea un PaymentIntent en Stripe y devuelve el objeto completo //
    public function crearPaymentIntent(
        int $ofertaId,
        int $usuarioId,
        float $monto,
        string $moneda,
        string $concepto
    ): PaymentIntent {
        return PaymentIntent::create([
            'amount'      => (int) round($monto * 100),          // Stripe usa centavos //
            'currency'    => strtolower($moneda),
            'metadata'    => [
                'oferta_id'  => $ofertaId,
                'usuario_id' => $usuarioId,
                'concepto'   => $concepto,
            ],
            'description' => $concepto,
            'automatic_payment_methods' => ['enabled' => true],  // acepta tarjeta + otros métodos //
        ]);
    }

    // Recupera un PaymentIntent por su ID (pi_xxx) para verificar estado real //
    public function obtenerPaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    // Cancela un PaymentIntent (rollback si falla la persistencia en BD) //
    public function cancelarPaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId)->cancel();
    }

    // ¿El PaymentIntent fue pagado exitosamente? //
    public function estaPagado(PaymentIntent $intent): bool
    {
        return $intent->status === 'succeeded';
    }

    // Convierte el PaymentIntent a array para guardarlo en `metadata` (auditoría) //
    public function aArray(PaymentIntent $intent): array
    {
        return $intent->toArray();
    }
}
