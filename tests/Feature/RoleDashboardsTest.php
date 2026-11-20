<?php

namespace Tests\Feature;

use App\Models\Atencion;
use App\Models\HistorialClinico;
use App\Models\Mascota;
use App\Models\Propietario;
use App\Models\Urgencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleDashboardsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function dashboardRoles(): array
    {
        return [
            'administrator' => [User::ROLE_ADMINISTRADOR, '/admin/dashboard', 'dashboard.admin', 'PetAlert'],
            'reception' => [User::ROLE_RECEPCION, '/recepcion/dashboard', 'dashboard.reception', 'PetAlert'],
            'veterinarian' => [User::ROLE_VETERINARIO, '/veterinario/dashboard', 'dashboard.veterinarian', 'PetAlert'],
            'owner' => [User::ROLE_PROPIETARIO, '/propietario/dashboard', 'dashboard', 'Auth/Account'],
        ];
    }

    #[DataProvider('dashboardRoles')]
    public function test_each_role_can_open_only_its_own_dashboard(string $role, string $dashboardPath, string $screen, string $component): void
    {
        $user = User::factory()->create([
            'rol' => $role,
            'estado' => 'activo',
        ]);

        if ($role === User::ROLE_PROPIETARIO) {
            $user->propietario()->create($this->ownerData('Laura', '12345678'));
        }

        $this->actingAs($user)
            ->get($dashboardPath)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component($component)
                ->where('screen', $screen));

        foreach ([
            User::ROLE_ADMINISTRADOR => '/admin/dashboard',
            User::ROLE_RECEPCION => '/recepcion/dashboard',
            User::ROLE_VETERINARIO => '/veterinario/dashboard',
            User::ROLE_PROPIETARIO => '/propietario/dashboard',
        ] as $otherRole => $otherDashboard) {
            if ($otherRole !== $role) {
                $this->get($otherDashboard)->assertForbidden();
            }
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function roleRedirects(): array
    {
        return [
            'administrator' => [User::ROLE_ADMINISTRADOR, '/admin/dashboard'],
            'reception' => [User::ROLE_RECEPCION, '/recepcion/dashboard'],
            'veterinarian' => [User::ROLE_VETERINARIO, '/veterinario/dashboard'],
            'owner' => [User::ROLE_PROPIETARIO, '/propietario/dashboard'],
        ];
    }

    #[DataProvider('roleRedirects')]
    public function test_login_redirects_each_role_to_its_dashboard(string $role, string $dashboardPath): void
    {
        $user = User::factory()->create([
            'rol' => $role,
            'estado' => 'activo',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect($dashboardPath);
    }

    public function test_reception_can_assign_urgencies_without_receiving_clinical_data_or_permissions(): void
    {
        $receptionist = User::factory()->create([
            'rol' => User::ROLE_RECEPCION,
            'estado' => 'activo',
        ]);
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $pet = $this->createPet('Nala', '12345678');
        $urgency = $pet->urgencias()->create([
            'registrado_por' => $receptionist->id,
            'tipo_urgencia' => 'Consulta',
            'descripcion' => 'Reporte de recepción.',
            'nivel_prioridad' => 'media',
            'estado' => 'pendiente',
            'fecha_ingreso' => now(),
        ]);
        Atencion::create([
            'urgencia_id' => $urgency->id,
            'veterinario_id' => $veterinarian->id,
            'diagnostico' => 'Diagnóstico clínico confidencial',
            'observaciones' => 'Nota clínica confidencial',
            'fecha_atencion' => now(),
            'estado' => 'en_proceso',
        ]);
        HistorialClinico::create([
            'mascota_id' => $pet->id,
            'antecedentes' => 'Antecedente clínico confidencial',
        ]);

        $this->actingAs($receptionist)
            ->get(route('urgencies.show', $urgency))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('urgency.estado', 'pendiente')
                ->missing('urgency.atenciones'));

        $this->get(route('urgencies.show', $urgency))
            ->assertDontSee('Diagnóstico clínico confidencial')
            ->assertDontSee('Nota clínica confidencial');
        $this->get(route('pets.show', $pet))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->missing('pet.historial_clinico'));
        $this->get(route('owners.show', $pet->propietario))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('recentAttentions', 0));

        $this->patch(route('urgencies.assign', $urgency), [
            'veterinario_id' => $veterinarian->id,
        ])->assertRedirect(route('urgencies.show', $urgency));

        $this->assertDatabaseHas('urgencias', [
            'id' => $urgency->id,
            'veterinario_id' => $veterinarian->id,
            'estado' => 'pendiente',
        ]);

        foreach (['/histories', '/attentions', '/evolutions', '/users'] as $restrictedPath) {
            $this->get($restrictedPath)->assertForbidden();
        }

        $this->put(route('urgencies.update', $urgency), [
            'estado' => 'finalizada',
            'veterinario_id' => $veterinarian->id,
        ])->assertForbidden();
    }

    public function test_veterinarian_dashboard_and_clinical_queries_only_include_assigned_cases(): void
    {
        $veterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $otherVeterinarian = User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);
        $assignedPet = $this->createPet('Nala', '12345678');
        $otherPet = $this->createPet('Milo', '87654321');
        $assignedUrgency = $this->createUrgency($assignedPet, $veterinarian, 'pendiente');
        $otherUrgency = $this->createUrgency($otherPet, $otherVeterinarian, 'en_atencion');
        $assignedAttention = Atencion::create([
            'urgencia_id' => $assignedUrgency->id,
            'veterinario_id' => $veterinarian->id,
            'diagnostico' => 'Diagnóstico propio',
            'fecha_atencion' => now(),
            'estado' => 'en_proceso',
        ]);
        Atencion::create([
            'urgencia_id' => $otherUrgency->id,
            'veterinario_id' => $otherVeterinarian->id,
            'diagnostico' => 'Diagnóstico de otro profesional',
            'fecha_atencion' => now(),
            'estado' => 'en_proceso',
        ]);

        $this->actingAs($veterinarian)
            ->get('/veterinario/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.assignedUrgencies', 1)
                ->where('stats.pendingUrgencies', 1)
                ->where('stats.attentions', 1)
                ->has('latestUrgencies', 1));

        $this->get('/urgencies')->assertOk()->assertInertia(fn ($page) => $page
            ->has('urgencies.data', 1)
            ->where('urgencies.data.0.id', $assignedUrgency->id));
        $this->get(route('urgencies.show', $otherUrgency))->assertNotFound();
        $this->get(route('histories.show', $assignedPet))->assertOk();
        $this->get(route('histories.show', $otherPet))->assertNotFound();
        $this->get(route('attentions.show', $assignedAttention))->assertOk();
        $this->get('/users')->assertForbidden();
    }

    /**
     * @return array<string, string>
     */
    private function ownerData(string $name, string $document): array
    {
        return [
            'nombres' => $name,
            'apellidos' => 'Propietario',
            'tipo_documento' => 'CC',
            'numero_documento' => $document,
            'telefono' => '3001234567',
        ];
    }

    private function createPet(string $name, string $document): Mascota
    {
        $owner = Propietario::create($this->ownerData('Dueño de '.$name, $document));

        return $owner->mascotas()->create([
            'nombre' => $name,
            'especie' => 'Canino',
            'estado' => 'activo',
        ]);
    }

    private function createUrgency(Mascota $pet, User $veterinarian, string $status): Urgencia
    {
        return $pet->urgencias()->create([
            'registrado_por' => $veterinarian->id,
            'veterinario_id' => $veterinarian->id,
            'tipo_urgencia' => 'Consulta',
            'descripcion' => 'Atención de prueba.',
            'nivel_prioridad' => 'media',
            'estado' => $status,
            'fecha_ingreso' => now(),
        ]);
    }
}
