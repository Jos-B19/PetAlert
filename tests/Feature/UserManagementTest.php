<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_manage_users_and_passwords_are_hashed(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $this->actingAs($administrator);

        $this->post('/users', [
            'name' => 'Veterinario de turno',
            'email' => 'vet@example.test',
            'telefono' => '3001234567',
            'tipo_documento' => 'CC',
            'numero_documento' => '33445566',
            'cargo' => 'Médico veterinario',
            'direccion' => 'Ipiales',
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
            'password' => 'PetAlertSecure2026',
            'password_confirmation' => 'PetAlertSecure2026',
        ])->assertRedirect('/users');

        $veterinarian = User::where('email', 'vet@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('PetAlertSecure2026', $veterinarian->password));
        $this->assertDatabaseHas('users', [
            'id' => $veterinarian->id,
            'telefono' => '3001234567',
            'tipo_documento' => 'CC',
            'numero_documento' => '33445566',
            'cargo' => 'Médico veterinario',
        ]);

        $this->get('/users')->assertOk()->assertInertia(fn ($page) => $page
            ->where('screen', 'users.index')
            ->missing('users.data.0.password'));

        $this->actingAs($veterinarian)->get('/users')->assertForbidden();
    }

    public function test_administrator_cannot_change_own_role_or_disable_the_last_admin(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $this->actingAs($administrator);

        $this->put("/users/{$administrator->id}", [
            'name' => $administrator->name,
            'email' => $administrator->email,
            'telefono' => '3001234567',
            'tipo_documento' => 'CC',
            'numero_documento' => '12345678',
            'cargo' => 'Administrador',
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ])->assertSessionHasErrors('rol');

        $this->patch("/users/{$administrator->id}/toggle-status")
            ->assertSessionHas('error');

        $this->assertSame('activo', $administrator->fresh()->estado);
    }

    public function test_public_registration_does_not_grant_user_management_access(): void
    {
        $this->post('/register', [
            'nombres' => 'Laura',
            'apellidos' => 'Gómez',
            'tipo_documento' => 'CC',
            'numero_documento' => '123456789',
            'telefono' => '3001234567',
            'email' => 'laura@example.test',
            'password' => 'PetAlertSecure2026',
            'password_confirmation' => 'PetAlertSecure2026',
        ])->assertRedirect(route('account.show'));

        $this->get('/users')->assertForbidden();
    }

    public function test_internal_staff_cannot_access_the_owner_account_page(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);

        $this->actingAs($administrator)
            ->get('/mi-cuenta')
            ->assertForbidden();
    }

    public function test_worker_with_clinical_records_is_deactivated_instead_of_deleted(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $worker = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $owner = \App\Models\Propietario::create([
            'nombres' => 'María',
            'apellidos' => 'Rojas',
            'tipo_documento' => 'CC',
            'numero_documento' => '44556677',
            'telefono' => '3002223344',
        ]);
        $pet = \App\Models\Mascota::create([
            'propietario_id' => $owner->id,
            'nombre' => 'Mora',
            'especie' => 'Canino',
            'estado' => 'activo',
        ]);
        \App\Models\Urgencia::create([
            'mascota_id' => $pet->id,
            'registrado_por' => $worker->id,
            'tipo_urgencia' => 'Consulta',
            'descripcion' => 'Evaluación',
            'nivel_prioridad' => 'media',
            'estado' => 'pendiente',
            'fecha_ingreso' => now(),
        ]);

        $this->actingAs($administrator)
            ->delete("/users/{$worker->id}")
            ->assertRedirect('/users');

        $this->assertDatabaseHas('users', ['id' => $worker->id, 'estado' => 'inactivo']);
        $this->assertDatabaseHas('urgencias', ['registrado_por' => $worker->id]);
    }

    public function test_worker_without_clinical_records_can_be_deleted(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $worker = User::factory()->create([
            'rol' => User::ROLE_RECEPCION,
            'estado' => 'activo',
        ]);

        $this->actingAs($administrator)
            ->delete("/users/{$worker->id}")
            ->assertRedirect('/users');

        $this->assertDatabaseMissing('users', ['id' => $worker->id]);
    }

    public function test_administrator_cannot_create_a_public_owner_from_worker_management(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);

        $this->actingAs($administrator)
            ->post('/users', [
                'name' => 'Cuenta propietaria',
                'email' => 'owner@example.test',
                'telefono' => '3001234567',
                'tipo_documento' => 'CC',
                'numero_documento' => '99887766',
                'cargo' => 'Propietario',
                'rol' => User::ROLE_PROPIETARIO,
                'estado' => 'activo',
                'password' => 'PetAlertSecure2026',
                'password_confirmation' => 'PetAlertSecure2026',
            ])
            ->assertSessionHasErrors('rol');

        $this->assertDatabaseMissing('users', ['email' => 'owner@example.test']);
    }
}
