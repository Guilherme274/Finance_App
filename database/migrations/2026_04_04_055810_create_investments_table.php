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
        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('pluggy_investment_id')->unique();
            $table->string('pluggy_item_id')->index();
            $table->string('name');
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('currency')->default('BRL');
            $table->string('type')->nullable(); // FIXED_INCOME, MUTUAL_FUND, EQUITY, etc.
            $table->string('subtype')->nullable(); // CDB, LCI, etc.
            $table->string('number')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investments');
    }
};
