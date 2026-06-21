<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            // Freeze pauses the clock: snapshot the remaining shelf so thaw can resume it (ADR-0003).
            $table->date('frozen_on')->nullable()->after('status');
            $table->unsignedInteger('frozen_days_remaining')->nullable()->after('frozen_on');
            // Discards are logged for waste-pattern learning.
            $table->date('discarded_on')->nullable()->after('frozen_days_remaining');
        });

        Schema::table('ingredients', function (Blueprint $table) {
            // Rules-based freeze suggestions skip items that freeze badly.
            $table->boolean('freezable')->default(true)->after('requires_open_tracking');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['frozen_on', 'frozen_days_remaining', 'discarded_on']);
        });

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('freezable');
        });
    }
};
