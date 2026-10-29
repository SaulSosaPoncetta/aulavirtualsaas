<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'admin@migestion.com.ar'],
            [
                'name'                  => 'Administrador',
                'apellido'              => 'MiGestión',
                'password'              => 'cambiar123',
                'debe_cambiar_password' => true,
                'activo'                => true,
            ]
        );

        $admin->syncRoles(['superadmin']);
    }
}
