<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('urgencias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mascota_id')
                ->constrained('mascotas')
                ->restrictOnDelete();

            $table->foreignId('registrado_por')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('veterinario_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('tipo_urgencia', 100);
            $table->text('descripcion');

            $table->string('nivel_prioridad', 20)
                ->default('media');

            $table->string('estado', 30)
                ->default('pendiente');

            $table->dateTime('fecha_ingreso');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('urgencias');
    }
};