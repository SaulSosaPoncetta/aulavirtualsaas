<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Usuarios de prueba (solo para desarrollo local).
 * php artisan db:seed --class=UsuariosPruebaSeeder
 */
class UsuariosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            ['email' => 'docente@prueba.com', 'dni' => '30111222', 'name' => 'Laura', 'apellido' => 'Docente', 'rol' => 'docente'],
            ['email' => 'alumno@prueba.com',  'dni' => '45111222', 'name' => 'Juan',  'apellido' => 'Alumno',  'rol' => 'alumno'],
            ['email' => null,                 'dni' => '45333444', 'name' => 'Sofía', 'apellido' => 'SinEmail', 'rol' => 'alumno'],
        ];

        foreach ($usuarios as $u) {
            $user = User::updateOrCreate(
                ['dni' => $u['dni']],
                [
                    'email'                 => $u['email'],
                    'name'                  => $u['name'],
                    'apellido'              => $u['apellido'],
                    'password'              => 'prueba123',
                    'debe_cambiar_password' => false,
                    'activo'                => true,
                ]
            );

            $user->syncRoles([$u['rol']]);
        }
    }
}
