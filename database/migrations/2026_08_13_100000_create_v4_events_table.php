<?php
// database/migrations/2026_08_13_100000_create_v4_events_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('v4_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('v4_users')->cascadeOnDelete();
            $table->foreignId('payment_request_id')->nullable()->constrained('v4_payment_requests')->nullOnDelete();
            $table->string('event_type');
            $table->string('name');
            $table->text('description');
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->timestamp('registration_deadline')->nullable();
            $table->timestamp('payment_deadline')->nullable();
            $table->string('country');
            $table->string('province');
            $table->string('city');
            $table->string('venue')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->smallInteger('age_min')->nullable();
            $table->smallInteger('age_max')->nullable();
            $table->string('age_division')->nullable();
            $table->integer('cost_person_cents')->nullable();
            $table->string('cost_person_currency', 3)->default('CAD');
            $table->text('special_qualification')->nullable();
            $table->string('coordinator_name')->nullable();
            $table->string('business_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('website_url')->nullable();
            $table->jsonb('social_links')->nullable();
            $table->jsonb('scout_leagues')->nullable();
            $table->jsonb('positions')->nullable();
            $table->jsonb('birth_years')->nullable();
            $table->string('league')->nullable();
            $table->string('team')->nullable();
            $table->string('status')->default('pending_payment');
            $table->text('cancel_reason')->nullable();
            $table->text('delete_reason')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'end_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('v4_events');
    }
};
