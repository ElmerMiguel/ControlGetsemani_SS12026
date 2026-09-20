<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Catálogos base de ingresos y egresos
        $this->call([
            CatalogosSeeder::class,
            EstructuraSeeder::class,
        ]);

        // 2. Roles y permisos (Spatie)
        $this->call(RolesPermisosSeeder::class);

        // 3. Usuario Administrador General
        $this->call(AdminSeeder::class);

        // 4. Datos de demostración (solo en local si SEED_DEMO=true)
        $this->call(DemoSeeder::class);
    }
}
