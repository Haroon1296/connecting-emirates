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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('title');
            $table->string('brand_name');
            $table->string('model_nmae');
            $table->string('category');
            $table->longText('description');
            $table->string('amount');
            $table->string('stock_quantity');
            $table->string('discount');
            $table->datetime('promotion_period_start_at');
            $table->datetime('promotion_period_end_at');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
