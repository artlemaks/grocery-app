<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            // AI imports (photo/url) land as drafts pending review — never auto-saved
            // (indication: ai-imports-need-review-screen).
            $table->boolean('is_draft')->default(false)->after('image_url');
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn('is_draft');
        });
    }
};
