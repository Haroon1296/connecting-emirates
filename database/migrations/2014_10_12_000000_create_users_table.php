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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->enum('user_type', ['admin','business','customer']);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('profile_image')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('restaurant_name')->nullable();
            

            $table->string('country_code')->nullable();
            $table->longText('about')->nullable();
            $table->date('date_of_birth')->nullable();

            $table->enum('registering_as', ['restaurant', 'adventure', 'event'])->nullable();
            $table->string('operting_emirates')->nullable();
            $table->integer('category_id')->nullable();
            $table->string('venue_seating')->nullable();
            $table->string('dietary')->nullable();
            $table->string('dietary')->nullable();
            $table->string('menu_image')->nullable();
            $table->string('license_image')->nullable();
            // Business hours []
            // Venue type []



            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('emirates')->nullable();
            $table->string('nationality')->nullable();

            $table->string('location')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();


            $table->string('customer_id')->nullable();

            $table->rememberToken()->nullable();

            $table->enum('is_profile_complete', ['0','1'])->default('0');
            $table->enum('device_type', ['ios','android','web'])->nullable();
            $table->longText('device_token')->nullable();
            $table->enum('social_type', ['google','facebook','twitter','instagram','apple','phone'])->nullable();
            $table->longText('social_token')->nullable();
            $table->enum('push_notification', ['0','1'])->default('1');
            $table->enum('is_verified', ['0','1'])->default('0');
            $table->enum('is_social', ['0','1'])->default('0');
            $table->integer('verified_code')->nullable();
            $table->enum('is_blocked', ['0','1'])->default('0');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
