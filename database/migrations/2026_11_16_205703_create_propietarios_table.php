<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propietarios', function (Blueprint $table) {
            $table->id();

            $table->string('nombres', 100);
            $table->string('apellidos', 100);

            $table->string('tipo_documento', 20);
            $table->string('numero_documento', 30)->unique();

            $table->string('telefono', 20);
            $table->string('correo', 150)->nullable();
            $table->string('direccion')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('propietarios');
    }
};