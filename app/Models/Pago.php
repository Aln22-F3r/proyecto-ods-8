<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pago extends Model
{
    use SoftDeletes;   // borrado lógico: un pago aprobado no debería borrarse fácil //

    protected $table = 'pagos';

    protected $fillable = [
        'usuario_id',
        'oferta_empleo_id',
        'monto',
        'moneda',
        'concepto',
        'metodo_pago',
        'estado',
        'referencia_externa',
        'metadata',
        'fecha_pago',
    ];

    protected $casts = [
        'monto'      => 'decimal:2',
        'metadata'   => 'array',        // json de Stripe se convierte a array PHP //
        'fecha_pago' => 'datetime',
    ];

    // Estados posibles de un pago //
    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_APROBADO  = 'aprobado';
    public const ESTADO_RECHAZADO = 'rechazado';
    public const ESTADO_CANCELADO = 'cancelado';

    public function usuario(): BelongsTo          // empresa que pagó //
    {
        return $this->belongsTo(Usuario::class)->withTrashed();
    }

    public function ofertaEmpleo(): BelongsTo     // oferta que se está destacando //
    {
        return $this->belongsTo(OfertaEmpleo::class)->withTrashed();
    }

    // Helpers de estado para usar en el controlador //
    public function estaPendiente(): bool
    {
        return $this->estado === self::ESTADO_PENDIENTE;
    }
    public function estaAprobado(): bool
    {
        return $this->estado === self::ESTADO_APROBADO;
    }
}
