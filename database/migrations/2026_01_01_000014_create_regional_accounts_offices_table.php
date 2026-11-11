<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('regional_accounts_offices')) {
            Schema::create('regional_accounts_offices', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name', 255);
                $table->string('name_bn', 255);
                $table->text('address')->nullable();
                $table->string('type', 50)->default('regional'); // regional, central_overhead, generation
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('regional_accounts_offices');
    }
};
