<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->date('week_start_date');
            // App\Enums\MealPlanStatus.
            $table->string('status')->default('planning');
            $table->timestamps();

            $table->unique(['household_id', 'week_start_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plans');
    }
};
