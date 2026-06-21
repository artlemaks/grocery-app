<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Generic async-result envelope every AI feature writes to (enqueue → poll).
        Schema::create('ai_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->string('type');               // App\Enums\AiJobType
            $table->string('status')->default('pending'); // App\Enums\AiJobStatus
            $table->json('input')->nullable();
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_jobs');
    }
};
