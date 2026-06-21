<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            // App\Enums\InventoryLocation.
            $table->string('location');
            $table->decimal('remaining', 3, 2)->default(1.00);
            $table->date('purchased_on')->nullable();
            $table->boolean('is_opened')->default(false);
            $table->date('opened_on')->nullable();
            $table->date('sealed_best_before')->nullable();
            $table->date('effective_best_before')->nullable();
            // App\Enums\InventoryStatus.
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index('status');
            $table->index('effective_best_before');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
