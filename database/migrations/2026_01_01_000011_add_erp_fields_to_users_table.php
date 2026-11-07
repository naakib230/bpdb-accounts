<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'erp_no')) {
                $table->string('erp_no', 50)->nullable()->index()->after('username');
            }
            if (! Schema::hasColumn('users', 'nid_no')) {
                $table->string('nid_no', 50)->nullable()->index()->after('erp_no');
            }
            if (! Schema::hasColumn('users', 'designation_code')) {
                $table->string('designation_code', 50)->nullable()->index()->after('designation');
            }
            if (! Schema::hasColumn('users', 'cadre')) {
                $table->string('cadre', 100)->nullable()->after('designation_code');
            }
            if (! Schema::hasColumn('users', 'post_type')) {
                $table->string('post_type', 50)->nullable()->after('cadre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['erp_no', 'nid_no', 'designation_code', 'cadre', 'post_type']);
        });
    }
};
