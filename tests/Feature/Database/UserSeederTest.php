<?php

namespace Tests\Feature\Database;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_seeders_are_repeatable_without_changing_existing_passwords_or_duplicating_owner_profiles(): void
    {
        $this->configureSeedAccounts();

        $this->seed(\Database\Seeders\UserSeeder::class);

        $admin = User::where('email', 'admin@petalert.test')->firstOrFail();
        $this->assertSame('administrador', $admin->rol);
        $this->assertSame('activo', $admin->estado);
        $this->assertTrue(Hash::check('initial-secret', $admin->password));
        $this->assertSame(4, User::count());
        $ownerUser = User::where('email', 'owner@petalert.test')->firstOrFail();
        $this->assertSame($this->configuredOwner()->id, $ownerUser->propietario->id);

        $admin->forceFill(['password' => Hash::make('changed-secret')])->save();
        $this->seed(\Database\Seeders\UserSeeder::class);

        $this->assertSame(4, User::count());
        $this->assertSame(1, $ownerUser->fresh()->propietario()->count());
        $this->assertTrue(Hash::check('changed-secret', $admin->fresh()->password));
    }

    public function test_role_seeder_requires_configured_passwords(): void
    {
        $this->configureSeedAccounts();
        config(['pet_alert.admin.password' => null]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('pet_alert.admin');

        $this->seed(\Database\Seeders\UserSeeder::class);
    }

    public function test_role_seeder_is_restricted_to_local_and_development_environments(): void
    {
        $this->configureSeedAccounts();
        $this->app['env'] = 'testing';

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('local o development');

        $this->seed(\Database\Seeders\UserSeeder::class);
    }

    public function test_seed_does_not_promote_an_existing_account_with_a_conflicting_role(): void
    {
        $this->configureSeedAccounts();
        $existing = User::factory()->create([
            'email' => 'admin@petalert.test',
            'rol' => User::ROLE_PROPIETARIO,
            'password' => Hash::make('existing-password'),
        ]);

        try {
            $this->seed(\Database\Seeders\UserSeeder::class);
            $this->fail('An account with a conflicting role should stop the seeder.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('rol diferente', $exception->getMessage());
        }

        $this->assertSame(User::ROLE_PROPIETARIO, $existing->fresh()->rol);
        $this->assertTrue(Hash::check('existing-password', $existing->fresh()->password));
    }

    private function configureSeedAccounts(): void
    {
        $this->app['env'] = 'local';
        config([
            'pet_alert.admin.name' => 'Administrador',
            'pet_alert.admin.email' => 'admin@petalert.test',
            'pet_alert.admin.password' => 'initial-secret',
            'pet_alert.reception.name' => 'Recepcionista',
            'pet_alert.reception.email' => 'reception@petalert.test',
            'pet_alert.reception.password' => 'reception-secret',
            'pet_alert.veterinarian.name' => 'Veterinario',
            'pet_alert.veterinarian.email' => 'vet@petalert.test',
            'pet_alert.veterinarian.password' => 'veterinarian-secret',
            'pet_alert.owner.name' => 'Propietario de pruebas',
            'pet_alert.owner.email' => 'owner@petalert.test',
            'pet_alert.owner.password' => 'owner-secret',
        ]);
    }

    private function configuredOwner(): \App\Models\Propietario
    {
        return \App\Models\Propietario::where('correo', 'owner@petalert.test')->firstOrFail();
    }
}
