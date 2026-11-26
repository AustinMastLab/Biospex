<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * On the servers (and locally) wedigbio_events is a view over WeDigBio Reports' events table, with these columns.
     * Where it is still the plain table the first migration created (the test database), add them so the code that
     * reads slug, name or channel_key works there too. Where the view already has them, this does nothing.
     */
    public function up(): void
    {
        if (Schema::hasColumn('wedigbio_events', 'slug')) {
            return;
        }

        Schema::table('wedigbio_events', function (Blueprint $table) {
            $table->string('slug')->default('');
            $table->string('name')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->string('display_alias')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('season')->nullable();
            $table->unsignedBigInteger('legacy_event_id')->nullable();
            $table->string('channel_key')->default('');
            $table->integer('has_transcriptions')->default(0);
        });
    }

    /**
     * Nothing to undo where the view supplies the columns; elsewhere the table goes with its create migration.
     */
    public function down(): void
    {
        //
    }
};
