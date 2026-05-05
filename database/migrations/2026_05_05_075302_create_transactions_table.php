<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('or_number')->nullable()->unique();
            
            // THE SCALABILITY KEY: This tells the system what the money is for
            $table->enum('transaction_type', [
                'initial_registration', 
                'renewal', 
                'upgrade',
                'event_ticket', // Future scalability
                'other'         // Future scalability
            ])->default('initial_registration');

            // Foreign Keys (Nullable because a transaction might belong to one or the other)
            $table->foreignId('applicant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('membership_due_id')->nullable()->constrained()->nullOnDelete();
            
            // Payment Details
            $table->decimal('amount', 10, 2);
            $table->string('payment_method'); // cash, gcash, bank_transfer
            $table->string('proof_of_payment_path')->nullable();
            
            // Status & Tracking
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};