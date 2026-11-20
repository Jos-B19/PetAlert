<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'development'])) {
            throw new RuntimeException('UserSeeder solo puede ejecutarse en entornos local o development.');
        }

        $accounts = [
            [
                'config' => 'pet_alert.admin',
                'role' => User::ROLE_ADMINISTRADOR,
            ],
            [
                'config' => 'pet_alert.reception',
                'role' => User::ROLE_RECEPCION,
            ],
            [
                'config' => 'pet_alert.veterinarian',
                'role' => User::ROLE_VETERINARIO,
            ],
            [
                'config' => 'pet_alert.owner',
                'role' => User::ROLE_PROPIETARIO,
            ],
        ];

        DB::transaction(function () use ($accounts): void {
            foreach ($accounts as $account) {
                $configKey = $account['config'];
                $email = config($configKey.'.email');
                $password = config($configKey.'.password');

                if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
                    throw new RuntimeException("Configura el correo y la contraseña de {$configKey} en el archivo .env.");
                }

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => config($configKey.'.name'),
                        'password' => Hash::make($password),
                        'rol' => $account['role'],
                        'estado' => 'activo',
                    ],
                );

                if (! $user->wasRecentlyCreated && $user->rol !== $account['role']) {
                    throw new RuntimeException("La cuenta {$email} ya existe con un rol diferente; UserSeeder no modificó la cuenta.");
                }

                if (! $user->wasRecentlyCreated && $user->estado !== 'activo') {
                    throw new RuntimeException("La cuenta {$email} está inactiva; UserSeeder no reactivó la cuenta.");
                }

                if ($account['role'] === User::ROLE_PROPIETARIO && ! $user->propietario()->exists()) {
                    $user->propietario()->create([
                        'nombres' => 'Propietario',
                        'apellidos' => 'de pruebas',
                        'tipo_documento' => 'OTRO',
                        'numero_documento' => 'PA-TEST-OWNER-'.$user->id,
                        'telefono' => '0000000000',
                        'correo' => $email,
                        'estado' => 'activo',
                    ]);
                }
            }
        });
    }
}
