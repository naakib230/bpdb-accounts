<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_reconciliations', 'cost_center_id')) {
                $table->foreignId('cost_center_id')->nullable()->after('id')->constrained('cost_centers')->nullOnDelete();
            }
            if (! Schema::hasColumn('bank_reconciliations', 'from_date')) {
                $table->date('from_date')->nullable()->after('bank_account_id');
            }
            if (! Schema::hasColumn('bank_reconciliations', 'to_date')) {
                $table->date('to_date')->nullable()->after('from_date');
            }
        });

        Schema::table('bank_reconciliation_items', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_reconciliation_items', 'schedule')) {
                $table->string('schedule', 10)->default('A1')->after('type'); // A1, A2, B1, B2
            }
        });
    }

    public function down(): void
    {
        Schema::table('bank_reconciliations', function (Blueprint $table) {
            if (Schema::hasColumn('bank_reconciliations', 'cost_center_id')) {
                $table->dropForeign(['cost_center_id']);
                $table->dropColumn(['cost_center_id', 'from_date', 'to_date']);
            }
        });

        Schema::table('bank_reconciliation_items', function (Blueprint $table) {
            if (Schema::hasColumn('bank_reconciliation_items', 'schedule')) {
                $table->dropColumn('schedule');
            }
        });
    }
};
