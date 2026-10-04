<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per classification, event, team and user, so concurrent listeners
     * or retried jobs cannot double count a transcription.
     */
    public function up(): void
    {
        Schema::table('event_transcriptions', function (Blueprint $table) {
            $table->unique(['classification_id', 'event_id', 'team_id', 'user_id'], 'event_transcriptions_classification_event_team_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_transcriptions', function (Blueprint $table) {
            $table->dropUnique('event_transcriptions_classification_event_team_user_unique');
        });
    }
};
