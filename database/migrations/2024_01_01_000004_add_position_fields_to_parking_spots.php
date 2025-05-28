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
        Schema::table('parking_spots', function (Blueprint $table) {
            $table->decimal('position_x', 5, 2)->nullable()->comment('X position in percentage');
            $table->decimal('position_y', 5, 2)->nullable()->comment('Y position in percentage');
            $table->string('image_url')->nullable()->comment('Custom image for this location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parking_spots', function (Blueprint $table) {
            $table->dropColumn(['position_x', 'position_y', 'image_url']);
        });
    }
};
