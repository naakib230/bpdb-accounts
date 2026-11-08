<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cost Centers
        Schema::table('cost_centers', function (Blueprint $table) {
            if (! Schema::hasColumn('cost_centers', 'regional_accounts_office_id')) {
                $table->foreignId('regional_accounts_office_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('regional_accounts_offices')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('cost_centers', 'trial_balance_type')) {
                $table->string('trial_balance_type', 50)
                    ->default('overhead')
                    ->after('name_bn'); // revenue_generation, revenue_distribution, overhead, deposit_works, development
            }
        });

        // 2. Bank Accounts
        Schema::table('bank_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_accounts', 'regional_accounts_office_id')) {
                $table->foreignId('regional_accounts_office_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('regional_accounts_offices')
                    ->nullOnDelete();
            }
        });

        // 3. Users
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'regional_accounts_office_id')) {
                $table->foreignId('regional_accounts_office_id')
                    ->nullable()
                    ->after('office_name')
                    ->constrained('regional_accounts_offices')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'regional_accounts_office_id')) {
                $table->dropForeign(['regional_accounts_office_id']);
                $table->dropColumn('regional_accounts_office_id');
            }
        });

        Schema::table('bank_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('bank_accounts', 'regional_accounts_office_id')) {
                $table->dropForeign(['regional_accounts_office_id']);
                $table->dropColumn('regional_accounts_office_id');
            }
        });

        Schema::table('cost_centers', function (Blueprint $table) {
            if (Schema::hasColumn('cost_centers', 'regional_accounts_office_id')) {
                $table->dropForeign(['regional_accounts_office_id']);
                $table->dropColumn('regional_accounts_office_id');
            }
            if (Schema::hasColumn('cost_centers', 'trial_balance_type')) {
                $table->dropColumn('trial_balance_type');
            }
        });
    }
};
