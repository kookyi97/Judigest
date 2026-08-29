<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->foreignId('practicante_id')
                ->nullable()
                ->after('asesor_id')
                ->constrained('usuarios')
                ->nullOnDelete();

            $table->index('practicante_id');
        });
    }

    public function down(): void
    {
        Schema::table('expedientes', function (Blueprint $table) {
            $table->dropForeign(['practicante_id']);
            $table->dropIndex(['practicante_id']);
            $table->dropColumn('practicante_id');
        });
    }
};