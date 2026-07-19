<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('institution')->nullable();
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('currency')->default('BRL');
            $table->string('type')->nullable(); // CHECKING, SAVINGS, CREDIT
            $table->string('color')->default('#8b5cf6'); // UI color
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
