<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vacation_requests', function (Blueprint $table) {
            $table->enum('day_type', ['full_day', 'morning', 'afternoon'])
                ->default('full_day')
                ->after('days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacation_requests', function (Blueprint $table) {
            $table->dropColumn('day_type');
        });
    }
};
