<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGithubRawEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('github_raw_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 64); // GitHub delivery GUID
            $table->string('event_name', 32); // event name
            $table->string('event_action', 32)->nullable()->default(null); // optional event-specific action name
            $table->text('payload')->nullable()->default(null); // the actual event payload
            $table->boolean('is_processed')->default(false); // whether the raw event has been processed by our GitHub worker
            $table->timestamp('last_processed_at')->nullable()->default(null); // timestamp for when our GitHub worker last processed the event 
            
            // specific timestamp columns since the timestamps() method does not default to use
            // the CURRENT_TIMESTAMP() expression for the created_at column
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->default(null);

            $table->index('event_name'); // we will be leveraging the event name for filters
            $table->unique('event_id'); // enforce uniqueness on the delivery GUID
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('github_raw_events');
    }
}
