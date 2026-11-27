<?php

namespace App\Services;

use App\Models\Log;
use Throwable;

class LogService
{
    // Registra un log de auditoría; nunca rompe el flujo principal si falla //
    public static function registrar(
        ?int $usuarioId,
        string $accion,
        ?string $descripcion = null,
        ?string $tablaAfectada = null,
        ?int $registroId = null
    ): void {
        try {
            Log::create([
                'usuario_id'    => $usuarioId,
                'accion'        => $accion,
                'descripcion'   => $descripcion,
                'tabla_afectada' => $tablaAfectada,
                'registro_id'   => $registroId,
                'fecha'         => now(),
            ]);
        } catch (Throwable $e) {
            report($e);   // se loguea pero no interrumpe la operación principal //
        }
    }
}
