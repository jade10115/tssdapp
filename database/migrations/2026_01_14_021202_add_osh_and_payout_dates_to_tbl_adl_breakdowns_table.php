<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tupad_adl_breakdowns', function (Blueprint $table) {
            // add AFTER status
            $table->date('osh_date')->nullable()->after('status');
            $table->date('payout_date')->nullable()->after('osh_date');
        });
    }

    public function down(): void
    {
        Schema::table('tupad_adl_breakdowns', function (Blueprint $table) {
            $table->dropColumn(['osh_date', 'payout_date']);
        });
    }
};
