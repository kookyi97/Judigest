<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            // A quién va dirigida la notificación
            $table->foreignId('usuario_id')
                  ->constrained('usuarios')
                  ->onDelete('cascade');
            // Qué tipo de evento la generó
            // 'audiencia_registrada' | 'audiencia_modificada' | 'caso_asignado'
            $table->string('tipo');
            $table->string('titulo');
            $table->text('mensaje');
            // Referencias opcionales al objeto que la generó
            $table->foreignId('expediente_id')
                  ->nullable()
                  ->constrained('expedientes')
                  ->nullOnDelete();
            $table->foreignId('audiencia_id')
                  ->nullable()
                  ->constrained('audiencias')
                  ->nullOnDelete();
            $table->boolean('leida')->default(false);
            // Estado de envío (JD025: registrar estado de envío)
            $table->string('estado_envio')->default('enviada'); // enviada | fallida
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
