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
        Schema::table('events', function (Blueprint $table) {
            $table->index(['end_date', 'start_date', 'id']);
            $table->index(['end_date', 'title', 'id']);
            $table->index(['project_id', 'end_date', 'start_date', 'id']);
            $table->index(['project_id', 'end_date', 'title', 'id']);
            $table->index(['owner_id', 'end_date', 'start_date', 'id']);
            $table->index(['owner_id', 'end_date', 'title', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['end_date', 'start_date', 'id']);
            $table->dropIndex(['end_date', 'title', 'id']);
            $table->dropIndex(['project_id', 'end_date', 'start_date', 'id']);
            $table->dropIndex(['project_id', 'end_date', 'title', 'id']);
            $table->dropIndex(['owner_id', 'end_date', 'start_date', 'id']);
            $table->dropIndex(['owner_id', 'end_date', 'title', 'id']);
        });
    }
};
