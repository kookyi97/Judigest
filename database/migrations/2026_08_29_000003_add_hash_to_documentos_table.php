<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->char('hash_sha256', 64)
                ->nullable()
                ->after('tamano');

            $table->index('hash_sha256');
        });
    }

    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->dropIndex(['hash_sha256']);
            $table->dropColumn('hash_sha256');
        });
    }
};