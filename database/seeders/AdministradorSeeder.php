<?php

namespace Database\Seeders;

use App\Models\Administrador;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdministradorSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('ADMIN_SEED_PASSWORD');

        if (!$password) {
            $this->command->error('Define ADMIN_SEED_PASSWORD en el archivo .env antes de ejecutar este seeder.');
            return;
        }

        $administradores = [
            ['Administrador General', 'admin', true],
            ['Laura Méndez', 'lmendez', true],
            ['Jorge Ruiz', 'jruiz', false],
        ];

        foreach ($administradores as [$nombre, $usuario, $activo]) {
            Administrador::updateOrCreate(
                ['usuario' => $usuario],
                [
                    'nombre' => $nombre,
                    'password' => Hash::make($password),
                    'activo' => $activo,
                ]
            );
        }
    }
}
