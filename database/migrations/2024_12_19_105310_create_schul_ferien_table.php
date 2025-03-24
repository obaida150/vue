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
        Schema::create('schul_ferien', function (Blueprint $table) {
            $table->id(); // Primärschlüssel
            $table->date('datum'); // Datum der Ferien
            $table->string('grund', 255); // Grund der Ferien
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schul_ferien');
    }
};
