<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_products', function (Blueprint $table) {
            $table->id();
            
            // Foreign key to users table (logged-in user)
            $table->unsignedBigInteger('user_id');
            
            // Example product fields
            $table->string('product_name');
            $table->text('image')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('current_stock')->default(0);
       

            $table->timestamps();

            // Add the foreign key constraint
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_products');
    }
};
