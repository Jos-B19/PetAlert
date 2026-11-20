<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atenciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('urgencia_id')
                ->constrained('urgencias')
                ->restrictOnDelete();

            $table->foreignId('veterinario_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->text('diagnostico')->nullable();
            $table->text('tratamiento')->nullable();
            $table->text('procedimientos')->nullable();

            $table->text('observaciones')->nullable();

            $table->dateTime('fecha_atencion');

            $table->string('estado', 30)
                ->default('en_proceso');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atenciones');
    }
};