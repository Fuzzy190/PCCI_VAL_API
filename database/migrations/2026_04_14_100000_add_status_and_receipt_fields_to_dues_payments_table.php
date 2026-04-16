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
        // Check if status exists before adding to prevent partial migration crashes
        if (!Schema::hasColumn('dues_payments', 'status')) {
            Schema::table('dues_payments', function (Blueprint $table) {
                $table->string('status')->default('paid')->after('payment_date');
            });
        }

        // Check if receipt_image_url exists before adding
        if (!Schema::hasColumn('dues_payments', 'receipt_image_url')) {
            Schema::table('dues_payments', function (Blueprint $table) {
                $table->string('receipt_image_url')->nullable()->after('notes');
            });
        }

        // Apply column modifications
        Schema::table('dues_payments', function (Blueprint $table) {
            $table->date('payment_date')->nullable()->change();
            $table->foreignId('received_by_user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dues_payments', function (Blueprint $table) {
            if (Schema::hasColumn('dues_payments', 'status')) {
                $table->dropColumn('status');
            }
            
            if (Schema::hasColumn('dues_payments', 'receipt_image_url')) {
                $table->dropColumn('receipt_image_url');
            }

            $table->date('payment_date')->nullable(false)->change();
            $table->foreignId('received_by_user_id')->nullable(false)->change();
        });
    }
};