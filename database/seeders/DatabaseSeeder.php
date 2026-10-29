<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);

        // Superadministrador local (única cuenta con contraseña propia)
        $admin = User::firstOrCreate(
            ['email' => 'admin@migestion.com.ar'],
            [
                'name'     => 'Administrador',
                'apellido' => 'MiGestión',
                'password' => 'cambiar123',
                'origen'   => 'local',
                'activo'   => true,
            ]
        );

        $admin->syncRoles(['superadmin']);
    }
}
