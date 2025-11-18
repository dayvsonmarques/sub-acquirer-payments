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
        Schema::create('withdraw_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subacquirer_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id')->unique(); // ID único da transação
            $table->string('external_id')->nullable(); // ID retornado pelo subadquirente
            $table->decimal('amount', 15, 2);
            $table->string('bank_code'); // Código do banco
            $table->string('agency'); // Agência
            $table->string('account'); // Conta
            $table->string('account_type'); // checking, savings
            $table->string('account_holder_name'); // Nome do titular
            $table->string('account_holder_document'); // CPF/CNPJ
            $table->enum('status', ['PENDING', 'PAID', 'FAILED', 'CANCELLED'])->default('PENDING');
            $table->text('description')->nullable();
            $table->json('request_data')->nullable(); // Dados enviados ao subadquirente
            $table->json('response_data')->nullable(); // Resposta do subadquirente
            $table->json('webhook_data')->nullable(); // Dados recebidos via webhook
            $table->timestamp('paid_at')->nullable();
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
        Schema::dropIfExists('withdraw_transactions');
    }
};
