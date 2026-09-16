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
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();

            $table->string('usuario_nombre', 150)->nullable();
            $table->string('usuario_rol', 50)->nullable();
            $table->string('modulo', 100)->index();
            $table->string('accion', 150)->index();
            $table->text('descripcion');
            $table->string('entidad_tipo', 100)->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->json('detalles')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('resultado', 30)->default('exitoso');
            $table->timestamp('fecha_hora')->index();
            $table->timestamps();

            $table->index(['modulo', 'fecha_hora']);
            $table->index(['usuario_id', 'fecha_hora']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
