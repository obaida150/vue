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
        Schema::create('ad', function (Blueprint $table) {
            $table->id(); // Primärschlüssel
            $table->unsignedBigInteger('user_id'); // Beziehung zu Benutzern
            $table->unsignedBigInteger('team_id')->nullable(); // Beziehung zu Teams
            $table->string('kunde', 255)->nullable(); // Kunde
            $table->date('datum')->nullable(); // Datum
            $table->date('enddatum')->nullable(); // Enddatum
            $table->time('zeit')->nullable(); // Zeit
            $table->timestamps(); // Zeitstempel "created_at" und "updated_at"

            // Fremdschlüssel-Beziehungen
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad');
    }
};
