<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evoluciones_clinicas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('atencion_id')
                ->constrained('atenciones')
                ->restrictOnDelete();

            $table->foreignId('veterinario_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('descripcion');
            $table->text('tratamiento_aplicado')->nullable();

            $table->string('estado_mascota', 50)
                ->nullable();

            $table->dateTime('fecha_evolucion');

            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evoluciones_clinicas');
    }
};