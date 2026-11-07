<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('chart_of_accounts', 'income_balance')) {
                $table->string('income_balance', 50)->nullable()->after('account_type');
            }
            if (! Schema::hasColumn('chart_of_accounts', 'control_head')) {
                $table->string('control_head', 191)->nullable()->after('income_balance');
            }
            if (! Schema::hasColumn('chart_of_accounts', 'direct_posting')) {
                $table->boolean('direct_posting')->default(true)->after('is_leaf');
            }
            $table->string('account_name_bn', 191)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn(['income_balance', 'control_head', 'direct_posting']);
        });
    }
};
