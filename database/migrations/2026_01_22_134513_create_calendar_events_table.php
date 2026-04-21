<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('created_by')->index()->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_at')->index();
            $table->dateTime('end_at')->nullable();
            $table->boolean('is_all_day')->default(false);

            $table->boolean('reminder_sent')->default(false)->index();
            $table->dateTime('reminder_sent_at')->nullable();

            $table->timestamps();
        });

        Schema::create('calendar_event_attendees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('calendar_event_id')->index();
            $table->unsignedBigInteger('user_id')->index(); // attendee user_id
            $table->string('phone')->nullable(); // snapshot from tbl_user_profile.phone
            $table->timestamps();

            $table->unique(['calendar_event_id', 'user_id']);
            $table->foreign('calendar_event_id')->references('id')->on('calendar_events')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_attendees');
        Schema::dropIfExists('calendar_events');
    }
};
