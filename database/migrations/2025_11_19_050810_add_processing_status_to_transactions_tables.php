<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Adicionar PROCESSING ao enum de pix_transactions
        DB::statement("ALTER TABLE `pix_transactions` MODIFY COLUMN `status` ENUM('PENDING', 'PROCESSING', 'CONFIRMED', 'FAILED', 'CANCELLED') DEFAULT 'PENDING'");
        
        // Adicionar PROCESSING ao enum de withdraw_transactions
        DB::statement("ALTER TABLE `withdraw_transactions` MODIFY COLUMN `status` ENUM('PENDING', 'PROCESSING', 'PAID', 'FAILED', 'CANCELLED') DEFAULT 'PENDING'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remover PROCESSING do enum de pix_transactions
        DB::statement("ALTER TABLE `pix_transactions` MODIFY COLUMN `status` ENUM('PENDING', 'CONFIRMED', 'FAILED', 'CANCELLED') DEFAULT 'PENDING'");
        
        // Remover PROCESSING do enum de withdraw_transactions
        DB::statement("ALTER TABLE `withdraw_transactions` MODIFY COLUMN `status` ENUM('PENDING', 'PAID', 'FAILED', 'CANCELLED') DEFAULT 'PENDING'");
    }
};
