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
        Schema::table('projects', function (Blueprint $table) {
            Schema::table('projects', function (Blueprint $table) {
                $table->index(['created_at', 'id']);
                $table->index(['title', 'id']);
                $table->index(['group_id', 'created_at', 'id']);
                $table->index(['group_id', 'title', 'id']);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropIndex(['created_at', 'id']);
                $table->dropIndex(['title', 'id']);
                $table->dropIndex(['group_id', 'created_at', 'id']);
                $table->dropIndex(['group_id', 'title', 'id']);
            });
        });
    }
};
