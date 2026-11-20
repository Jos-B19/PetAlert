<?php

namespace Tests\Feature;

use App\Models\AdministracionMedicamento;
use App\Models\Atencion;
use App\Models\AtencionMedicamento;
use App\Models\EvolucionClinica;
use App\Models\Mascota;
use App\Models\Medicamento;
use App\Models\Propietario;
use App\Models\User;
use App\Services\SeguimientoMascotaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerClinicalFollowUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_tracking_routes_render_the_expected_inertia_screens(): void
    {
        $owner = $this->createOwner('Laura');
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $pet = $this->createPet($owner, 'Nala', 'Canino');
        $this->createUrgency(
            $pet,
            $veterinarian,
            'en_atencion',
            '2026-11-19 08:00:00',
        );

        $this->actingAs($owner->user)
            ->get(route('account.tracking.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Account')
                ->where('screen', 'tracking.index')
                ->has('trackedPets', 1));

        $this->get(route('account.tracking.show', $pet))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Account')
                ->where('screen', 'tracking.show')
                ->where('trackingPet.mascota.id', $pet->id));

        $this->get(route('account.tracking.history', $pet))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Account')
                ->where('screen', 'tracking.history')
                ->has('trackingTimeline'));
    }

    public function test_unauthenticated_user_cannot_access_clinical_follow_up(): void
    {
        $this->get(route('account.tracking.index'))
            ->assertRedirect(route('login', ['redirect' => '/mi-cuenta/seguimiento']));
    }

    public function test_owner_lists_only_their_pets_that_remain_in_the_clinic(): void
    {
        $owner = $this->createOwner('Laura');
        $otherOwner = $this->createOwner('Carlos');
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);

        $activePet = $this->createPet($owner, 'Nala', 'Canino');
        $this->createUrgency(
            $activePet,
            $veterinarian,
            'en_atencion',
            '2026-11-19 08:30:00',
        );

        $otherPet = $this->createPet($otherOwner, 'Milo', 'Felino');
        $this->createUrgency(
            $otherPet,
            $veterinarian,
            'pendiente',
            '2026-11-19 09:30:00',
        );

        $finishedPet = $this->createPet($owner, 'Luna', 'Canino');
        $this->createUrgency(
            $finishedPet,
            $veterinarian,
            'finalizada',
            '2026-11-18 08:00:00',
            '2026-11-18 12:00:00',
        );

        $response = $this->actingAs($owner->user)
            ->getJson(route('account.tracking.index'))
            ->assertOk()
            ->assertJsonCount(1, 'mascotas')
            ->assertJsonPath('mascotas.0.id', $activePet->id)
            ->assertJsonPath('mascotas.0.nombre', 'Nala')
            ->assertJsonPath('mascotas.0.estado', 'en_atencion');

        $this->assertSame(
            ['id', 'nombre', 'especie', 'raza', 'estado', 'fecha_ingreso', 'ultima_actualizacion_clinica'],
            array_keys($response->json('mascotas.0')),
        );
    }

    public function test_owner_cannot_view_another_owners_pet_by_manipulating_the_url(): void
    {
        $owner = $this->createOwner('Laura');
        $otherOwner = $this->createOwner('Carlos');
        $otherPet = $this->createPet($otherOwner, 'Milo', 'Felino');

        $this->actingAs($owner->user)
            ->getJson(route('account.tracking.show', $otherPet))
            ->assertNotFound();

        $this->getJson(route('account.tracking.history', $otherPet))
            ->assertNotFound();
    }

    public function test_non_owner_roles_cannot_access_clinical_follow_up_routes(): void
    {
        foreach ([User::ROLE_ADMINISTRADOR, User::ROLE_RECEPCION, User::ROLE_VETERINARIO] as $role) {
            $user = $this->createUser($role);

            $this->actingAs($user)
                ->get(route('account.tracking.index'))
                ->assertForbidden();
        }
    }

    public function test_veterinarians_and_administrators_keep_access_to_internal_evolution_details(): void
    {
        $owner = $this->createOwner('Laura');
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $administrator = $this->createUser(User::ROLE_ADMINISTRADOR);
        $pet = $this->createPet($owner, 'Nala', 'Canino');
        $urgency = $this->createUrgency(
            $pet,
            $veterinarian,
            'en_atencion',
            '2026-11-19 08:00:00',
        );
        $attention = Atencion::create([
            'urgencia_id' => $urgency->id,
            'veterinario_id' => $veterinarian->id,
            'fecha_atencion' => '2026-11-19 08:30:00',
            'estado' => 'en_proceso',
        ]);
        $evolution = EvolucionClinica::create([
            'atencion_id' => $attention->id,
            'veterinario_id' => $veterinarian->id,
            'descripcion' => 'Detalle clínico profesional',
            'observaciones' => 'Nota interna profesional',
            'fecha_evolucion' => '2026-11-19 12:00:00',
        ]);

        foreach ([$veterinarian, $administrator] as $staffUser) {
            $this->actingAs($staffUser)
                ->get(route('evolutions.show', $evolution))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('PetAlert')
                    ->where('evolution.descripcion', 'Detalle clínico profesional')
                    ->where('evolution.observaciones', 'Nota interna profesional'));
        }
    }

    public function test_owner_follow_up_excludes_unclassified_clinical_data_from_json_and_inertia(): void
    {
        $owner = $this->createOwner('Laura');
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $pet = $this->createPet($owner, 'Nala', 'Canino');
        $urgency = $this->createUrgency(
            $pet,
            $veterinarian,
            'en_atencion',
            '2026-11-19 08:00:00',
        );
        $attention = Atencion::create([
            'urgencia_id' => $urgency->id,
            'veterinario_id' => $veterinarian->id,
            'diagnostico' => 'PRIVATE_DIAGNOSIS',
            'tratamiento' => 'PRIVATE_ATTENTION_TREATMENT',
            'observaciones' => 'PRIVATE_ATTENTION_NOTE',
            'fecha_atencion' => '2026-11-19 08:30:00',
            'estado' => 'en_proceso',
        ]);

        $evolution = EvolucionClinica::create([
            'atencion_id' => $attention->id,
            'veterinario_id' => $veterinarian->id,
            'descripcion' => 'PRIVATE_EVOLUTION_DESCRIPTION',
            'tratamiento_aplicado' => 'PRIVATE_EVOLUTION_TREATMENT',
            'estado_mascota' => 'PRIVATE_EVOLUTION_STATUS',
            'observaciones' => 'PRIVATE_EVOLUTION_NOTE',
            'fecha_evolucion' => '2026-11-19 12:00:00',
        ]);

        $medication = Medicamento::create([
            'nombre' => 'PRIVATE_MEDICATION_NAME',
            'presentacion' => 'PRIVATE_MEDICATION_PRESENTATION',
        ]);
        $indication = AtencionMedicamento::create([
            'atencion_id' => $attention->id,
            'medicamento_id' => $medication->id,
            'veterinario_id' => $veterinarian->id,
            'dosis' => 'PRIVATE_INDICATED_DOSE',
            'cantidad' => 1,
            'frecuencia' => 'PRIVATE_FREQUENCY',
            'via_administracion' => 'PRIVATE_ROUTE',
        ]);
        AdministracionMedicamento::create([
            'atencion_medicamento_id' => $indication->id,
            'veterinario_id' => $veterinarian->id,
            'dosis_administrada' => 'PRIVATE_ADMINISTERED_DOSE',
            'cantidad_administrada' => 1,
            'via_administracion' => 'PRIVATE_ADMINISTERED_ROUTE',
            'fecha_hora' => '2026-11-19 13:00:00',
            'observaciones' => 'PRIVATE_ADMINISTRATION_NOTE',
        ]);

        $response = $this->actingAs($owner->user)
            ->getJson(route('account.tracking.show', $pet))
            ->assertOk()
            ->assertJsonPath('mascota.id', $pet->id)
            ->assertJsonPath('ingreso.estado', 'en_atencion')
            ->assertJsonPath('ultima_actualizacion_clinica', '2026-11-19 13:00:00')
            ->assertJsonCount(0, 'evoluciones');

        $data = $response->json();
        $this->assertSame(
            ['mascota', 'ingreso', 'ultima_actualizacion_clinica', 'egreso', 'evoluciones', 'timeline'],
            array_keys($data),
        );
        $this->assertSame(
            ['id', 'nombre', 'especie', 'raza', 'sexo', 'estado'],
            array_keys($data['mascota']),
        );
        $this->assertSame(['fecha', 'estado'], array_keys($data['ingreso']));
        $this->assertSame(
            [['tipo' => 'ingreso', 'fecha' => '2026-11-19 08:00:00', 'estado' => 'en_atencion']],
            $data['timeline'],
        );

        $privateValues = [
            'PRIVATE_DIAGNOSIS',
            'PRIVATE_ATTENTION_TREATMENT',
            'PRIVATE_ATTENTION_NOTE',
            'PRIVATE_EVOLUTION_DESCRIPTION',
            'PRIVATE_EVOLUTION_TREATMENT',
            'PRIVATE_EVOLUTION_STATUS',
            'PRIVATE_EVOLUTION_NOTE',
            'PRIVATE_MEDICATION_NAME',
            'PRIVATE_MEDICATION_PRESENTATION',
            'PRIVATE_INDICATED_DOSE',
            'PRIVATE_FREQUENCY',
            'PRIVATE_ROUTE',
            'PRIVATE_ADMINISTERED_DOSE',
            'PRIVATE_ADMINISTERED_ROUTE',
            'PRIVATE_ADMINISTRATION_NOTE',
        ];

        foreach ($privateValues as $privateValue) {
            $this->assertStringNotContainsString($privateValue, $response->getContent());
        }

        $inertiaResponse = $this->get(route('account.tracking.show', $pet))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('trackingPet.evoluciones', [])
                ->where('trackingPet.timeline.0.tipo', 'ingreso'));

        foreach ($privateValues as $privateValue) {
            $this->assertStringNotContainsString($privateValue, $inertiaResponse->getContent());
        }

        $this->assertSame('PRIVATE_EVOLUTION_NOTE', $evolution->fresh()->observaciones);
    }

    public function test_owner_history_hides_clinical_evolutions_without_disclosure_classification(): void
    {
        $owner = $this->createOwner('Laura');
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $pet = $this->createPet($owner, 'Nala', 'Canino');
        $urgency = $this->createUrgency(
            $pet,
            $veterinarian,
            'en_observacion',
            '2026-11-19 07:00:00',
        );
        $attention = Atencion::create([
            'urgencia_id' => $urgency->id,
            'veterinario_id' => $veterinarian->id,
            'tratamiento' => 'Observación',
            'fecha_atencion' => '2026-11-19 08:00:00',
            'estado' => 'en_proceso',
        ]);

        EvolucionClinica::create([
            'atencion_id' => $attention->id,
            'veterinario_id' => $veterinarian->id,
            'descripcion' => 'Primera actualización',
            'estado_mascota' => 'Continúa en tratamiento',
            'fecha_evolucion' => '2026-11-19 12:00:00',
        ]);
        EvolucionClinica::create([
            'atencion_id' => $attention->id,
            'veterinario_id' => $veterinarian->id,
            'descripcion' => 'Segunda actualización',
            'estado_mascota' => 'Requiere seguimiento',
            'fecha_evolucion' => '2026-11-19 10:00:00',
        ]);

        $response = $this->actingAs($owner->user)
            ->getJson(route('account.tracking.history', $pet))
            ->assertOk()
            ->assertJsonCount(0, 'evoluciones')
            ->assertJsonCount(1, 'timeline')
            ->assertJsonPath('timeline.0.tipo', 'ingreso');

        $this->assertStringNotContainsString('Primera actualización', $response->getContent());
        $this->assertStringNotContainsString('Segunda actualización', $response->getContent());
    }

    public function test_discharge_date_is_returned_without_clinical_outcome(): void
    {
        $owner = $this->createOwner('Laura');
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $pet = $this->createPet($owner, 'Nala', 'Canino');
        $this->createUrgency(
            $pet,
            $veterinarian,
            'finalizada',
            '2026-11-18 08:00:00',
            '2026-11-18 16:30:00',
        );

        $response = $this->actingAs($owner->user)
            ->getJson(route('account.tracking.show', $pet))
            ->assertOk()
            ->assertJsonPath('egreso.fecha', '2026-11-18 16:30:00');

        $this->assertSame(['fecha'], array_keys($response->json('egreso')));
        $this->assertStringNotContainsString('Recuperado', $response->getContent());
    }

    public function test_pet_without_evolutions_returns_an_empty_collection(): void
    {
        $owner = $this->createOwner('Laura');
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $pet = $this->createPet($owner, 'Nala', 'Canino');
        $this->createUrgency(
            $pet,
            $veterinarian,
            'pendiente',
            '2026-11-19 08:00:00',
        );

        $this->actingAs($owner->user)
            ->getJson(route('account.tracking.show', $pet))
            ->assertOk()
            ->assertJsonCount(0, 'evoluciones')
            ->assertJsonPath('ultima_actualizacion_clinica', '2026-11-19 08:00:00');
    }

    public function test_owner_downloads_a_pdf_for_their_pet(): void
    {
        $owner = $this->createOwner('Laura');
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $pet = $this->createPet($owner, 'Nala', 'Canino');
        $this->createUrgency(
            $pet,
            $veterinarian,
            'en_atencion',
            '2026-11-19 08:00:00',
        );

        $response = $this->actingAs($owner->user)
            ->get(route('account.tracking.pdf', $pet));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload("seguimiento-nala-{$pet->id}.pdf");
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_pdf_download_returns_404_for_a_pet_owned_by_another_owner(): void
    {
        $owner = $this->createOwner('Laura');
        $otherOwner = $this->createOwner('Carlos');
        $otherPet = $this->createPet($otherOwner, 'Milo', 'Felino');

        $this->actingAs($owner->user)
            ->get(route('account.tracking.pdf', $otherPet))
            ->assertNotFound();
    }

    public function test_unauthenticated_user_is_redirected_from_pdf_download(): void
    {
        $owner = $this->createOwner('Laura');
        $pet = $this->createPet($owner, 'Nala', 'Canino');

        $this->get(route('account.tracking.pdf', $pet))
            ->assertRedirect(route('login', ['redirect' => "/mi-cuenta/seguimiento/{$pet->id}/pdf"]));
    }

    public function test_non_owner_role_is_forbidden_from_pdf_download(): void
    {
        $user = $this->createUser(User::ROLE_VETERINARIO);
        $owner = $this->createOwner('Laura');
        $pet = $this->createPet($owner, 'Nala', 'Canino');

        $this->actingAs($user)
            ->get(route('account.tracking.pdf', $pet))
            ->assertForbidden();
    }

    public function test_pdf_template_renders_only_owner_safe_data_when_clinical_records_exist(): void
    {
        $owner = $this->createOwner('Laura');
        $otherOwner = $this->createOwner('Carlos');
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $pet = $this->createPet($owner, 'Nala <script>alert(1)</script>', 'Canino');
        $urgency = $this->createUrgency(
            $pet,
            $veterinarian,
            'en_atencion',
            '2026-11-19 08:00:00',
        );
        $attention = Atencion::create([
            'urgencia_id' => $urgency->id,
            'veterinario_id' => $veterinarian->id,
            'diagnostico' => 'PRIVATE_DIAGNOSIS',
            'tratamiento' => 'PRIVATE_ATTENTION_TREATMENT',
            'observaciones' => 'PRIVATE_ATTENTION_NOTE',
            'fecha_atencion' => '2026-11-19 08:30:00',
            'estado' => 'en_proceso',
        ]);
        EvolucionClinica::create([
            'atencion_id' => $attention->id,
            'veterinario_id' => $veterinarian->id,
            'descripcion' => 'PRIVATE_EVOLUTION_DESCRIPTION',
            'tratamiento_aplicado' => 'PRIVATE_EVOLUTION_TREATMENT',
            'estado_mascota' => 'PRIVATE_EVOLUTION_STATUS',
            'observaciones' => 'PRIVATE_EVOLUTION_NOTE',
            'fecha_evolucion' => '2026-11-19 12:00:00',
        ]);

        $medication = Medicamento::create([
            'nombre' => 'PRIVATE_MEDICATION_NAME',
            'presentacion' => 'Suspensión',
        ]);
        $indication = AtencionMedicamento::create([
            'atencion_id' => $attention->id,
            'medicamento_id' => $medication->id,
            'veterinario_id' => $veterinarian->id,
            'dosis' => 'PRIVATE_INDICATED_DOSE',
            'cantidad' => 1,
            'frecuencia' => 'PRIVATE_FREQUENCY',
            'via_administracion' => 'PRIVATE_ROUTE',
        ]);
        AdministracionMedicamento::create([
            'atencion_medicamento_id' => $indication->id,
            'veterinario_id' => $veterinarian->id,
            'dosis_administrada' => 'PRIVATE_ADMINISTERED_DOSE',
            'cantidad_administrada' => 1,
            'via_administracion' => 'PRIVATE_ADMINISTERED_ROUTE',
            'fecha_hora' => '2026-11-19 13:00:00',
            'observaciones' => 'PRIVATE_ADMINISTRATION_NOTE',
        ]);

        $otherPet = $this->createPet($otherOwner, 'Milo', 'Felino');
        $otherUrgency = $this->createUrgency(
            $otherPet,
            $veterinarian,
            'en_atencion',
            '2026-11-19 09:00:00',
        );
        $otherAttention = Atencion::create([
            'urgencia_id' => $otherUrgency->id,
            'veterinario_id' => $veterinarian->id,
            'fecha_atencion' => '2026-11-19 09:30:00',
            'estado' => 'en_proceso',
        ]);
        $otherMedication = Medicamento::create([
            'nombre' => 'Medicamento de Milo',
            'presentacion' => 'Tableta',
        ]);
        AtencionMedicamento::create([
            'atencion_id' => $otherAttention->id,
            'medicamento_id' => $otherMedication->id,
            'veterinario_id' => $veterinarian->id,
            'dosis' => '1 tableta',
            'cantidad' => 1,
            'frecuencia' => 'Cada 24 horas',
            'via_administracion' => 'Oral',
        ]);

        $trackingPet = app(SeguimientoMascotaService::class)->detalle($owner, $pet);
        $trackingPet['ingreso']['motivo'] = 'PRIVATE_URGENCY_REASON';
        $trackingPet['ingreso']['veterinario'] = 'PRIVATE_VETERINARIAN_NAME';
        $trackingPet['egreso'] = [
            'fecha' => '2026-11-19 16:00:00',
            'estado_final' => 'PRIVATE_FINAL_STATE',
        ];
        $trackingPet['timeline'][0]['descripcion'] = 'PRIVATE_EVOLUTION_DESCRIPTION';
        $trackingPet['timeline'][0]['tratamiento_aplicado'] = 'PRIVATE_EVOLUTION_TREATMENT';
        $trackingPet['timeline'][0]['estado_mascota'] = 'PRIVATE_EVOLUTION_STATUS';
        $trackingPet['timeline'][0]['observaciones'] = 'PRIVATE_EVOLUTION_NOTE';
        $trackingPet['timeline'][0]['medicamento'] = 'PRIVATE_MEDICATION_NAME';
        $trackingPet['timeline'][0]['dosis'] = 'PRIVATE_INDICATED_DOSE';
        $html = view('pdf.seguimiento-mascota', [
            'trackingPet' => $trackingPet,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString('Ingreso a la clínica', $html);
        $this->assertStringContainsString('en_atencion', $html);
        $this->assertStringContainsString('No incluye evoluciones, observaciones, tratamientos ni medicamentos.', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);

        foreach ([
            'PRIVATE_DIAGNOSIS',
            'PRIVATE_ATTENTION_TREATMENT',
            'PRIVATE_ATTENTION_NOTE',
            'PRIVATE_URGENCY_REASON',
            'PRIVATE_VETERINARIAN_NAME',
            'PRIVATE_FINAL_STATE',
            'PRIVATE_EVOLUTION_DESCRIPTION',
            'PRIVATE_EVOLUTION_TREATMENT',
            'PRIVATE_EVOLUTION_STATUS',
            'PRIVATE_EVOLUTION_NOTE',
            'PRIVATE_MEDICATION_NAME',
            'PRIVATE_INDICATED_DOSE',
            'PRIVATE_FREQUENCY',
            'PRIVATE_ROUTE',
            'PRIVATE_ADMINISTERED_DOSE',
            'PRIVATE_ADMINISTERED_ROUTE',
            'PRIVATE_ADMINISTRATION_NOTE',
            'Medicamento de Milo',
        ] as $privateValue) {
            $this->assertStringNotContainsString($privateValue, $html);
        }
    }

    public function test_pdf_generation_succeeds_when_pet_has_no_evolutions(): void
    {
        $owner = $this->createOwner('Laura');
        $veterinarian = $this->createUser(User::ROLE_VETERINARIO);
        $pet = $this->createPet($owner, 'Nala', 'Canino');
        $this->createUrgency(
            $pet,
            $veterinarian,
            'pendiente',
            '2026-11-19 08:00:00',
        );

        $response = $this->actingAs($owner->user)
            ->get(route('account.tracking.pdf', $pet));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_pdf_template_shows_an_empty_state_when_timeline_has_no_updates(): void
    {
        $owner = $this->createOwner('Laura');
        $pet = $this->createPet($owner, 'Nala', 'Canino');
        $trackingPet = app(SeguimientoMascotaService::class)->detalle($owner, $pet);

        $html = view('pdf.seguimiento-mascota', [
            'trackingPet' => $trackingPet,
            'generatedAt' => now(),
        ])->render();

        $this->assertStringContainsString(
            'No hay fechas de ingreso o egreso disponibles para mostrar.',
            $html,
        );
    }

    private function createOwner(string $name): Propietario
    {
        $user = $this->createUser(User::ROLE_PROPIETARIO);

        return $user->propietario()->create([
            'nombres' => $name,
            'apellidos' => 'Propietario',
            'tipo_documento' => 'CC',
            'numero_documento' => fake()->unique()->numerify('########'),
            'telefono' => '3001234567',
            'estado' => 'activo',
        ]);
    }

    private function createUser(string $role): User
    {
        return User::factory()->create([
            'rol' => $role,
            'estado' => 'activo',
        ]);
    }

    private function createPet(Propietario $owner, string $name, string $species): Mascota
    {
        return $owner->mascotas()->create([
            'nombre' => $name,
            'especie' => $species,
            'raza' => 'Criollo',
            'sexo' => 'Macho',
            'estado' => 'activo',
        ]);
    }

    private function createUrgency(
        Mascota $pet,
        User $veterinarian,
        string $status,
        string $admissionDate,
        ?string $dischargeDate = null,
    ) {
        return $pet->urgencias()->create([
            'registrado_por' => $veterinarian->id,
            'veterinario_id' => $veterinarian->id,
            'tipo_urgencia' => 'Consulta',
            'descripcion' => 'Descripción interna de recepción',
            'nivel_prioridad' => 'media',
            'estado' => $status,
            'fecha_ingreso' => $admissionDate,
            'fecha_egreso' => $dischargeDate,
            'estado_final_mascota' => $dischargeDate ? 'Recuperado' : null,
        ]);
    }
}
