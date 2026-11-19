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
        Schema::connection('mongodb')->table('subjects', function (Blueprint $collection) {
            // The subject grid lists a project's subjects newest first; without this every page sorted the whole project.
            if (! $collection->hasIndex('project_id_1__id_-1')) {
                $collection->index(['project_id' => 1, '_id' => -1], 'project_id_1__id_-1');
            }

            // On fields subjects don't have (they use _id and expedition_ids), so they only slowed writes.
            $collection->dropIndexIfExists('id_-1');
            $collection->dropIndexIfExists('expedition_id_1_ocr_1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mongodb')->table('subjects', function (Blueprint $collection) {
            $collection->dropIndexIfExists('project_id_1__id_-1');
        });
    }
};
