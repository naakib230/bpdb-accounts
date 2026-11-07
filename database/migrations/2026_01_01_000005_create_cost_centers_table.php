<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 191);
            $table->string('name_bn', 191);
            $table->string('location', 100)->nullable();
            $table->string('division_or_zone', 100)->nullable();
            $table->string('contact_person', 100)->nullable();
            $table->string('phone', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_cost_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cost_center_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'cost_center_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_cost_centers');
        Schema::dropIfExists('cost_centers');
    }
};
