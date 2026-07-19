<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->string('type')->default('DEBIT'); // CREDIT, DEBIT
            $table->string('category')->nullable(); // Alimentação, Transporte, etc.
            $table->json('tags')->nullable();
            $table->boolean('is_fixed')->default(false); // Gasto fixo mensal
            $table->string('account_name')->nullable(); // Nome da conta importada
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
