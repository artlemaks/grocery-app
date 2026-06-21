<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            // App\Enums\DietClass.
            $table->string('diet_class')->default('other');
            $table->string('default_unit')->nullable();
            $table->string('default_pack_size')->nullable();
            $table->string('category')->nullable();
            // Self-referencing substitute ingredient.
            $table->foreignId('substitute_ingredient_id')->nullable()
                ->constrained('ingredients')->nullOnDelete();
            $table->unsignedInteger('shelf_life_sealed_days')->nullable();
            $table->unsignedInteger('use_within_after_open_days')->nullable();
            $table->boolean('requires_open_tracking')->default(false);
            $table->timestamps();

            $table->index(['household_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
