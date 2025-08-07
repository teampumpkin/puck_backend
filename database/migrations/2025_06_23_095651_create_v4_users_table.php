<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('v4_users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->string('zip')->nullable();

            //child
            $table->boolean('is_child')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('v4_users')->nullOnDelete();
            $table->string('username')->nullable()->unique();
            $table->string('password')->nullable();

            $table->boolean('enable_private_account')->default(false);
            $table->boolean('receive_news_offers')->default(false);
            $table->boolean('terms_accepted')->default(false);
            $table->string('role')->nullable();
            $table->boolean('is_onboarded')->default(false);
            $table->string('otp')->nullable();
            $table->timestamp('otp_expiry')->nullable();
            $table->timestamps();
        });

        Schema::create('player_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('v4_user_id')->constrained('v4_users')->cascadeOnDelete();
            $table->json('teams')->nullable();
            $table->json('leagues')->nullable();
            $table->enum('handedness', ['left', 'right', 'Ambidextrous'])->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->enum('position', ['forward', 'defense', 'goalie', 'right wing', 'left wing', 'centre'])->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->json('permissions')->nullable(); // Permissions for child accounts
            $table->timestamps();
        });

        Schema::create('coach_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('v4_user_id')->constrained('v4_users')->cascadeOnDelete();
            $table->json('leagues')->nullable();
            $table->json('teams')->nullable();
            $table->timestamps();
        });

        Schema::create('scout_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('v4_user_id')->constrained('v4_users')->cascadeOnDelete();
            $table->integer('scouting_years')->nullable();
            $table->string('level_hockey_played')->nullable();
            $table->string('current_involvement_level')->nullable();
            $table->string('current_sport_role')->nullable();
            $table->json('leagues')->nullable();
            $table->json('teams')->nullable();
            $table->string('resume')->nullable();
            $table->json('references')->nullable();
            $table->timestamps();
        });

        Schema::create('team_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('v4_user_id')->constrained('v4_users')->cascadeOnDelete();
            $table->string('team_name')->nullable();
            $table->string('administrator_first_name')->nullable();
            $table->string('administrator_last_name')->nullable();
            $table->string('administrator_email')->nullable();
            $table->json('leagues')->nullable();
            $table->string('website')->nullable(); // team_website
            $table->string('address')->nullable(); // team_address
            $table->integer('team_years_running')->nullable();
            $table->timestamps();
        });
        Schema::create('academy_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('v4_user_id')->constrained('v4_users')->cascadeOnDelete();
            $table->string('academy_name')->nullable();
            $table->json('teams')->nullable();
            $table->string('administrator_first_name')->nullable();
            $table->string('administrator_last_name')->nullable();
            $table->json('leagues')->nullable();
            $table->string('website')->nullable(); // academy_website
            $table->string('address')->nullable(); // academy_address
            $table->integer('academy_years_running')->nullable();
            $table->string('main_team_name')->nullable();
            $table->timestamps();
        });

        Schema::create('organizer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('v4_user_id')->constrained('v4_users')->cascadeOnDelete();
            $table->string('business_name')->nullable();
            $table->string('business_phone')->nullable();
            $table->string('website')->nullable(); // business_website
            $table->string('address')->nullable(); // street_address
            $table->json('leagues')->nullable();
            $table->integer('number_years_organizing')->nullable();
            $table->json('link_of_previous_events')->nullable();
            $table->integer('number_of_events_organized')->nullable();
            $table->timestamps();
        });

        Schema::create('adviser_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('v4_user_id')->constrained('v4_users')->cascadeOnDelete();
            $table->string('business_name')->nullable();
            $table->string('business_phone')->nullable(); // business_phone
            $table->string('website')->nullable(); // business_website
            $table->string('address')->nullable(); // business_address
            $table->json('leagues')->nullable();
            $table->string('level_hockey_played')->nullable();
            $table->string('current_involvement_level')->nullable();
            $table->string('current_sport_role')->nullable();
            $table->integer('number_of_years_experience')->nullable();
            $table->string('resume')->nullable();
            $table->json('references')->nullable();
            $table->timestamps();
        });

        Schema::create('parent_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('v4_user_id')->constrained('v4_users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('fan_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('v4_user_id')->constrained('v4_users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fan_profiles');
        Schema::dropIfExists('parent_profiles');
        Schema::dropIfExists('adviser_profiles');
        Schema::dropIfExists('organizer_profiles');
        Schema::dropIfExists('academy_profiles');
        Schema::dropIfExists('team_profiles');
        Schema::dropIfExists('scout_profiles');
        Schema::dropIfExists('coach_profiles');
        Schema::dropIfExists('player_profiles');
        Schema::dropIfExists('v4_users');
    }
};
