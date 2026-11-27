<?php

namespace App\Observers;

use App\Models\Perfil;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PerfilObserver
{
    // Se dispara con $perfil->delete() o forceDelete() en Eloquent //

    public function deleting(Perfil $perfil): void
    {
        $this->borrarArchivos($perfil);   // limpia cv y foto del disco antes de borrar la fila //
    }

    private function borrarArchivos(Perfil $perfil): void
    {
        // Itera cv y foto y borra cada archivo si existe en disco //
        foreach (['cv', 'foto'] as $campo) {
            $ruta = $this->relativa($perfil->{$campo});

            if ($ruta && Storage::disk('public')->exists($ruta)) {
                Storage::disk('public')->delete($ruta);
            }
        }
    }

    private function relativa(?string $ruta): ?string
    {
        // /storage/cv/x.pdf → cv/x.pdf (formato que entiende Storage::disk) //
        return $ruta ? Str::after($ruta, '/storage/') : null;
    }
}
