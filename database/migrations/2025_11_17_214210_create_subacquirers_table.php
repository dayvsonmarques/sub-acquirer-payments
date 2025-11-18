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
        Schema::create('subacquirers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // SubadqA, SubadqB, etc
            $table->string('code')->unique(); // subadqa, subadqb, etc
            $table->string('base_url'); // URL base da API
            $table->json('config')->nullable(); // Configurações específicas
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subacquirers');
    }
};
