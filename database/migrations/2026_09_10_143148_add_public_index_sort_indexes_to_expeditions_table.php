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
        Schema::table('expeditions', function (Blueprint $table) {
            $table->index(['completed', 'created_at', 'id']);
            $table->index(['completed', 'title', 'id']);
            $table->index(['project_id', 'completed', 'created_at', 'id']);
            $table->index(['project_id', 'completed', 'title', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expeditions', function (Blueprint $table) {
            $table->dropIndex(['completed', 'created_at', 'id']);
            $table->dropIndex(['completed', 'title', 'id']);
            $table->dropIndex(['project_id', 'completed', 'created_at', 'id']);
            $table->dropIndex(['project_id', 'completed', 'title', 'id']);
        });
    }
};
