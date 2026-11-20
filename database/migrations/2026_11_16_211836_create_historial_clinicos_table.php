<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historiales_clinicos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mascota_id')
                ->unique()
                ->constrained('mascotas')
                ->restrictOnDelete();

            $table->text('antecedentes')->nullable();
            $table->text('alergias')->nullable();
            $table->text('enfermedades_previas')->nullable();
            $table->text('vacunas')->nullable();

            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historiales_clinicos');
    }
};