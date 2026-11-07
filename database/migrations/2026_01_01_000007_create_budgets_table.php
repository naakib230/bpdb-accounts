<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fiscal_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cost_center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->cascadeOnDelete();
            $table->decimal('original_budget', 15, 2)->default(0.00);
            $table->decimal('revised_budget', 15, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['fiscal_year_id', 'cost_center_id', 'chart_of_account_id'], 'budget_unique_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
