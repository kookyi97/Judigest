<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audiencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')
                  ->constrained('expedientes')
                  ->onDelete('cascade');
            $table->foreignId('registrado_por')
                  ->constrained('usuarios');
            $table->date('fecha');
            $table->time('hora');
            $table->string('tipo_audiencia');       // Inicial, Pruebas, Sentencia, etc.
            $table->string('sala_juzgado');          // Sala 1, Juzgado 2, etc.
            $table->text('observaciones')->nullable();
            $table->string('estado')->default('programada'); // programada, cancelada
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audiencias');
    }
};
