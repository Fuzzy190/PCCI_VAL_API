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
        Schema::create('dues_notifications', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('membership_due_id')->nullable()->constrained('membership_dues')->cascadeOnDelete();
            
            // Notification Data
            $table->string('type'); // first_warning, second_warning, final_warning, expired, payment_received
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Additional data (due_year, amount, etc)
            
            // Status
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dues_notifications');
    }
};
