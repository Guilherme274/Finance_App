<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('institution')->nullable();
            $table->decimal('amount_invested', 15, 2)->default(0); // Valor aplicado
            $table->decimal('balance', 15, 2)->default(0);         // Saldo atual
            $table->decimal('rate', 8, 4)->nullable();             // Rentabilidade %
            $table->string('currency')->default('BRL');
            $table->string('type')->nullable();    // RENDA_FIXA, FUNDO, ACAO, CRIPTO, etc.
            $table->string('subtype')->nullable(); // CDB, LCI, LCA, FII, etc.
            $table->date('purchase_date')->nullable();
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
