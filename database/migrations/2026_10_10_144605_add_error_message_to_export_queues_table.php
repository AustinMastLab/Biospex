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
        Schema::table('export_queues', function (Blueprint $table) {
            // Why a zip export failed, set by export:listen (SqsListenerExportUpdate).
            $table->text('error_message')->nullable()->after('error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('export_queues', function (Blueprint $table) {
            $table->dropColumn('error_message');
        });
    }
};
