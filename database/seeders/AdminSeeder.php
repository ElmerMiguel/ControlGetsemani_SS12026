<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Crea o actualiza el usuario Administrador General inicial desde las variables de entorno.
     */
    public function run(): void
    {
        $name = config('app.admin_name') ?? env('ADMIN_NAME', 'Administrador General');
        $email = config('app.admin_email') ?? env('ADMIN_EMAIL', 'admin@getsemani.test');
        $password = config('app.admin_password') ?? env('ADMIN_PASSWORD');

        if (empty($password)) {
            throw new RuntimeException('La variable de entorno ADMIN_PASSWORD no está definida o está vacía en el archivo .env.');
        }

        $admin = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'activo' => true,
                'must_change_password' => true,
            ]
        );

        // Si ya existía pero con otros datos o sin rol
        if (! $admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
    }
}
