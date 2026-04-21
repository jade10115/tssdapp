<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_user_profile', function (Blueprint $table) {
            // if column already exists, don't re-add (safe guard)
            if (!Schema::hasColumn('tbl_user_profile', 'position_id')) {
                $table->unsignedBigInteger('position_id')->nullable()->after('suffix');
            }

            // add FK (name is optional, but explicit is clearer)
            $table->foreign('position_id', 'tbl_user_profile_position_id_foreign')
                ->references('id')
                ->on('tbl_position')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_user_profile', function (Blueprint $table) {
            // drop FK first then column
            if (Schema::hasColumn('tbl_user_profile', 'position_id')) {
                $table->dropForeign('tbl_user_profile_position_id_foreign');
                $table->dropColumn('position_id');
            }
        });
    }
};
