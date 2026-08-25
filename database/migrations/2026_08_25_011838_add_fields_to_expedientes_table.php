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
        Schema::table('expedientes', function (Blueprint $table) {
            $table->text('descripcion')->nullable()->after('cliente');
            $table->string('estado')->default('Abierto')->after('descripcion');
            $table->foreignId('modificado_por')->nullable()->constrained('usuarios')->after('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropForeign(['modificado_por']);
            $table->dropColumn(['descripcion', 'estado', 'modificado_por']);
        });
    }
};
