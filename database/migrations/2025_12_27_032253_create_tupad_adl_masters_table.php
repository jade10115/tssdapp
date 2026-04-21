<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tupad_adl_masters', function (Blueprint $table) {
            $table->id();

            $table->string('adl');                 // ADL Name
            $table->string('sponsor')->nullable(); // Sponsor

            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);

            $table->string('status')->default('pending'); // pending/active/completed

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tupad_adl_masters');
    }
};
