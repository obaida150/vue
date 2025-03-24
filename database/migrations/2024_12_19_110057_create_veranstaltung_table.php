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
        Schema::create('veranstaltung', function (Blueprint $table) {
            $table->id(); // Primärschlüssel
            $table->string('name', 255); // Name der Veranstaltung
            $table->date('datum')->nullable(); // Datum der Veranstaltung
            $table->timestamps(); // Zeitstempel "created_at" und "updated_at"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('veranstaltung');
    }
};
