<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;

/**
 * Traduce los errores de base de datos (MySQL/MariaDB) a avisos claros.
 * Uso: return $this->errorBD($e, 'guardar el usuario');
 */
trait ManejaErroresBD
{
    protected function errorBD(QueryException $e, string $accion): JsonResponse
    {
        report($e);

        // Código propio del driver (MySQL/MariaDB), no el SQLSTATE. //
        $codigo = $e->errorInfo[1] ?? null;

        [$estado, $mensaje] = match ($codigo) {
            1062 => [409, "No se pudo {$accion}: ya existe un registro con un valor que debe ser único."],
            1451 => [409, "No se pudo {$accion}: tiene registros relacionados que lo impiden."],
            1452 => [422, "No se pudo {$accion}: una de las referencias indicadas no existe."],
            1048 => [422, "No se pudo {$accion}: falta un dato obligatorio."],
            1406, 1264, 1265 => [422, "No se pudo {$accion}: un dato excede la longitud o el formato permitido."],
            1205, 1213 => [503, "No se pudo {$accion}: el sistema está ocupado, intenta de nuevo en unos segundos."],
            2002, 2006, 2013 => [503, "No se pudo {$accion}: no hay conexión con la base de datos."],
            default => [500, "No se pudo {$accion}. Intenta de nuevo o contacta a soporte."],
        };

        return response()->json(['message' => $mensaje], $estado);
    }
}
