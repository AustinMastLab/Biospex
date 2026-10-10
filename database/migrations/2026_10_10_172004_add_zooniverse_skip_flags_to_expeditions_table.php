<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expeditions', function (Blueprint $table) {
            // Replace config('zooniverse.skip_api') and config('zooniverse.skip_reconcile'), set in the admin panel.
            $table->boolean('skip_api')->default(false)->after('locked');
            $table->boolean('skip_reconcile')->default(false)->after('skip_api');
        });

        // Carry over the expeditions the config lists skipped.
        DB::table('expeditions')->whereIn('id', [55])->update(['skip_api' => true]);
        DB::table('expeditions')->whereIn('id', [27, 45, 194, 223])->update(['skip_reconcile' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expeditions', function (Blueprint $table) {
            $table->dropColumn(['skip_api', 'skip_reconcile']);
        });
    }
};
