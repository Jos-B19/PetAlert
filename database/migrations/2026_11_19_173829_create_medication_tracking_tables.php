<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medicamentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('presentacion', 100);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['nombre', 'presentacion']);
        });

        Schema::create('atencion_medicamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atencion_id')->constrained('atenciones')->restrictOnDelete();
            $table->foreignId('medicamento_id')->constrained('medicamentos')->restrictOnDelete();
            $table->foreignId('veterinario_id')->constrained('users')->restrictOnDelete();
            $table->string('dosis', 100);
            $table->decimal('cantidad', 8, 2);
            $table->string('frecuencia', 100);
            $table->string('via_administracion', 50);
            $table->timestamps();

            $table->unique(['atencion_id', 'medicamento_id']);
        });

        Schema::create('administraciones_medicamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atencion_medicamento_id')
                ->constrained('atencion_medicamentos')
                ->restrictOnDelete();
            $table->foreignId('veterinario_id')->constrained('users')->restrictOnDelete();
            $table->string('dosis_administrada', 100);
            $table->decimal('cantidad_administrada', 8, 2);
            $table->string('via_administracion', 50);
            $table->dateTime('fecha_hora')->index();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(
                ['atencion_medicamento_id', 'fecha_hora'],
                'admin_meds_usage_time_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('administraciones_medicamentos');
        Schema::dropIfExists('atencion_medicamentos');
        Schema::dropIfExists('medicamentos');
    }
};
