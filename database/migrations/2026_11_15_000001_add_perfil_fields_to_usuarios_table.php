<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('telefono', 20)->nullable()->after('correo');       // JD049: dato de contacto editable
            $table->timestamp('ultimo_acceso')->nullable()->after('bloqueado_hasta'); // JD049: solo lo escribe el servidor
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn(['telefono', 'ultimo_acceso']);
        });
    }
};