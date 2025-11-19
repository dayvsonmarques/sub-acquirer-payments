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
        Schema::create('webhook_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type'); // 'pix' or 'withdraw'
            $table->unsignedBigInteger('transaction_id');
            $table->string('status'); // 'success', 'failed', 'pending'
            $table->text('payload')->nullable();
            $table->text('response')->nullable();
            $table->string('source')->default('simulation'); // 'simulation' or 'external'
            $table->text('error_message')->nullable();
            $table->integer('attempt_number')->default(1);
            $table->timestamps();

            $table->index(['transaction_type', 'transaction_id']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_attempts');
    }
};
