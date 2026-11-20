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
        Schema::table('urgencias', function (Blueprint $table) {
            $table->foreignId('egreso_registrado_por')
                ->nullable()
                ->after('fecha_egreso')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('estado_final_mascota', 50)->nullable()->after('egreso_registrado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('urgencias', function (Blueprint $table) {
            $table->dropColumn('estado_final_mascota');
            $table->dropConstrainedForeignId('egreso_registrado_por');
        });
    }
};
