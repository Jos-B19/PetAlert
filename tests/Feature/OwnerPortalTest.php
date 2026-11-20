<?php

namespace Tests\Feature;

use App\Models\Atencion;
use App\Models\Mascota;
use App\Models\Urgencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_pet_registration_with_only_the_required_profile_fields(): void
    {
        $ownerUser = User::factory()->create([
            'rol' => User::ROLE_PROPIETARIO,
            'estado' => 'activo',
        ]);
        $owner = $ownerUser->propietario()->create($this->ownerData('Laura'));

        $this->actingAs($ownerUser)
            ->get(route('account.pets.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('screen', 'pets.create')
                ->where('ownerProfile.id', $owner->id)
                ->where('ownerProfile.nombres', 'Laura')
                ->where('ownerProfile.estado', 'activo')
                ->missing('ownerProfile.correo')
                ->missing('ownerProfile.direccion'));
    }

    public function test_owner_can_view_and_update_only_owned_pets(): void
    {
        $ownerUser = User::factory()->create([
            'rol' => User::ROLE_PROPIETARIO,
            'estado' => 'activo',
        ]);
        $owner = $ownerUser->propietario()->create($this->ownerData('Laura'));
        $otherOwnerUser = User::factory()->create([
            'rol' => User::ROLE_PROPIETARIO,
            'estado' => 'activo',
        ]);
        $otherOwner = $otherOwnerUser->propietario()->create($this->ownerData('Camila'));
        $ownedPet = $owner->mascotas()->create([
            'nombre' => 'Nala',
            'especie' => 'Canino',
            'estado' => 'activo',
        ]);
        $otherPet = $otherOwner->mascotas()->create([
            'nombre' => 'Milo',
            'especie' => 'Felino',
            'estado' => 'activo',
        ]);

        $this->actingAs($ownerUser)
            ->get(route('account.pets.show', $ownedPet))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('screen', 'pets.show')
                ->where('ownerProfile.id', $owner->id)
                ->where('pet.nombre', 'Nala'));

        $this->get(route('account.pets.edit', $ownedPet))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('screen', 'pets.edit')
                ->where('ownerProfile.id', $owner->id)
                ->where('pet.id', $ownedPet->id));

        $this->get(route('account.pets.show', $otherPet))->assertNotFound();

        $this->put(route('account.pets.update', $otherPet), [
            'nombre' => 'No autorizado',
            'especie' => 'Felino',
        ])->assertNotFound();

        $this->assertDatabaseHas('mascotas', [
            'id' => $otherPet->id,
            'nombre' => 'Milo',
            'propietario_id' => $otherOwner->id,
        ]);
    }

    public function test_owner_can_update_their_own_profile_without_changing_their_role(): void
    {
        $ownerUser = User::factory()->create([
            'rol' => User::ROLE_PROPIETARIO,
            'estado' => 'activo',
            'email' => 'laura@example.test',
        ]);
        $owner = $ownerUser->propietario()->create($this->ownerData('Laura'));

        $this->actingAs($ownerUser)
            ->patch(route('account.profile.update'), [
                'nombres' => 'Laura María',
                'apellidos' => 'Gómez',
                'tipo_documento' => 'CC',
                'numero_documento' => '12345678',
                'telefono' => '3117654321',
                'email' => 'laura.updated@example.test',
                'rol' => User::ROLE_ADMINISTRADOR,
            ])
            ->assertRedirect(route('account.show'));

        $this->assertDatabaseHas('propietarios', [
            'id' => $owner->id,
            'nombres' => 'Laura María',
            'apellidos' => 'Gómez',
            'telefono' => '3117654321',
            'correo' => 'laura.updated@example.test',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $ownerUser->id,
            'name' => 'Laura María Gómez',
            'email' => 'laura.updated@example.test',
            'rol' => User::ROLE_PROPIETARIO,
        ]);
    }

    public function test_owner_can_register_owned_pets_and_urgencies_without_assigning_owner_or_status(): void
    {
        $ownerUser = User::factory()->create([
            'rol' => User::ROLE_PROPIETARIO,
            'estado' => 'activo',
        ]);
        $owner = $ownerUser->propietario()->create($this->ownerData('Laura'));
        $otherOwnerUser = User::factory()->create([
            'rol' => User::ROLE_PROPIETARIO,
            'estado' => 'activo',
        ]);
        $otherOwner = $otherOwnerUser->propietario()->create($this->ownerData('Camila'));
        $otherPet = $otherOwner->mascotas()->create([
            'nombre' => 'Milo',
            'especie' => 'Felino',
            'estado' => 'activo',
        ]);

        $this->actingAs($ownerUser)
            ->post(route('account.pets.store'), [
                'propietario_id' => $otherOwner->id,
                'estado' => 'inactivo',
                'nombre' => 'Nala',
                'especie' => 'Canino',
            ])
            ->assertRedirect();

        $ownedPet = Mascota::query()->where('nombre', 'Nala')->firstOrFail();
        $this->assertSame($owner->id, $ownedPet->propietario_id);
        $this->assertSame('activo', $ownedPet->estado);

        $this->post(route('account.urgencies.store'), [
            'mascota_id' => $ownedPet->id,
            'tipo_urgencia' => 'Consulta urgente',
            'descripcion' => 'Reporte enviado por su propietario.',
            'nivel_prioridad' => 'alta',
            'estado' => 'finalizada',
            'veterinario_id' => $ownerUser->id,
        ])->assertRedirect();

        $urgency = Urgencia::query()->where('mascota_id', $ownedPet->id)->firstOrFail();
        $this->assertSame(User::ROLE_PROPIETARIO, $urgency->registradoPor->rol);
        $this->assertSame('pendiente', $urgency->estado);
        $this->assertNull($urgency->veterinario_id);

        $this->post(route('account.urgencies.store'), [
            'mascota_id' => $otherPet->id,
            'tipo_urgencia' => 'No autorizada',
            'descripcion' => 'Debe rechazarse.',
            'nivel_prioridad' => 'media',
        ])->assertNotFound();

        $this->assertSame(1, Urgencia::count());
    }

    public function test_owner_urgency_view_omits_private_clinical_notes(): void
    {
        $ownerUser = User::factory()->create([
            'rol' => User::ROLE_PROPIETARIO,
            'estado' => 'activo',
        ]);
        $owner = $ownerUser->propietario()->create($this->ownerData('Laura'));
        $pet = $owner->mascotas()->create([
            'nombre' => 'Nala',
            'especie' => 'Canino',
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $urgency = $pet->urgencias()->create([
            'registrado_por' => $ownerUser->id,
            'tipo_urgencia' => 'Revisión',
            'descripcion' => 'Dificultad para caminar.',
            'nivel_prioridad' => 'media',
            'estado' => 'en_atencion',
            'fecha_ingreso' => now(),
        ]);
        Atencion::create([
            'urgencia_id' => $urgency->id,
            'veterinario_id' => $veterinarian->id,
            'diagnostico' => 'Nota clínica privada',
            'observaciones' => 'Observación clínica privada',
            'fecha_atencion' => now(),
            'estado' => 'en_proceso',
        ]);

        $this->actingAs($ownerUser)
            ->get(route('account.urgencies.show', $urgency))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('ownerProfile.id', $owner->id)
                ->where('urgency.estado', 'en_atencion')
                ->where('attentions.0.estado', 'en_proceso')
                ->missing('attentions.0.diagnostico')
                ->missing('attentions.0.observaciones'));

        $this->get(route('account.show'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.urgencies', 1)
                ->where('stats.activeUrgencies', 1));

        $this->actingAs($ownerUser)->get("/histories/{$pet->id}")->assertForbidden();
    }

    public function test_administrator_can_deactivate_owner_and_linked_account(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $ownerUser = User::factory()->create([
            'rol' => User::ROLE_PROPIETARIO,
            'estado' => 'activo',
        ]);
        $owner = $ownerUser->propietario()->create($this->ownerData('Laura'));

        $this->actingAs($administrator)
            ->patch(route('owners.toggle-status', $owner))
            ->assertRedirect();

        $this->assertDatabaseHas('propietarios', ['id' => $owner->id, 'estado' => 'inactivo']);
        $this->assertDatabaseHas('users', ['id' => $ownerUser->id, 'estado' => 'inactivo']);

        $this->actingAs($ownerUser)->get(route('account.show'))->assertForbidden();
    }

    /**
     * @return array<string, string>
     */
    private function ownerData(string $name): array
    {
        return [
            'nombres' => $name,
            'apellidos' => 'Propietario',
            'tipo_documento' => 'CC',
            'numero_documento' => $name === 'Laura' ? '12345678' : '87654321',
            'telefono' => '3001234567',
            'correo' => strtolower($name).'@example.test',
        ];
    }
}
