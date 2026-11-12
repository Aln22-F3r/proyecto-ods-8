<?php

namespace App\Providers;

use App\Models\Administrador;
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
        Gate::define('consultar', function ($admin) {
            return $admin instanceof Administrador
                && $admin->activo
                && in_array($admin->nivel, [1, 2, 3], true);
        });

        Gate::define('crear', function ($admin) {
            return $admin instanceof Administrador
                && $admin->activo
                && in_array($admin->nivel, [2, 3], true);
        });

        Gate::define('editar', function ($admin) {
            return $admin instanceof Administrador
                && $admin->activo
                && in_array($admin->nivel, [2, 3], true);
        });

        Gate::define('eliminar', function ($admin) {
            return $admin instanceof Administrador
                && $admin->activo
                && $admin->nivel === 3;
        });

        Gate::define('administrar-cuentas', function ($admin) {
            return $admin instanceof Administrador
                && $admin->activo
                && $admin->nivel === 3;
        });
    }
}
