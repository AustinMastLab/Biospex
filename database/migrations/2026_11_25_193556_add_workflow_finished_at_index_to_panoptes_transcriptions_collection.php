<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mongodb')->table('panoptes_transcriptions', function (Blueprint $collection) {
            // The Digitizations chart counts a project's transcriptions per day from its last stored day
            // (App\Services\Chart\ProjectTranscriptionChart). With only the date index, that read every project's
            // transcriptions since that day; with the workflow first, it reads only the project's own.
            if (! $collection->hasIndex('workflow_id_1_classification_finished_at_1')) {
                $collection->index(['workflow_id' => 1, 'classification_finished_at' => 1], 'workflow_id_1_classification_finished_at_1');
            }

            // The new index starts with workflow_id, so it serves every query this one did.
            $collection->dropIndexIfExists('workflow_id_1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->table('panoptes_transcriptions', function (Blueprint $collection) {
            if (! $collection->hasIndex('workflow_id_1')) {
                $collection->index(['workflow_id' => 1], 'workflow_id_1');
            }

            $collection->dropIndexIfExists('workflow_id_1_classification_finished_at_1');
        });
    }
};
