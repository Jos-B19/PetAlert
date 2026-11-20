<?php

namespace Tests\Feature;

use App\Models\AdministracionMedicamento;
use App\Models\Atencion;
use App\Models\AtencionMedicamento;
use App\Models\Mascota;
use App\Models\Medicamento;
use App\Models\Propietario;
use App\Models\Urgencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_catalog_and_veterinarian_can_record_an_indication_and_administration(): void
    {
        $this->travelTo('2026-11-16 14:00:00');
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $attention = $this->createAttention($administrator, $veterinarian);

        $this->actingAs($administrator)->post(route('medications.store'), [
            'nombre' => 'Analgésico de prueba',
            'presentacion' => 'Tableta',
        ])->assertRedirect(route('medications.index'));

        $medication = Medicamento::query()->firstOrFail();

        $this->actingAs($administrator)->get(route('medications.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('screen', 'medications.index')
                ->has('medications', 1)
                ->where('medications.0.administraciones_count', 0));

        $this->actingAs($veterinarian)->post(route('attentions.medications.store', $attention), [
            'medicamento_id' => $medication->id,
            'dosis' => '5 mg',
            'cantidad' => '1.50',
            'frecuencia' => 'Cada 12 horas',
            'via_administracion' => 'Oral',
        ])->assertRedirect(route('attentions.show', $attention));

        $indication = AtencionMedicamento::query()->firstOrFail();
        $this->assertSame($veterinarian->id, $indication->veterinario_id);

        $this->actingAs($veterinarian)->post(route('attention-medications.administrations.store', $indication), [
            'dosis_administrada' => '5 mg',
            'cantidad_administrada' => '1.50',
            'via_administracion' => 'Oral',
            'fecha_hora' => '2026-11-16 12:00:00',
            'observaciones' => 'Registro de prueba',
        ])->assertRedirect(route('attentions.show', $attention));

        $administration = AdministracionMedicamento::query()->firstOrFail();
        $this->assertSame($veterinarian->id, $administration->veterinario_id);
        $this->assertSame('2026-11-16 12:00:00', $administration->fecha_hora->format('Y-m-d H:i:s'));

        $this->get(route('attentions.show', $attention))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('screen', 'attentions.show')
                ->has('attention.medicamentos_indicados', 1)
                ->where('attention.medicamentos_indicados.0.administraciones.0.id', $administration->id));
    }

    public function test_veterinarian_cannot_manage_catalog(): void
    {
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);

        $this->actingAs($veterinarian)->get(route('medications.index'))->assertForbidden();

        $this->assertDatabaseCount('medicamentos', 0);
    }

    public function test_catalog_rejects_duplicate_name_and_presentation(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        Medicamento::query()->create([
            'nombre' => 'Analgésico',
            'presentacion' => 'Tableta',
            'activo' => true,
        ]);

        $this->actingAs($administrator)->post(route('medications.store'), [
            'nombre' => 'Analgésico',
            'presentacion' => 'Tableta',
        ])->assertSessionHasErrors([
            'nombre' => 'Ya existe un medicamento con estos datos.',
        ]);

        $this->assertDatabaseCount('medicamentos', 1);
    }

    public function test_administration_before_attention_is_rejected_without_persisting(): void
    {
        $this->travelTo('2026-11-16 14:00:00');
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $attention = $this->createAttention($administrator, $veterinarian);
        $medication = Medicamento::query()->create([
            'nombre' => 'Solución de prueba',
            'presentacion' => 'Solución',
            'activo' => true,
        ]);
        $indication = AtencionMedicamento::query()->create([
            'atencion_id' => $attention->id,
            'medicamento_id' => $medication->id,
            'veterinario_id' => $veterinarian->id,
            'dosis' => '2 ml',
            'cantidad' => 1,
            'frecuencia' => 'Cada 8 horas',
            'via_administracion' => 'Oral',
        ]);

        $this->actingAs($veterinarian)->post(route('attention-medications.administrations.store', $indication), [
            'dosis_administrada' => '2 ml',
            'cantidad_administrada' => '1',
            'via_administracion' => 'Oral',
            'fecha_hora' => '2026-11-16 10:30:00',
        ])->assertSessionHasErrors('fecha_hora');

        $this->assertDatabaseCount('administraciones_medicamentos', 0);
    }

    public function test_veterinarian_cannot_indicate_medicine_for_another_veterinarian_attention(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $assignedVeterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $otherVeterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $attention = $this->createAttention($administrator, $assignedVeterinarian);
        $medication = Medicamento::query()->create([
            'nombre' => 'Medicamento restringido',
            'presentacion' => 'Frasco',
            'activo' => true,
        ]);

        $this->actingAs($otherVeterinarian)->post(route('attentions.medications.store', $attention), [
            'medicamento_id' => $medication->id,
            'dosis' => '1 ml',
            'cantidad' => '1',
            'frecuencia' => 'Cada 12 horas',
            'via_administracion' => 'Oral',
        ])->assertNotFound();

        $this->assertDatabaseCount('atencion_medicamentos', 0);
    }

    private function createAttention(User $recorder, User $veterinarian): Atencion
    {
        $owner = Propietario::create([
            'nombres' => 'Camila',
            'apellidos' => 'Paz',
            'tipo_documento' => 'CC',
            'numero_documento' => '11223344',
            'telefono' => '3004445566',
        ]);
        $pet = Mascota::create([
            'propietario_id' => $owner->id,
            'nombre' => 'Nube',
            'especie' => 'Felino',
            'estado' => 'activo',
        ]);
        $urgency = Urgencia::create([
            'mascota_id' => $pet->id,
            'registrado_por' => $recorder->id,
            'veterinario_id' => $veterinarian->id,
            'tipo_urgencia' => 'Consulta clínica',
            'descripcion' => 'Evaluación veterinaria',
            'nivel_prioridad' => 'media',
            'estado' => 'en_atencion',
            'fecha_ingreso' => '2026-11-16 10:00:00',
        ]);

        return Atencion::create([
            'urgencia_id' => $urgency->id,
            'veterinario_id' => $veterinarian->id,
            'diagnostico' => 'Evaluación inicial',
            'tratamiento' => 'Registro de atención de prueba',
            'fecha_atencion' => '2026-11-16 11:00:00',
            'estado' => 'en_proceso',
        ]);
    }
}
