<?php

namespace Tests\Feature;

use App\Models\Atencion;
use App\Models\Mascota;
use App\Models\Propietario;
use App\Models\Urgencia;
use App\Models\User;
use Database\Seeders\ReportesDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ReportesDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_records_are_related_coherent_and_idempotent(): void
    {
        $this->app['env'] = 'local';

        foreach (['Base 1', 'Base 2'] as $index => $name) {
            Propietario::query()->create([
                'nombres' => $name,
                'apellidos' => 'Propietario existente',
                'tipo_documento' => 'OTRO',
                'numero_documento' => 'EXISTING-OWNER-'.($index + 1),
                'telefono' => '300555000'.($index + 1),
            ]);
        }

        User::factory()->create([
            'rol' => User::ROLE_RECEPCION,
            'estado' => 'activo',
        ]);
        User::factory()->create([
            'rol' => User::ROLE_VETERINARIO,
            'estado' => 'activo',
        ]);

        $this->seed(ReportesDemoSeeder::class);
        $this->seed(ReportesDemoSeeder::class);

        $this->assertDatabaseCount('propietarios', 15);
        $this->assertDatabaseCount('mascotas', 30);
        $this->assertDatabaseCount('historiales_clinicos', 30);
        $this->assertDatabaseCount('urgencias', 45);
        $this->assertDatabaseCount('atenciones', 40);
        $this->assertDatabaseCount('evoluciones_clinicas', 60);
        $this->assertDatabaseCount('users', 2);

        $this->assertSame(30, Urgencia::query()->where('estado', 'finalizada')->count());
        $this->assertSame(5, Urgencia::query()->where('estado', 'en_atencion')->count());
        $this->assertSame(5, Urgencia::query()->where('estado', 'en_observacion')->count());
        $this->assertSame(3, Urgencia::query()->where('estado', 'pendiente')->count());
        $this->assertSame(2, Urgencia::query()->where('estado', 'cancelada')->count());
        $this->assertSame(30, Urgencia::query()->whereNotNull('fecha_egreso')->count());
        $this->assertSame(
            10,
            Urgencia::query()
                ->whereIn('estado', ['en_atencion', 'en_observacion'])
                ->whereNull('fecha_egreso')
                ->count(),
        );
        $this->assertSame(30, Atencion::query()->where('estado', 'finalizada')->count());
        $this->assertSame(10, Atencion::query()->where('estado', 'en_proceso')->count());

        $urgencies = Urgencia::query()->with('atenciones.evolucionesClinicas')->get();
        $months = [];
        $weeks = [];
        $hours = [];

        foreach ($urgencies as $urgency) {
            $ingress = $urgency->fecha_ingreso;
            $months[] = (int) $ingress->format('n');
            $weeks[] = $ingress->format('o-W');
            $hours[] = $ingress->format('G');
            $this->assertGreaterThanOrEqual('2026-09-01 00:00:00', $ingress->format('Y-m-d H:i:s'));
            $this->assertLessThanOrEqual('2026-11-30 23:59:59', $ingress->format('Y-m-d H:i:s'));

            foreach ($urgency->atenciones as $attention) {
                $this->assertTrue($attention->fecha_atencion->greaterThanOrEqualTo($ingress));

                foreach ($attention->evolucionesClinicas as $evolution) {
                    $this->assertTrue($evolution->fecha_evolucion->greaterThanOrEqualTo($attention->fecha_atencion));
                }
            }

            if ($urgency->fecha_egreso) {
                $this->assertTrue($urgency->fecha_egreso->greaterThan($ingress));

                $latestEvolution = $urgency->atenciones
                    ->flatMap(fn (Atencion $attention) => $attention->evolucionesClinicas)
                    ->sortByDesc('fecha_evolucion')
                    ->first();

                $this->assertNotNull($latestEvolution);
                $this->assertTrue($urgency->fecha_egreso->greaterThanOrEqualTo($latestEvolution->fecha_evolucion));
            }
        }

        $this->assertSame([9, 10, 11], collect($months)->unique()->sort()->values()->all());
        $this->assertGreaterThanOrEqual(10, collect($weeks)->unique()->count());
        $this->assertGreaterThanOrEqual(5, collect($hours)->unique()->count());
    }

    public function test_demo_seeder_refuses_to_run_outside_local_or_development(): void
    {
        $this->app['env'] = 'production';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('solo puede ejecutarse en entornos local o development');

        app(ReportesDemoSeeder::class)->run();
    }
}
