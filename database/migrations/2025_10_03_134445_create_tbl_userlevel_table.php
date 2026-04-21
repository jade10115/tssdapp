<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_userlevel', function (Blueprint $table) {
            $table->id();
            $table->string('level_name');   // Example: Admin, Staff, User
            $table->timestamps();
        });

        // Add foreign key column to users table
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('userlevel_id')->nullable()->after('id');

            $table->foreign('userlevel_id')
                  ->references('id')
                  ->on('tbl_userlevel')
                  ->onDelete('set null'); // or 'cascade'
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['userlevel_id']);
            $table->dropColumn('userlevel_id');
        });

        Schema::dropIfExists('tbl_userlevel');
    }
};
