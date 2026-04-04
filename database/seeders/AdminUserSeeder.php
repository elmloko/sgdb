<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@sgdb.local'],
            [
                'name'       => 'Admin',
                'password'   => Hash::make('admin1234'),
                'rol_global' => 'admin',
            ]
        );

        $this->command->info('Usuario admin creado/actualizado: admin@sgdb.local');
    }
}
