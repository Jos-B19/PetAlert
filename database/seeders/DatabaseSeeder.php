<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            PropietarioSeeder::class,
            MascotaSeeder::class,
            HistorialClinicoSeeder::class,
            UrgenciaSeeder::class,
            AtencionSeeder::class,
        ]);
    }
}