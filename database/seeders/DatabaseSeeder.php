<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\ListaDeseo;
use App\Models\Log;
use App\Models\Notificacion;
use App\Models\OfertaEmpleo;
use App\Models\Perfil;
use App\Models\Postulacion;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    // Contraseña común para los 3 usuarios de prueba //
    private const PASSWORD = 'password123';

    public function run(): void
    {
        $this->command->info('Sembrando datos de prueba...');

        // ==================== ROLES (inamovibles) ==================== //

        $rolCandidato = Rol::updateOrCreate(
            ['nombre' => 'Candidato'],
            ['descripcion' => 'Usuario que busca empleo y se postula a ofertas.']
        );

        $rolEmpresa = Rol::updateOrCreate(
            ['nombre' => 'Empresa'],
            ['descripcion' => 'Usuario que publica ofertas y gestiona postulaciones.']
        );

        $rolAdmin = Rol::updateOrCreate(
            ['nombre' => 'Administrador'],
            ['descripcion' => 'Usuario con permisos administrativos por nivel.']
        );

        $this->command->info('  ✓ Roles: Candidato, Empresa, Administrador');

        // ==================== USUARIO ADMIN (nivel 3 para probar todo) ==================== //

        $admin = Usuario::updateOrCreate(
            ['correo' => 'admin@ejemplo.com'],
            [
                'rol_id'         => $rolAdmin->id,
                'password'       => Hash::make(self::PASSWORD),
                'nivel'          => 3,
                'estado'         => true,
                'fecha_registro' => now(),
            ]
        );

        Perfil::updateOrCreate(
            ['usuario_id' => $admin->id],
            [
                'nombre'    => 'Administrador',
                'apellido'  => 'del Sistema',
                'telefono'  => '5555555555',
                'ciudad'    => 'Ciudad de México',
            ]
        );

        $this->command->info('  ✓ Admin: admin@ejemplo.com (nivel 3)');

        // ==================== USUARIO EMPRESA ==================== //

        $empresa = Usuario::updateOrCreate(
            ['correo' => 'empresa@ejemplo.com'],
            [
                'rol_id'         => $rolEmpresa->id,
                'password'       => Hash::make(self::PASSWORD),
                'nivel'          => null,
                'estado'         => true,
                'fecha_registro' => now(),
            ]
        );

        Perfil::updateOrCreate(
            ['usuario_id' => $empresa->id],
            [
                'nombre'           => 'TechCorp S.A. de C.V.',
                'apellido'         => null,
                'telefono'         => '5551234567',
                'profesion_oficio' => 'Desarrollo de software',
                'descripcion'      => 'Empresa líder en soluciones tecnológicas.',
                'experiencia'      => 10,
                'habilidades'      => 'Desarrollo web, móvil y cloud.',
                'ciudad'           => 'Guadalajara',
            ]
        );

        $this->command->info('  ✓ Empresa: empresa@ejemplo.com');

        // ==================== USUARIO CANDIDATO (con CV para poder postularse) ==================== //

        $candidato = Usuario::updateOrCreate(
            ['correo' => 'candidato@ejemplo.com'],
            [
                'rol_id'         => $rolCandidato->id,
                'password'       => Hash::make(self::PASSWORD),
                'nivel'          => null,
                'estado'         => true,
                'fecha_registro' => now(),
            ]
        );

        $perfilCandidato = Perfil::updateOrCreate(
            ['usuario_id' => $candidato->id],
            [
                'nombre'           => 'Juan',
                'apellido'         => 'Pérez',
                'telefono'         => '5559876543',
                'profesion_oficio' => 'Desarrollador Full Stack',
                'descripcion'      => 'Desarrollador con 5 años de experiencia en Laravel y Vue.',
                'experiencia'      => 5,
                'habilidades'      => 'PHP, Laravel, JavaScript, Vue, MySQL.',
                'ciudad'           => 'Ciudad de México',
                'cv'               => '/storage/cv/demo_cv.pdf',   // ruta ficticia para pruebas; el archivo real no existe pero la validación solo verifica que no sea null //
            ]
        );

        $this->command->info('  ✓ Candidato: candidato@ejemplo.com (con CV en perfil)');

        // ==================== CATEGORÍA ==================== //

        $categoria = Categoria::updateOrCreate(
            ['nombre' => 'Tecnología'],
            ['descripcion' => 'Ofertas de desarrollo, sistemas e IT.']
        );

        $this->command->info('  ✓ Categoría: Tecnología');

        // ==================== OFERTA DE EMPLEO ==================== //

        $oferta = OfertaEmpleo::updateOrCreate(
            [
                'usuario_id' => $empresa->id,
                'titulo'     => 'Desarrollador Laravel Senior',
            ],
            [
                'categoria_id'      => $categoria->id,
                'descripcion'       => 'Buscamos desarrollador con experiencia en Laravel, Vue y APIs REST.',
                'ubicacion'         => 'Guadalajara, Jalisco (Híbrido)',
                'salario'           => 35000.00,
                'tipo_empleo'       => 'Tiempo completo',
                'fecha_publicacion' => now(),
                'estado'            => 'Abierta',
                'logo'              => null,
            ]
        );

        $this->command->info('  ✓ Oferta: "Desarrollador Laravel Senior" (Abierta)');

        // ==================== POSTULACIÓN (candidato → oferta) ==================== //

        $postulacion = Postulacion::updateOrCreate(
            [
                'usuario_id'       => $candidato->id,
                'oferta_empleo_id' => $oferta->id,
            ],
            [
                'cv'                => $perfilCandidato->cv,   // se copia el CV del perfil al momento de postularse //
                'fecha_postulacion' => now(),
                'estado'            => 'Pendiente',
                'comentario'        => 'Me interesa mucho esta oportunidad. Adjunto mi CV.',
            ]
        );

        $this->command->info('  ✓ Postulación: Juan Pérez → Desarrollador Laravel Senior (Pendiente)');

        // ==================== LISTA DE DESEOS (candidato → oferta) ==================== //

        ListaDeseo::updateOrCreate(
            [
                'usuario_id'       => $candidato->id,
                'oferta_empleo_id' => $oferta->id,
            ],
            ['fecha_agregado' => now()]
        );

        $this->command->info('  ✓ Lista de deseos: Juan Pérez → Desarrollador Laravel Senior');

        // ==================== NOTIFICACIÓN (para la empresa por la nueva postulación) ==================== //

        Notificacion::updateOrCreate(
            [
                'usuario_id' => $empresa->id,
                'tipo'       => 'postulacion_nueva',
                'titulo'     => 'Nueva postulación recibida',
            ],
            [
                'mensaje' => 'Juan Pérez se ha postulado a tu oferta «Desarrollador Laravel Senior».',
                'leida'   => false,
                'fecha'   => now(),
            ]
        );

        $this->command->info('  ✓ Notificación: Empresa recibió aviso de nueva postulación');

        // ==================== LOG (auditoría de la postulación) ==================== //

        Log::updateOrCreate(
            [
                'accion'      => 'crear-postulacion',
                'registro_id' => $postulacion->id,
            ],
            [
                'usuario_id'     => $candidato->id,
                'descripcion'    => 'Se postuló a la oferta «Desarrollador Laravel Senior».',
                'tabla_afectada' => 'postulaciones',
                'fecha'          => now(),
            ]
        );

        $this->command->info('  ✓ Log: postulación registrada en auditoría');

        // ==================== RESUMEN DE CREDENCIALES ==================== //

        $this->command->newLine();
        $this->command->info('╔═══════════════════════════════════════════════════════════╗');
        $this->command->info('║           DATOS DE PRUEBA LISTOS PARA POSTMAN             ║');
        $this->command->info('╠═══════════════════════════════════════════════════════════╣');
        $this->command->info('║ ADMIN (nivel 3 - todo):                                   ║');
        $this->command->info('║   correo: admin@ejemplo.com                               ║');
        $this->command->info('║   password: password123                                   ║');
        $this->command->info('║                                                           ║');
        $this->command->info('║ EMPRESA (gestiona sus ofertas):                           ║');
        $this->command->info('║   correo: empresa@ejemplo.com                             ║');
        $this->command->info('║   password: password123                                   ║');
        $this->command->info('║                                                           ║');
        $this->command->info('║ CANDIDATO (se postula a ofertas):                         ║');
        $this->command->info('║   correo: candidato@ejemplo.com                           ║');
        $this->command->info('║   password: password123                                   ║');
        $this->command->info('╚═══════════════════════════════════════════════════════════╝');
    }
}
