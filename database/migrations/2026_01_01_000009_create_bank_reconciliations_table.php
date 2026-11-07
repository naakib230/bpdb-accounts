<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->date('statement_date');
            $table->decimal('bank_statement_balance', 15, 2)->default(0.00);
            $table->decimal('cash_book_balance', 15, 2)->default(0.00);
            $table->decimal('reconciled_balance', 15, 2)->default(0.00);
            $table->decimal('difference', 15, 2)->default(0.00);
            $table->enum('status', ['draft', 'reconciled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('prepared_by_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['outstanding_cheque', 'deposit_in_transit', 'bank_charge', 'interest_credited', 'other_adjustment']);
            $table->date('item_date');
            $table->string('reference_no', 100)->nullable();
            $table->string('description', 255);
            $table->decimal('amount', 15, 2);
            $table->enum('effect', ['add', 'less']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_items');
        Schema::dropIfExists('bank_reconciliations');
    }
};
