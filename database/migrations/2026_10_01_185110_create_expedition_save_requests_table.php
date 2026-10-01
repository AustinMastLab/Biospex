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
        Schema::create('expedition_save_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('expedition_id')->unique();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('operation', 10);
            $table->json('subject_ids');
            $table->unsignedBigInteger('revision')->default(1);
            $table->string('status', 20)->default('pending');
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expedition_save_requests');
    }
};
