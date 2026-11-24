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
        Schema::table('transcription_locations', function (Blueprint $table) {
            // The project page's state and county maps count a project's locations per county.
            $table->index(['project_id', 'state_county_id'], 'transcription_locations_project_county_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transcription_locations', function (Blueprint $table) {
            $table->dropIndex('transcription_locations_project_county_index');
        });
    }
};
