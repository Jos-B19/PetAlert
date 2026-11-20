<?php

namespace Tests\Feature;

use App\Models\Mascota;
use App\Models\Propietario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_indicators_reflect_database_records_and_worker_statuses(): void
    {
        $administrator = User::factory()->create([
            'rol' => User::ROLE_ADMINISTRADOR,
            'estado' => 'activo',
        ]);
        User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'inactivo',
        ]);
        $owner = Propietario::create([
            'nombres' => 'Laura',
            'apellidos' => 'Gómez',
            'tipo_documento' => 'CC',
            'numero_documento' => '12345678',
            'telefono' => '3001234567',
        ]);
        $activePet = Mascota::create([
            'propietario_id' => $owner->id,
            'nombre' => 'Nala',
            'especie' => 'Canino',
            'estado' => 'activo',
        ]);
        $inactivePet = Mascota::create([
            'propietario_id' => $owner->id,
            'nombre' => 'Milo',
            'especie' => 'Felino',
            'estado' => 'inactivo',
        ]);
        $activePet->urgencias()->create([
            'registrado_por' => $administrator->id,
            'tipo_urgencia' => 'Consulta',
            'descripcion' => 'Reporte pendiente',
            'nivel_prioridad' => 'media',
            'estado' => 'pendiente',
            'fecha_ingreso' => now(),
        ]);
        $inactivePet->urgencias()->create([
            'registrado_por' => $administrator->id,
            'tipo_urgencia' => 'Seguimiento',
            'descripcion' => 'En observación',
            'nivel_prioridad' => 'baja',
            'estado' => 'en_observacion',
            'fecha_ingreso' => now(),
        ]);

        $this->actingAs($administrator)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.owners', 1)
                ->where('stats.pets', 2)
                ->where('stats.activePets', 1)
                ->where('stats.inactivePets', 1)
                ->where('stats.urgencies', 2)
                ->where('stats.pendingUrgencies', 1)
                ->where('stats.activeUrgencies', 0)
                ->where('stats.observationUrgencies', 1)
                ->where('stats.completedUrgencies', 0)
                ->where('stats.staff', 2)
                ->where('stats.activeStaff', 1));
    }
}
