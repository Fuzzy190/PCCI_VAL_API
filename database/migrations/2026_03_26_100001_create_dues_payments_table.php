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
        Schema::create('dues_payments', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('membership_due_id')->constrained('membership_dues')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('received_by_user_id')->constrained('users')->cascadeOnDelete();
            
            // Payment Information
            $table->string('or_number')->unique(); // Official Receipt number
            $table->decimal('amount', 10, 2); // Amount paid
            $table->date('payment_date'); // When payment was received
            
            // Payment method tracking
            $table->string('payment_method')->nullable(); // cash, check, bank_transfer, etc
            $table->string('reference_number')->nullable(); // Check #, transfer ID, etc
            
            // Additional info
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dues_payments');
    }
};
