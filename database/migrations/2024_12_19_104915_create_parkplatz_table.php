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
        Schema::create('parkplatz', function (Blueprint $table) {
            $table->id(); // Primärschlüssel
            $table->unsignedBigInteger('user_id'); // Beziehung zu Benutzern
            $table->date('datum')->nullable(); // Datum
            $table->time('zeit_von')->nullable(); // Zeit von
            $table->time('zeit_bis')->nullable(); // Zeit bis
            $table->string('platznummer', 255)->nullable(); // Parkplatznummer
            $table->timestamps(); // Zeitstempel "created_at" und "updated_at"

            // Fremdschlüssel-Beziehungen
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parkplatz');
    }
};
