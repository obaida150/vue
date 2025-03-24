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
        Schema::create('zeitung', function (Blueprint $table) {
            $table->id(); // Primärschlüssel
            $table->string('originalname', 255); // Originalname der Datei
            $table->string('filename', 255); // Gespeicherter Dateiname
            $table->timestamps(); // Zeitstempel "created_at" und "updated_at"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zeitung');
    }
};
