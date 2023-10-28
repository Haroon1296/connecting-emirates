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
        Schema::create('shout_out_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('event_id')->references('id')->on('events')->onDelete('cascade')->onUpdate('cascade');
            $table->string('title');
            $table->foreignId('category_id')->references('id')->on('event_types')->onDelete('cascade')->onUpdate('cascade');
            $table->string('receiver');
            $table->longText('message');
            $table->integer('seat_number');
            $table->integer('status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shout_out_requests');
    }
};
