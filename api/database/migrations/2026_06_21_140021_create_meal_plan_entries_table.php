<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_plan_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_plan_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            // Slot tag (e.g. breakfast/dinner) lives in the tags table.
            $table->foreignId('slot_tag_id')->constrained('tags')->restrictOnDelete();
            $table->foreignId('recipe_id')->constrained()->restrictOnDelete();
            $table->boolean('is_split')->default(false);
            $table->json('members')->nullable();
            $table->timestamps();

            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_plan_entries');
    }
};
