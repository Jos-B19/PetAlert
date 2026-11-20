<?php

namespace Tests\Feature;

use App\Models\Atencion;
use App\Models\EvolucionClinica;
use App\Models\Mascota;
use App\Models\Propietario;
use App\Models\Urgencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_can_register_emergency_and_veterinarian_can_record_clinical_care(): void
    {
        $owner = Propietario::create([
            'nombres' => 'Laura',
            'apellidos' => 'Gómez',
            'tipo_documento' => 'CC',
            'numero_documento' => '44332211',
            'telefono' => '3005556677',
        ]);
        $pet = Mascota::create([
            'propietario_id' => $owner->id,
            'nombre' => 'Mora',
            'especie' => 'Canino',
            'estado' => 'activo',
        ]);
        $receptionist = User::factory()->create([
            'rol' => User::ROLE_RECEPCION,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);

        $this->actingAs($receptionist)->post('/urgencies', [
            'mascota_id' => $pet->id,
            'tipo_urgencia' => 'Consulta urgente',
            'descripcion' => 'Dificultad para caminar',
            'nivel_prioridad' => 'alta',
            'fecha_ingreso' => '2026-11-16 10:00:00',
        ])->assertRedirect();

        $urgency = Urgencia::firstOrFail();
        $this->assertSame($receptionist->id, $urgency->registrado_por);
        $this->assertSame('pendiente', $urgency->estado);

        $this->actingAs($receptionist)->patch(route('urgencies.assign', $urgency), [
            'veterinario_id' => $veterinarian->id,
        ])->assertRedirect(route('urgencies.show', $urgency));

        $this->actingAs($veterinarian)->put("/urgencies/{$urgency->id}", [
            'estado' => 'en_observacion',
        ])->assertRedirect();
        $this->assertSame('en_observacion', $urgency->fresh()->estado);

        $this->post('/attentions', [
            'urgencia_id' => $urgency->id,
            'diagnostico' => 'Evaluación veterinaria',
            'tratamiento' => 'Reposo y seguimiento',
            'fecha_atencion' => '2026-11-16 10:30:00',
        ])->assertRedirect();

        $attention = Atencion::firstOrFail();
        $this->assertSame($veterinarian->id, $attention->veterinario_id);
        $this->assertSame('en_atencion', $urgency->fresh()->estado);

        $this->post('/evolutions', [
            'atencion_id' => $attention->id,
            'descripcion' => 'Evolución estable',
            'peso' => '12.50',
            'fecha_evolucion' => '2026-11-16 12:00:00',
        ])->assertRedirect();

        $evolution = EvolucionClinica::firstOrFail();
        $this->assertSame($attention->id, $evolution->atencion_id);
        $this->assertSame('12.50', $evolution->peso);

        $this->actingAs($veterinarian)->put(route('attentions.update', $attention), [
            'diagnostico' => $attention->diagnostico,
            'tratamiento' => $attention->tratamiento,
            'procedimientos' => $attention->procedimientos,
            'observaciones' => $attention->observaciones,
            'estado' => 'finalizada',
        ])->assertRedirect(route('attentions.show', $attention));

        $this->assertSame('finalizada', $urgency->fresh()->estado);
        $this->assertNull($urgency->fresh()->fecha_egreso);

        $this->get("/histories/{$pet->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('screen', 'histories.show')
                ->where('pet.propietario.id', $owner->id)
                ->has('timeline', 3));
    }

    public function test_urgency_cannot_be_finalized_without_an_attention(): void
    {
        $owner = Propietario::create([
            'nombres' => 'Diego',
            'apellidos' => 'Rojas',
            'tipo_documento' => 'CC',
            'numero_documento' => '55667788',
            'telefono' => '3001112233',
        ]);
        $pet = Mascota::create([
            'propietario_id' => $owner->id,
            'nombre' => 'Toby',
            'especie' => 'Canino',
            'estado' => 'activo',
        ]);
        $user = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $urgency = Urgencia::create([
            'mascota_id' => $pet->id,
            'registrado_por' => $user->id,
            'tipo_urgencia' => 'Consulta',
            'descripcion' => 'Revisión requerida',
            'nivel_prioridad' => 'media',
            'estado' => 'pendiente',
            'fecha_ingreso' => now(),
        ]);

        $this->actingAs($user)->put("/urgencies/{$urgency->id}", [
            'estado' => 'finalizada',
        ])->assertSessionHasErrors('estado');

        $this->assertSame('pendiente', $urgency->fresh()->estado);
    }

    public function test_urgency_cannot_be_finalized_while_any_attention_is_open(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $urgency = $this->createUrgency('en_atencion', $administrator, $veterinarian);
        $finishedAttention = $this->createAttention($urgency, $veterinarian, 'finalizada');
        $openAttention = $this->createAttention($urgency, $veterinarian, 'en_proceso');

        $this->actingAs($veterinarian)->put(route('urgencies.update', $urgency), [
            'estado' => 'finalizada',
        ])->assertSessionHasErrors([
            'estado' => 'Finaliza todas las atenciones antes de cerrar la urgencia.',
        ]);

        $this->assertSame('en_atencion', $urgency->fresh()->estado);
        $this->assertSame('finalizada', $finishedAttention->fresh()->estado);
        $this->assertSame('en_proceso', $openAttention->fresh()->estado);
    }

    public function test_urgency_can_be_finalized_when_all_attentions_are_finalized(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $urgency = $this->createUrgency('en_observacion', $administrator, $veterinarian);
        $this->createAttention($urgency, $veterinarian, 'finalizada');
        $this->createAttention($urgency, $veterinarian, 'finalizada');

        $this->actingAs($veterinarian)->put(route('urgencies.update', $urgency), [
            'estado' => 'finalizada',
        ])->assertRedirect(route('urgencies.show', $urgency));

        $this->assertSame('finalizada', $urgency->fresh()->estado);
    }

    public function test_new_attention_cannot_be_registered_for_a_closed_urgency(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $urgency = $this->createUrgency('finalizada', $administrator, $veterinarian);

        $this->actingAs($veterinarian)->post(route('attentions.store'), [
            'urgencia_id' => $urgency->id,
            'diagnostico' => 'Atención posterior al cierre',
            'fecha_atencion' => '2026-11-16 11:00:00',
        ])->assertSessionHasErrors([
            'urgencia_id' => 'No puedes crear una atención para una urgencia cerrada o con egreso físico.',
        ]);

        $this->assertDatabaseCount('atenciones', 0);
    }

    public function test_finalizing_an_attention_keeps_urgency_open_until_every_attention_is_finalized(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $urgency = $this->createUrgency('en_atencion', $administrator, $veterinarian);
        $firstAttention = $this->createAttention($urgency, $veterinarian, 'en_proceso');
        $secondAttention = $this->createAttention($urgency, $veterinarian, 'en_proceso');

        $this->actingAs($veterinarian)->put(route('attentions.update', $firstAttention), [
            'diagnostico' => $firstAttention->diagnostico,
            'tratamiento' => $firstAttention->tratamiento,
            'procedimientos' => $firstAttention->procedimientos,
            'observaciones' => $firstAttention->observaciones,
            'estado' => 'finalizada',
        ])->assertRedirect(route('attentions.show', $firstAttention));

        $this->assertSame('en_atencion', $urgency->fresh()->estado);
        $this->assertSame('en_proceso', $secondAttention->fresh()->estado);

        $this->put(route('attentions.update', $secondAttention), [
            'diagnostico' => $secondAttention->diagnostico,
            'tratamiento' => $secondAttention->tratamiento,
            'procedimientos' => $secondAttention->procedimientos,
            'observaciones' => $secondAttention->observaciones,
            'estado' => 'finalizada',
        ])->assertRedirect(route('attentions.show', $secondAttention));

        $this->assertSame('finalizada', $firstAttention->fresh()->estado);
        $this->assertSame('finalizada', $secondAttention->fresh()->estado);
        $this->assertSame('finalizada', $urgency->fresh()->estado);
    }

    public function test_finalized_attention_cannot_be_modified(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $urgency = $this->createUrgency('finalizada', $administrator, $veterinarian);
        $attention = $this->createAttention($urgency, $veterinarian, 'finalizada');

        $this->actingAs($veterinarian)->put(route('attentions.update', $attention), [
            'diagnostico' => 'Diagnóstico modificado',
            'tratamiento' => $attention->tratamiento,
            'procedimientos' => $attention->procedimientos,
            'observaciones' => $attention->observaciones,
            'estado' => 'finalizada',
        ])->assertSessionHasErrors([
            'estado' => 'No puedes modificar una atención finalizada.',
        ]);

        $this->assertSame('Evaluación clínica', $attention->fresh()->diagnostico);
        $this->assertSame('finalizada', $attention->fresh()->estado);
    }

    public function test_finalized_urgency_cannot_be_reopened(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $urgency = $this->createUrgency('finalizada', $administrator, $veterinarian);

        $this->actingAs($veterinarian)->put(route('urgencies.update', $urgency), [
            'estado' => 'en_atencion',
        ])->assertSessionHasErrors([
            'estado' => 'No puedes cambiar el estado de una urgencia finalizada o cancelada.',
        ]);

        $this->assertSame('finalizada', $urgency->fresh()->estado);
    }

    public function test_veterinarian_registers_physical_discharge_separately_from_attention_completion(): void
    {
        $this->travelTo('2026-11-16 13:00:00');
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $urgency = $this->createUrgency('finalizada', $administrator, $veterinarian);
        $attention = Atencion::create([
            'urgencia_id' => $urgency->id,
            'veterinario_id' => $veterinarian->id,
            'diagnostico' => 'Valoración final',
            'tratamiento' => 'Seguimiento',
            'fecha_atencion' => '2026-11-16 11:00:00',
            'estado' => 'finalizada',
        ]);

        $this->actingAs($veterinarian)->post(route('urgencies.discharge', $urgency), [
            'fecha_egreso' => '2026-11-16 11:30:00',
            'estado_final_mascota' => 'Recuperación favorable',
        ])->assertRedirect(route('urgencies.show', $urgency));

        $this->assertDatabaseHas('urgencias', [
            'id' => $urgency->id,
            'fecha_egreso' => '2026-11-16 11:30:00',
            'egreso_registrado_por' => $veterinarian->id,
            'estado_final_mascota' => 'Recuperación favorable',
        ]);

        $this->actingAs($veterinarian)->put(route('urgencies.update', $urgency), [
            'estado' => 'cancelada',
        ])->assertSessionHasErrors([
            'estado' => 'No puedes cambiar el estado de una urgencia después de registrar el egreso físico.',
        ]);

        $this->assertSame('finalizada', $urgency->fresh()->estado);

        $this->actingAs($veterinarian)->put(route('attentions.update', $attention), [
            'diagnostico' => $attention->diagnostico,
            'tratamiento' => $attention->tratamiento,
            'procedimientos' => $attention->procedimientos,
            'observaciones' => $attention->observaciones,
            'estado' => 'en_proceso',
        ])->assertSessionHasErrors([
            'estado' => 'No puedes reabrir una atención después del egreso físico de la mascota.',
        ]);

        $this->assertSame('finalizada', $attention->fresh()->estado);

        $this->actingAs($veterinarian)->post(route('urgencies.discharge', $urgency), [
            'fecha_egreso' => '2026-11-16 11:30:00',
            'estado_final_mascota' => 'Recuperación favorable',
        ])->assertSessionHasErrors([
            'fecha_egreso' => 'El egreso físico ya fue registrado.',
        ]);
    }

    public function test_physical_discharge_is_rejected_for_an_active_urgency(): void
    {
        $this->travelTo('2026-11-16 13:00:00');
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $urgency = $this->createUrgency('en_atencion', $administrator, $veterinarian);

        $this->actingAs($veterinarian)->post(route('urgencies.discharge', $urgency), [
            'fecha_egreso' => now()->toDateTimeString(),
            'estado_final_mascota' => 'Recuperación favorable',
        ])->assertSessionHasErrors([
            'fecha_egreso' => 'Solo puedes registrar el egreso físico de una urgencia finalizada.',
        ]);

        $this->assertNull($urgency->fresh()->fecha_egreso);
    }

    public function test_receptionist_cannot_register_physical_discharge(): void
    {
        $this->travelTo('2026-11-16 13:00:00');
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $receptionist = User::factory()->create([
            'rol' => User::ROLE_RECEPCION,
            'estado' => 'activo',
        ]);
        $urgency = $this->createUrgency('finalizada', $administrator, $veterinarian);

        $this->actingAs($receptionist)->post(route('urgencies.discharge', $urgency), [
            'fecha_egreso' => now()->toDateTimeString(),
            'estado_final_mascota' => 'Recuperación favorable',
        ])->assertForbidden();

        $this->assertNull($urgency->fresh()->fecha_egreso);
    }

    public function test_veterinarian_cannot_discharge_an_urgency_assigned_to_another_veterinarian(): void
    {
        $this->travelTo('2026-11-16 13:00:00');
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
        $urgency = $this->createUrgency('finalizada', $administrator, $assignedVeterinarian);

        $this->actingAs($otherVeterinarian)->post(route('urgencies.discharge', $urgency), [
            'fecha_egreso' => '2026-11-16 11:30:00',
            'estado_final_mascota' => 'Recuperación favorable',
        ])->assertNotFound();

        $this->assertNull($urgency->fresh()->fecha_egreso);
    }

    public function test_discharge_date_before_admission_is_rejected(): void
    {
        $this->travelTo('2026-11-16 13:00:00');
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $urgency = $this->createUrgency('finalizada', $administrator, $veterinarian);

        $this->actingAs($veterinarian)->post(route('urgencies.discharge', $urgency), [
            'fecha_egreso' => '2026-11-16 09:59:00',
            'estado_final_mascota' => 'Recuperación favorable',
        ])->assertSessionHasErrors([
            'fecha_egreso' => 'La fecha y hora de egreso debe ser igual o posterior al ingreso.',
        ]);

        $this->assertNull($urgency->fresh()->fecha_egreso);
    }

    public function test_future_discharge_date_is_rejected(): void
    {
        $this->travelTo('2026-11-16 13:00:00');
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $urgency = $this->createUrgency('finalizada', $administrator, $veterinarian);

        $this->actingAs($veterinarian)->post(route('urgencies.discharge', $urgency), [
            'fecha_egreso' => '2026-11-16 13:01:00',
            'estado_final_mascota' => 'Recuperación favorable',
        ])->assertSessionHasErrors([
            'fecha_egreso' => 'La fecha y hora de egreso no puede ser futura.',
        ]);

        $this->assertNull($urgency->fresh()->fecha_egreso);
    }

    public function test_discharge_is_rejected_while_any_attention_is_open(): void
    {
        $this->travelTo('2026-11-16 13:00:00');
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $urgency = $this->createUrgency('finalizada', $administrator, $veterinarian);
        $this->createAttention($urgency, $veterinarian, 'en_proceso');

        $this->actingAs($veterinarian)->post(route('urgencies.discharge', $urgency), [
            'fecha_egreso' => '2026-11-16 11:30:00',
            'estado_final_mascota' => 'Recuperación favorable',
        ])->assertSessionHasErrors([
            'fecha_egreso' => 'Finaliza todas las atenciones antes de registrar el egreso físico.',
        ]);

        $this->assertNull($urgency->fresh()->fecha_egreso);
    }

    public function test_evolution_cannot_be_registered_after_urgency_finalization(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $urgency = $this->createUrgency('finalizada', $administrator, $veterinarian);
        $attention = $this->createAttention($urgency, $veterinarian, 'en_proceso');

        $this->actingAs($veterinarian)
            ->get(route('evolutions.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('attentions', 0));

        $this->post(route('evolutions.store'), [
            'atencion_id' => $attention->id,
            'descripcion' => 'Evolución posterior al cierre',
            'fecha_evolucion' => '2026-11-16 12:00:00',
        ])->assertSessionHasErrors([
            'atencion_id' => 'No puedes registrar evoluciones después del cierre de la urgencia o del egreso físico.',
        ]);

        $this->assertDatabaseCount('evoluciones_clinicas', 0);
    }

    public function test_evolution_cannot_be_registered_after_physical_discharge(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $urgency = $this->createUrgency('finalizada', $administrator, $veterinarian);
        $urgency->update(['fecha_egreso' => '2026-11-16 11:30:00']);
        $attention = $this->createAttention($urgency, $veterinarian, 'en_proceso');

        $this->actingAs($veterinarian)->post(route('evolutions.store'), [
            'atencion_id' => $attention->id,
            'descripcion' => 'Evolución posterior al egreso',
            'fecha_evolucion' => '2026-11-16 12:00:00',
        ])->assertSessionHasErrors([
            'atencion_id' => 'No puedes registrar evoluciones después del cierre de la urgencia o del egreso físico.',
        ]);

        $this->assertDatabaseCount('evoluciones_clinicas', 0);
    }

    private function createUrgency(string $status, User $recorder, User $veterinarian): Urgencia
    {
        $owner = Propietario::create([
            'nombres' => 'Ana',
            'apellidos' => 'Ríos',
            'tipo_documento' => 'CC',
            'numero_documento' => '99887766',
            'telefono' => '3005556677',
        ]);
        $pet = Mascota::create([
            'propietario_id' => $owner->id,
            'nombre' => 'Luna',
            'especie' => 'Canino',
            'estado' => 'activo',
        ]);

        return Urgencia::create([
            'mascota_id' => $pet->id,
            'registrado_por' => $recorder->id,
            'veterinario_id' => $veterinarian->id,
            'tipo_urgencia' => 'Consulta urgente',
            'descripcion' => 'Revisión clínica',
            'nivel_prioridad' => 'media',
            'estado' => $status,
            'fecha_ingreso' => '2026-11-16 10:00:00',
        ]);
    }

    private function createAttention(Urgencia $urgency, User $veterinarian, string $status): Atencion
    {
        return Atencion::create([
            'urgencia_id' => $urgency->id,
            'veterinario_id' => $veterinarian->id,
            'diagnostico' => 'Evaluación clínica',
            'fecha_atencion' => '2026-11-16 10:30:00',
            'estado' => $status,
        ]);
    }
}
