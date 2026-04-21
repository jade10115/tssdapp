<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('checkouts', function (Blueprint $table) {
        $table->unsignedBigInteger('approved_by_id')->nullable()->after('user_id');
        $table->unsignedBigInteger('issued_by_id')->nullable()->after('approved_by_id');

        $table->foreign('approved_by_id')->references('user_id')->on('tbl_user_profile')->nullOnDelete();
        $table->foreign('issued_by_id')->references('user_id')->on('tbl_user_profile')->nullOnDelete();
    });
}

public function down()
{
    Schema::table('checkouts', function (Blueprint $table) {
        $table->dropForeign(['approved_by_id']);
        $table->dropForeign(['issued_by_id']);
        $table->dropColumn(['approved_by_id', 'issued_by_id']);
    });
}

};
