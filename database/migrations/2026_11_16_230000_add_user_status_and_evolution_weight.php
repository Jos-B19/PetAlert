<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('estado', 20)->default('activo')->after('rol');
        });

        Schema::table('evoluciones_clinicas', function (Blueprint $table) {
            $table->decimal('peso', 8, 2)->nullable()->after('estado_mascota');
        });
    }

    public function down(): void
    {
        Schema::table('evoluciones_clinicas', function (Blueprint $table) {
            $table->dropColumn('peso');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
