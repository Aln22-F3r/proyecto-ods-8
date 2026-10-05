<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\ListaDeseo;
use App\Models\Log;
use App\Models\LoginSocial;
use App\Models\OfertaEmpleo;
use App\Models\Perfil;
use App\Models\Postulacion;
use App\Models\Rol;
use App\Models\Transaccion;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Model::unguard();
        $faker = fake('es_MX');

        $ciudades = ['Guadalajara', 'Zapopan', 'Tlaquepaque', 'Tonalá', 'Tlajomulco de Zúñiga'];

        // ROLES (a mano)
        $roles = [
            ['Administrador', 'Acceso total al sistema'],
            ['Empresa', 'Publica y gestiona ofertas de empleo'],
            ['Candidato', 'Busca y se postula a ofertas'],
            ['Reclutador', 'Revisa postulaciones y contacta candidatos'],
            ['Moderador', 'Revisa y aprueba el contenido publicado'],
            ['Soporte técnico', 'Atiende incidencias de los usuarios'],
            ['Analista', 'Consulta reportes y estadísticas'],
            ['Auditor', 'Revisa el historial de acciones del sistema'],
            ['Capacitador', 'Administra cursos y recursos de formación'],
            ['Invitado', 'Consulta ofertas sin poder postularse'],
        ];
        foreach ($roles as [$nombre, $descripcion]) {
            Rol::create(['nombre' => $nombre, 'descripcion' => $descripcion]);
        }

        // CATEGORÍAS (a mano)
        $categorias = [
            ['Tecnología', 'Desarrollo de software, soporte y sistemas'],
            ['Construcción', 'Oficios y proyectos de construcción'],
            ['Diseño', 'Diseño gráfico, web y audiovisual'],
            ['Salud', 'Atención médica y cuidado de pacientes'],
            ['Educación', 'Docencia y apoyo educativo'],
            ['Ventas', 'Atención comercial y venta de productos'],
            ['Administración', 'Gestión de oficina, contabilidad y finanzas'],
            ['Gastronomía', 'Cocina y servicio en restaurantes'],
            ['Transporte y logística', 'Reparto, almacén y distribución'],
            ['Manufactura', 'Producción industrial y mantenimiento'],
        ];
        foreach ($categorias as [$nombre, $descripcion]) {
            Categoria::create(['nombre' => $nombre, 'descripcion' => $descripcion]);
        }

        // USUARIOS (1 administrador, 4 empresas, 10 candidatos)
        $password = Hash::make('password');
        for ($i = 1; $i <= 15; $i++) {
            Usuario::create([
                'rol_id' => $i === 1 ? 1 : ($i <= 5 ? 2 : 3),
                'nombre' => $faker->firstName(),
                'apellido' => $faker->lastName(),
                'correo' => $faker->unique()->safeEmail(),
                'password' => $password,
                'telefono' => $faker->numerify('33########'),
                'fecha_registro' => $faker->dateTimeBetween('-1 year', 'now'),
                'estado' => $faker->boolean(90),
            ]);
        }

        // PERFILES (uno por cada candidato, con profesión y habilidades coherentes)
        $profesiones = [
            ['Desarrollador web', 'PHP, Laravel, React, MySQL'],
            ['Diseñador gráfico', 'Photoshop, Illustrator, Figma'],
            ['Electricista', 'Instalación eléctrica, mantenimiento, lectura de planos'],
            ['Enfermero(a)', 'Atención al paciente, toma de signos vitales, primeros auxilios'],
            ['Profesor de inglés', 'Planeación de clases, comunicación, manejo de grupos'],
            ['Cocinero', 'Preparación de alimentos, higiene, control de inventario'],
            ['Auxiliar administrativo', 'Excel, archivo, atención telefónica'],
            ['Chofer repartidor', 'Manejo, rutas de entrega, atención al cliente'],
            ['Ejecutivo de ventas', 'Negociación, atención al cliente, prospección'],
            ['Técnico de mantenimiento', 'Mantenimiento preventivo, soldadura, mecánica básica'],
        ];
        foreach (Usuario::where('rol_id', 3)->get() as $i => $usuario) {
            [$profesion, $habilidades] = $profesiones[$i];
            Perfil::create([
                'usuario_id' => $usuario->id,
                'profesion_oficio' => $profesion,
                'descripcion' => "{$profesion} con experiencia y disponibilidad inmediata. " . $faker->sentence(),
                'experiencia' => $faker->numberBetween(0, 15),
                'habilidades' => $habilidades,
                'ciudad' => $faker->randomElement($ciudades),
                'cv' => 'cv/' . Str::slug($usuario->nombre . ' ' . $usuario->apellido) . '.pdf',
            ]);
        }

        // OFERTAS DE EMPLEO (títulos que corresponden a cada categoría)
        $ofertas = [
            1 => ['Desarrollador web', 'Soporte técnico', 'Analista de datos'],
            2 => ['Albañil', 'Electricista', 'Supervisor de obra'],
            3 => ['Diseñador gráfico', 'Diseñador UX'],
            4 => ['Enfermero(a)', 'Recepcionista de clínica'],
            5 => ['Profesor de inglés', 'Asistente educativo'],
            6 => ['Ejecutivo de ventas', 'Vendedor de mostrador'],
            7 => ['Auxiliar administrativo', 'Asistente contable'],
            8 => ['Cocinero', 'Mesero'],
            9 => ['Chofer repartidor', 'Auxiliar de almacén'],
            10 => ['Operador de producción', 'Técnico de mantenimiento'],
        ];
        foreach ($ofertas as $categoriaId => $titulos) {
            foreach ($titulos as $titulo) {
                $empresa = $faker->company();
                OfertaEmpleo::create([
                    'categoria_id' => $categoriaId,
                    'titulo' => $titulo,
                    'empresa' => $empresa,
                    'descripcion' => "Buscamos {$titulo} para integrarse al equipo de {$empresa}. " . $faker->sentence(),
                    'ubicacion' => $faker->randomElement($ciudades),
                    'salario' => $faker->randomFloat(2, 7000, 25000),
                    'tipo_empleo' => $faker->randomElement(['Tiempo completo', 'Medio tiempo', 'Por obra']),
                    'fecha_publicacion' => $faker->dateTimeBetween('-3 months', 'now'),
                    'estado' => $faker->randomElement(['Abierta', 'Abierta', 'Cerrada']),
                ]);
            }
        }

        $usuarioIds = Usuario::pluck('id')->all();
        $candidatoIds = Usuario::where('rol_id', 3)->pluck('id')->all();
        $ofertaIds = OfertaEmpleo::pluck('id')->all();

        // POSTULACIONES (20, sin repetir candidato y oferta)
        $pares = [];
        while (count($pares) < 20) {
            $u = $faker->randomElement($candidatoIds);
            $o = $faker->randomElement($ofertaIds);
            $pares["$u-$o"] = [$u, $o];
        }
        foreach ($pares as [$u, $o]) {
            Postulacion::create([
                'usuario_id' => $u,
                'oferta_empleo_id' => $o,
                'fecha_postulacion' => $faker->dateTimeBetween('-2 months', 'now'),
                'estado' => $faker->randomElement(['En revisión', 'Aceptada', 'Rechazada']),
                'comentario' => $faker->sentence(),
            ]);
        }

        // LISTA DE DESEOS (12, sin repetir)
        $pares = [];
        while (count($pares) < 12) {
            $u = $faker->randomElement($candidatoIds);
            $o = $faker->randomElement($ofertaIds);
            $pares["$u-$o"] = [$u, $o];
        }
        foreach ($pares as [$u, $o]) {
            ListaDeseo::create([
                'usuario_id' => $u,
                'oferta_empleo_id' => $o,
                'fecha_agregado' => $faker->dateTimeBetween('-2 months', 'now'),
            ]);
        }

        // TRANSACCIONES (15)
        $tipos = [
            'Registro' => 'Alta de usuario en el sistema',
            'Publicación' => 'Publicación de una oferta de empleo',
            'Postulación' => 'Postulación a una oferta de empleo',
        ];
        for ($i = 0; $i < 15; $i++) {
            $tipo = $faker->randomElement(array_keys($tipos));
            Transaccion::create([
                'usuario_id' => $faker->randomElement($usuarioIds),
                'tipo_registro' => $tipo,
                'descripcion' => $tipos[$tipo],
                'fecha' => $faker->dateTimeBetween('-2 months', 'now'),
                'estado' => $faker->randomElement(['Pendiente', 'Completada']),
            ]);
        }

        // LOGIN SOCIALES (10, uno por usuario, con el correo del usuario)
        foreach (Usuario::take(10)->get() as $usuario) {
            LoginSocial::create([
                'usuario_id' => $usuario->id,
                'proveedor' => $faker->randomElement(['Google', 'Facebook', 'GitHub']),
                'proveedor_id' => $faker->numerify('####################'),
                'correo' => $usuario->correo,
            ]);
        }

        // LOGS (20)
        $acciones = [
            'Inició sesión',
            'Cerró sesión',
            'Publicó una oferta de empleo',
            'Se postuló a una oferta',
            'Actualizó su perfil',
            'Agregó una oferta a su lista de deseos',
        ];
        for ($i = 0; $i < 20; $i++) {
            Log::create([
                'usuario_id' => $faker->randomElement($usuarioIds),
                'accion' => $faker->randomElement($acciones),
                'fecha' => $faker->dateTimeBetween('-1 month', 'now'),
                'ip' => $faker->ipv4(),
            ]);
        }
    }
}
