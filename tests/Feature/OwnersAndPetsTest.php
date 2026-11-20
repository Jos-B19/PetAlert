<?php

namespace Tests\Feature;

use App\Models\HistorialClinico;
use App\Models\Mascota;
use App\Models\Propietario;
use App\Models\Urgencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Inertia;
use Tests\TestCase;

class OwnersAndPetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_be_created_listed_updated_and_not_deleted_with_pets(): void
    {
        $this->actingAs(User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]));

        $ownerData = [
            'nombres' => 'María',
            'apellidos' => 'Pérez',
            'tipo_documento' => 'CC',
            'numero_documento' => '12345678',
            'telefono' => '3001234567',
            'correo' => 'maria@example.test',
            'direccion' => 'Ipiales',
        ];

        $this->post('/owners', $ownerData)->assertRedirect('/owners');

        $owner = Propietario::where('numero_documento', '12345678')->firstOrFail();
        $this->get('/owners')->assertOk()->assertInertia(fn ($page) => $page
            ->component('PetAlert')
            ->where('screen', 'owners.index')
            ->has('owners.data', 1));

        $this->put("/owners/{$owner->id}", [...$ownerData, 'nombres' => 'María Elena'])
            ->assertRedirect("/owners/{$owner->id}");

        $pet = Mascota::create([
            'propietario_id' => $owner->id,
            'nombre' => 'Luna',
            'especie' => 'Canino',
            'estado' => 'activo',
        ]);

        $this->delete("/owners/{$owner->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('propietarios', ['id' => $owner->id]);
        $this->assertSame($owner->id, $pet->fresh()->propietario->id);
    }

    public function test_duplicate_owner_document_is_rejected(): void
    {
        $this->actingAs(User::factory()->create([
            'rol' => User::ROLE_RECEPCION,
            'estado' => 'activo',
        ]));

        Propietario::create([
            'nombres' => 'Ana',
            'apellidos' => 'López',
            'tipo_documento' => 'CC',
            'numero_documento' => '87654321',
            'telefono' => '3000000000',
        ]);

        $this->post('/owners', [
            'nombres' => 'Otra',
            'apellidos' => 'Persona',
            'tipo_documento' => 'CC',
            'numero_documento' => '87654321',
            'telefono' => '3111111111',
        ])->assertSessionHasErrors('numero_documento');
    }

    public function test_pet_requires_a_real_owner_and_is_searchable_with_owner_details(): void
    {
        $this->actingAs(User::factory()->create([
            'rol' => User::ROLE_RECEPCION,
            'estado' => 'activo',
        ]));

        $this->post('/pets', [
            'propietario_id' => 999,
            'nombre' => 'Luna',
            'especie' => 'Canino',
            'estado' => 'activo',
        ])->assertSessionHasErrors('propietario_id');

        $owner = Propietario::create([
            'nombres' => 'Carlos',
            'apellidos' => 'Ruiz',
            'tipo_documento' => 'CC',
            'numero_documento' => '10203040',
            'telefono' => '3001234567',
        ]);

        $this->post('/pets', [
            'propietario_id' => $owner->id,
            'nombre' => 'Luna',
            'especie' => 'Canino',
            'raza' => 'Mestiza',
            'estado' => 'activo',
        ])->assertRedirect();

        $this->get('/pets?q=Carlos')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('screen', 'pets.index')
                ->has('pets.data', 1)
                ->where('pets.data.0.propietario.nombres', 'Carlos'));
    }

    public function test_veterinarian_cannot_create_owner_records(): void
    {
        $this->actingAs(User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]));

        $this->post('/owners', [
            'nombres' => 'Ana',
            'apellidos' => 'López',
            'tipo_documento' => 'CC',
            'numero_documento' => '33445566',
            'telefono' => '3000000000',
        ])->assertForbidden();
    }

    public function test_administrator_can_list_all_pets_with_management_fields(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $firstPet = $this->createPet('Luna', '12345678', [
            'observaciones' => 'Observación administrativa',
            'peso' => 12.5,
        ]);
        $this->createPet('Max', '87654321');

        $this->actingAs($administrator)
            ->get(route('pets.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('pets.data', 2)
                ->where('pets.data.0.id', $firstPet->id)
                ->where('pets.data.0.observaciones', 'Observación administrativa')
                ->where('pets.data.0.peso', '12.50'));

        $this->get(route('owners.show', $firstPet->propietario_id))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('owner.mascotas.0.id', $firstPet->id)
                ->where('owner.mascotas.0.observaciones', 'Observación administrativa'));
    }

    public function test_veterinarian_lists_only_pets_and_filter_options_from_assigned_urgencies(): void
    {
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $otherVeterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $assignedPet = $this->createPet('Nala', '12345678', [
            'observaciones' => 'Dato clínico autorizado',
        ]);
        $otherPet = $this->createPet('Milo', '87654321', ['especie' => 'Felino']);
        $unassignedPet = $this->createPet('Toby', '11223344', ['especie' => 'Ave']);
        $this->createUrgency($assignedPet, $veterinarian, $veterinarian);
        $this->createUrgency($otherPet, $otherVeterinarian, $otherVeterinarian);

        $this->actingAs($veterinarian)
            ->get(route('pets.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('pets.data', 1)
                ->where('pets.data.0.id', $assignedPet->id)
                ->where('pets.data.0.observaciones', 'Dato clínico autorizado')
                ->has('filterOptions.owners', 1)
                ->where('filterOptions.owners.0.id', $assignedPet->propietario_id)
                ->where('filterOptions.species', ['Canino']));

        $this->assertModelExists($unassignedPet);
    }

    public function test_veterinarian_can_open_an_assigned_pet_but_not_another_assignment_by_url(): void
    {
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $otherVeterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $assignedPet = $this->createPet('Nala', '12345678', [
            'observaciones' => 'Dato clínico autorizado',
        ]);
        $otherPet = $this->createPet('Milo', '87654321');
        $assignedUrgency = $this->createUrgency($assignedPet, $veterinarian, $veterinarian);
        $this->createUrgency($assignedPet, $otherVeterinarian, $otherVeterinarian);
        $this->createUrgency($otherPet, $otherVeterinarian, $otherVeterinarian);

        $this->actingAs($veterinarian)
            ->get(route('pets.show', $assignedPet))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('pet.observaciones', 'Dato clínico autorizado')
                ->has('pet.urgencias', 1)
                ->where('pet.urgencias.0.id', $assignedUrgency->id));

        $this->get(route('pets.show', $otherPet))->assertNotFound();
    }

    public function test_reception_can_view_operational_pet_data_without_private_or_clinical_fields(): void
    {
        $receptionist = User::factory()->create([
            'rol' => User::ROLE_RECEPCION,
            'estado' => 'activo',
        ]);
        $pet = $this->createPet('Luna', '12345678', [
            'sexo' => 'hembra',
            'fecha_nacimiento' => '2024-01-15',
            'peso' => 12.5,
            'observaciones' => 'Observación privada de la mascota',
        ]);
        $urgency = $this->createUrgency($pet, $receptionist, null, 'Descripción clínica privada');
        HistorialClinico::create([
            'mascota_id' => $pet->id,
            'antecedentes' => 'Antecedente clínico privado',
        ]);

        $this->actingAs($receptionist)
            ->get(route('pets.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('pets.data.0.nombre', 'Luna')
                ->missing('pets.data.0.peso')
                ->missing('pets.data.0.observaciones'));

        $this->get(route('pets.show', $pet))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('pet.nombre', 'Luna')
                ->missing('pet.peso')
                ->missing('pet.observaciones')
                ->missing('pet.historial_clinico')
                ->missing('pet.urgencias.0.descripcion'));

        $response = $this->get(route('pets.show', $pet), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
        ]);

        $response->assertOk()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'PetAlert')
            ->assertJsonPath('props.pet.nombre', 'Luna')
            ->assertJsonPath('props.pet.urgencias.0.id', $urgency->id)
            ->assertJsonMissingPaths([
                'props.pet.sexo',
                'props.pet.fecha_nacimiento',
                'props.pet.peso',
                'props.pet.observaciones',
                'props.pet.historial_clinico',
                'props.pet.urgencias.0.descripcion',
            ]);
        $response->assertDontSee('Observación privada de la mascota');
        $response->assertDontSee('Descripción clínica privada');
        $response->assertDontSee('Antecedente clínico privado');
    }

    public function test_owner_cannot_access_staff_pet_routes_by_url(): void
    {
        $ownerUser = User::factory()->create([
            'rol' => User::ROLE_PROPIETARIO,
            'estado' => 'activo',
        ]);
        $pet = $this->createPet('Nala', '12345678');

        $this->actingAs($ownerUser)
            ->get(route('pets.index'))
            ->assertForbidden();

        $this->get(route('pets.show', $pet))->assertForbidden();
    }

    public function test_veterinarian_owner_detail_includes_only_assigned_pets_and_counts(): void
    {
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $otherVeterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $owner = Propietario::create([
            'nombres' => 'Laura',
            'apellidos' => 'Pérez',
            'tipo_documento' => 'CC',
            'numero_documento' => '44556677',
            'telefono' => '3001234567',
        ]);
        $assignedPet = $owner->mascotas()->create([
            'nombre' => 'Nala',
            'especie' => 'Canino',
            'estado' => 'activo',
        ]);
        $otherPet = $owner->mascotas()->create([
            'nombre' => 'Milo',
            'especie' => 'Felino',
            'estado' => 'activo',
        ]);
        $assignedUrgency = $this->createUrgency($assignedPet, $veterinarian, $veterinarian);
        $this->createUrgency($otherPet, $otherVeterinarian, $otherVeterinarian);

        $response = $this->actingAs($veterinarian)->get(route('owners.show', $owner));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('owner.mascotas', 1)
                ->where('owner.mascotas.0.id', $assignedPet->id)
                ->where('owner.mascotas_count', 1)
                ->where('owner.urgencias_count', 1)
                ->has('recentUrgencies', 1)
                ->where('recentUrgencies.0.id', $assignedUrgency->id));
        $response->assertDontSee('Milo');
    }

    public function test_reception_owner_detail_excludes_private_pet_fields_from_inertia_and_json(): void
    {
        $receptionist = User::factory()->create([
            'rol' => User::ROLE_RECEPCION,
            'estado' => 'activo',
        ]);
        $pet = $this->createPet('Luna', '33445566', [
            'sexo' => 'hembra',
            'fecha_nacimiento' => '2024-01-15',
            'peso' => 12.5,
            'observaciones' => 'Observación privada de mascota',
        ]);
        $owner = $pet->propietario;

        $this->actingAs($receptionist)
            ->get(route('owners.show', $owner))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('owner.mascotas.0.id', $pet->id)
                ->where('owner.mascotas.0.nombre', 'Luna')
                ->missing('owner.mascotas.0.sexo')
                ->missing('owner.mascotas.0.fecha_nacimiento')
                ->missing('owner.mascotas.0.peso')
                ->missing('owner.mascotas.0.observaciones'));

        $response = $this->get(route('owners.show', $owner), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => Inertia::getVersion(),
        ]);

        $response->assertOk()
            ->assertJsonPath('component', 'PetAlert')
            ->assertJsonPath('props.owner.mascotas.0.id', $pet->id)
            ->assertJsonMissingPaths([
                'props.owner.mascotas.0.sexo',
                'props.owner.mascotas.0.fecha_nacimiento',
                'props.owner.mascotas.0.peso',
                'props.owner.mascotas.0.observaciones',
            ])
            ->assertDontSee('Observación privada de mascota');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createPet(string $name, string $document, array $attributes = []): Mascota
    {
        $owner = Propietario::create([
            'nombres' => 'Dueño de '.$name,
            'apellidos' => 'Prueba',
            'tipo_documento' => 'CC',
            'numero_documento' => $document,
            'telefono' => '3001234567',
        ]);

        return $owner->mascotas()->create([
            'nombre' => $name,
            'especie' => 'Canino',
            'estado' => 'activo',
            ...$attributes,
        ]);
    }

    private function createUrgency(
        Mascota $pet,
        User $registeredBy,
        ?User $veterinarian,
        string $description = 'Descripción de prueba.',
    ): Urgencia {
        return $pet->urgencias()->create([
            'registrado_por' => $registeredBy->id,
            'veterinario_id' => $veterinarian?->id,
            'tipo_urgencia' => 'Consulta',
            'descripcion' => $description,
            'nivel_prioridad' => 'media',
            'estado' => 'pendiente',
            'fecha_ingreso' => now(),
        ]);
    }
}
