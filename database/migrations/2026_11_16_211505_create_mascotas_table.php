<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mascotas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('propietario_id')
                ->constrained('propietarios')
                ->restrictOnDelete();

            $table->string('nombre', 100);
            $table->string('especie', 50);
            $table->string('raza', 100)->nullable();

            $table->string('sexo', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();

            $table->decimal('peso', 8, 2)->nullable();
            $table->string('color', 50)->nullable();

            $table->text('observaciones')->nullable();

            $table->string('estado', 20)->default('activo');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mascotas');
    }
};