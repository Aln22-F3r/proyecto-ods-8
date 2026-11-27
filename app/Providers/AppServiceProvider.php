<?php

namespace App\Providers;

use App\Models\Perfil;
use App\Models\Usuario;
use App\Observers\PerfilObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Registra el observer para limpiar cv/foto al borrar perfiles //
        Perfil::observe(PerfilObserver::class);

        // Helper: valida que el usuario sea admin activo con nivel 1, 2 o 3 //
        $esAdministrador = function ($usuario): bool {
            return $usuario instanceof Usuario
                && $usuario->estado
                && $usuario->rol?->nombre === 'Administrador'
                && in_array($usuario->nivel, [1, 2, 3], true);
        };

        // === Permisos administrativos por nivel === //

        // Nivel 1, 2, 3 pueden consultar //
        Gate::define('consultar', fn($usuario) => $esAdministrador($usuario));

        // Nivel 2 y 3 pueden crear //
        Gate::define('crear', fn($usuario) => $esAdministrador($usuario)
            && in_array($usuario->nivel, [2, 3], true));

        // Nivel 2 y 3 pueden editar //
        Gate::define('editar', fn($usuario) => $esAdministrador($usuario)
            && in_array($usuario->nivel, [2, 3], true));

        // Solo nivel 3 puede eliminar (lógico y físico) //
        Gate::define('eliminar', fn($usuario) => $esAdministrador($usuario)
            && $usuario->nivel === 3);

        // Solo nivel 3 puede administrar cuentas sensibles //
        Gate::define('administrar-cuentas', fn($usuario) => $esAdministrador($usuario)
            && $usuario->nivel === 3);

        // === Permisos por tipo de cuenta (módulos /empresa y /candidato) === //

        Gate::define('es-administrador', fn($usuario) => $esAdministrador($usuario));

        // Rol Candidato activo //
        Gate::define('es-candidato', fn($usuario) => $usuario instanceof Usuario
            && $usuario->estado
            && $usuario->rol?->nombre === 'Candidato');

        // Rol Empresa activa //
        Gate::define('es-empresa', fn($usuario) => $usuario instanceof Usuario
            && $usuario->estado
            && $usuario->rol?->nombre === 'Empresa');
    }
}
