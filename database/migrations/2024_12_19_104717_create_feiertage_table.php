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
        Schema::create('feiertage', function (Blueprint $table) {
            $table->id(); // Primärschlüssel
            $table->date('datum'); // Datum des Feiertags
            $table->string('grund', 255); // Grund des Feiertags
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feiertage');
    }
};
