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
        // Transcriptions per expedition per day, the data behind a project's Digitizations chart, kept independent of
        // any chart library (App\Services\Chart\ProjectTranscriptionChart).
        Schema::create('project_daily_transcriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expedition_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('count');

            $table->unique(['expedition_id', 'date']);
            $table->index(['project_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_daily_transcriptions');
    }
};
