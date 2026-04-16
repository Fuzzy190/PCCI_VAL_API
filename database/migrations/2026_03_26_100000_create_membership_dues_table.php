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
        Schema::create('membership_dues', function (Blueprint $table) {
            $table->id();
            
            // Foreign Key to members
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            
            // Dues Information
            $table->decimal('amount', 10, 2); // Amount owed
            $table->year('due_year'); // Year the dues are for
            $table->date('due_date'); // When dues are due
            $table->date('paid_date')->nullable(); // When they were paid
            
            // Status tracking
            $table->string('status')->default('pending'); // pending, paid, overdue, waived, expired
            
            // Warning tracking for expiration notifications
            // Warnings are sent at: 5 months, 3 months, and 1 month before expiration
            $table->timestamp('first_warning_sent_at')->nullable(); // ~5 months before expiration
            $table->timestamp('second_warning_sent_at')->nullable(); // ~3 months before expiration
            $table->timestamp('final_warning_sent_at')->nullable(); // ~1 month before expiration
            
            // Notes
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Unique constraint: One dues record per member per year
            $table->unique(['member_id', 'due_year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_dues');
    }
};
