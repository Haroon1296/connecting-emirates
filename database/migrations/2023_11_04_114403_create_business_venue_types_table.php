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
        Schema::create('business_venue_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('venue_type_id')->references('id')->on('venue_types')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_venue_types');
    }
};
