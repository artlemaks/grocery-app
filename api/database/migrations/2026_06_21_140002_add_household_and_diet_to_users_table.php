<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Household membership; null when user has no household yet.
            $table->foreignId('household_id')->nullable()->after('id')
                ->constrained('households')->nullOnDelete();
            // App\Enums\DietType value.
            $table->string('diet_type')->nullable();
            // Allergens / dislikes.
            $table->json('dietary_dislikes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('household_id');
            $table->dropColumn(['diet_type', 'dietary_dislikes']);
        });
    }
};
