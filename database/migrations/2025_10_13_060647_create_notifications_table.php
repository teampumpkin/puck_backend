<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('v4_user_id')->constrained('v4_users')->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('general');
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('sent_via')->default('database');
            $table->string('status')->default('sent');

            // Redirection fields
            $table->string('redirect_url')->nullable();
            $table->string('action_type')->nullable();

            // Polymorphic reference fields
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->string('icon')->nullable()->after('message');
            $table->string('icon_type')->default('default')->after('icon'); // default, url, asset, material, custom
            $table->string('icon_color')->nullable()->after('icon_type'); // For material icons color
            $table->string('image_url')->nullable()->after('icon_color'); // For notification image

            // Soft deletes
            $table->softDeletes(); // Added soft delete

            $table->timestamps();

            // Indexes
            $table->index(['v4_user_id', 'read_at']);
            $table->index(['v4_user_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('type');
            $table->index('action_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
