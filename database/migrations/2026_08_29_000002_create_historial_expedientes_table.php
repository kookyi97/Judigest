<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_expedientes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('expediente_id')
                ->constrained('expedientes')
                ->cascadeOnDelete();

            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();

            $table->string('accion', 100);
            $table->text('descripcion');
            $table->json('detalles')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('fecha_hora');

            $table->index(['expediente_id', 'fecha_hora']);
            $table->index(['usuario_id', 'fecha_hora']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_expedientes');
    }
};