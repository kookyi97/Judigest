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
        Schema::create('configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->string('categoria', 50)->index();
            $table->string('tipo', 30); // string, number, boolean, array
            $table->text('valor');
            $table->json('opciones')->nullable();
            $table->foreignId('modificado_por')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('historial_configuraciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('configuracion_id')
                ->constrained('configuraciones')
                ->cascadeOnDelete();
            $table->string('parametro_clave', 100)->index();
            $table->string('parametro_nombre', 150);
            $table->string('categoria', 50);
            $table->text('valor_anterior')->nullable();
            $table->text('valor_nuevo');
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('usuarios')
                ->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('fecha_hora')->index();
            $table->timestamps();

            $table->index(['parametro_clave', 'fecha_hora']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_configuraciones');
        Schema::dropIfExists('configuraciones');
    }
};
