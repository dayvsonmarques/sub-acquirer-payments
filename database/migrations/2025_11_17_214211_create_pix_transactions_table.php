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
        Schema::create('pix_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subacquirer_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id')->unique(); // ID único da transação
            $table->string('external_id')->nullable(); // ID retornado pelo subadquirente
            $table->decimal('amount', 15, 2);
            $table->string('pix_key'); // Chave PIX (CPF, email, telefone, chave aleatória)
            $table->string('pix_key_type'); // cpf, email, phone, random
            $table->enum('status', ['PENDING', 'CONFIRMED', 'FAILED', 'CANCELLED'])->default('PENDING');
            $table->text('description')->nullable();
            $table->json('request_data')->nullable(); // Dados enviados ao subadquirente
            $table->json('response_data')->nullable(); // Resposta do subadquirente
            $table->json('webhook_data')->nullable(); // Dados recebidos via webhook
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['subacquirer_id', 'status']);
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pix_transactions');
    }
};
