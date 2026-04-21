<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reports', function (Blueprint $table) {
            $table->id();
           $table->foreignId('product_id')->constrained('tbl_products')->onDelete('cascade');
            $table->integer('month');
            $table->integer('year');
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('starting_qty')->default(0);
            $table->integer('added_qty')->default(0);
            $table->integer('released_qty')->default(0);
            $table->integer('remaining_qty')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reports');
    }
};
