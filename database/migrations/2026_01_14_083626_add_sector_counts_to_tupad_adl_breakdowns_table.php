<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tupad_adl_breakdowns', function (Blueprint $table) {
            // Add after payout_date
            $table->unsignedInteger('four_ps')->default(0)->after('payout_date');
            $table->unsignedInteger('seniors')->default(0)->after('four_ps');
            $table->unsignedInteger('pwd')->default(0)->after('seniors');
            $table->unsignedInteger('female')->default(0)->after('pwd');
        });
    }

    public function down(): void
    {
        Schema::table('tupad_adl_breakdowns', function (Blueprint $table) {
            $table->dropColumn(['four_ps', 'seniors', 'pwd', 'female']);
        });
    }
};
