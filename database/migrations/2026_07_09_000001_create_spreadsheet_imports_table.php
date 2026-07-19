<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spreadsheet_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('type')->default('transactions'); // transactions, investments
            $table->integer('rows_imported')->default(0);
            $table->integer('rows_skipped')->default(0);
            $table->string('status')->default('success'); // success, partial, failed
            $table->json('column_mapping')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spreadsheet_imports');
    }
};
