<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tupad_bens_status', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tupad_adl_breakdown_id')
                ->constrained('tupad_adl_breakdowns')
                ->cascadeOnDelete();

            // Names from C/D/E/F
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('ext_name')->nullable();

            // helpful for display/search
            $table->string('full_name')->index();

            // Flags (female-only rows are stored, so no need is_female)
            $table->boolean('is_pwd')->default(false)->index();
            $table->boolean('is_four_ps')->default(false)->index();
            $table->boolean('is_senior')->default(false)->index();

            $table->unsignedSmallInteger('age')->nullable();
            $table->string('sex_raw')->nullable();

            $table->timestamps();

            // prevents duplicates per breakdown
            $table->unique(['tupad_adl_breakdown_id', 'full_name'], 'uniq_breakdown_fullname');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tupad_bens_status');
    }
};
