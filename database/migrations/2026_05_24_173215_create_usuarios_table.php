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
    Schema::create('usuarios', function (Blueprint $table) {
    $table->id();
    $table->string('nombre', 100);
    $table->string('apellido', 100);
    $table->string('correo', 150)->unique();
    $table->string('contrasena');      // hash bcrypt (JD044)
    $table->enum('rol', ['administrador','secretario','asesor','practicante']);
    $table->boolean('activo')->default(true);
    $table->integer('intentos_fallidos')->default(0);  // (JD045)
    $table->timestamp('bloqueado_hasta')->nullable();   // (JD045)
    $table->string('foto_perfil')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes(); // eliminado_en
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
