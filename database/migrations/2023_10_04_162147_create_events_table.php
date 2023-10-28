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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('event_type_id')->references('id')->on('event_types')->onDelete('cascade')->onUpdate('cascade');
            $table->string('title');
            $table->longText('description');
            $table->datetime('date_time');
            $table->string('thumbnail');
            $table->string('qr_code');
            $table->string('venue_name');
            $table->string('venue_address');
            $table->string('latitude');
            $table->string('longitude');
            $table->string('state');
            $table->string('city');
            $table->string('zip_code');
            $table->enum('is_premium', ['0', '1'])->default('0');
            $table->bigInteger('code');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
