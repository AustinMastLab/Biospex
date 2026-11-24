<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The amCharts 4 chart settings and data per project, replaced by project_daily_transcriptions (#645).
     */
    public function up(): void
    {
        Schema::dropIfExists('amcharts');
    }

    /**
     * Reverse the migrations: the empty table; app:refresh-project-chart-data fills the new one, not this.
     */
    public function down(): void
    {
        if (! Schema::hasTable('amcharts')) {
            Schema::create('amcharts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('project_id')->index('amcharts_project_id_foreign');
                $table->json('series')->nullable();
                $table->json('data')->nullable();
                $table->tinyInteger('queued')->default(0)->index();
                $table->timestamp('created_at')->nullable()->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
                $table->foreign('project_id')->references('id')->on('projects')->onUpdate('no action')->onDelete('cascade');
            });
        }
    }
};
