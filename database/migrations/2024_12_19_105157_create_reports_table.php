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
        Schema::create('reports', function (Blueprint $table) {
            $table->id(); // Primärschlüssel
            $table->unsignedBigInteger('user_id'); // Beziehung zu Benutzern
            $table->enum('type', ['Berufsschule', 'Unternehmen'])->nullable(); // Typ
            $table->integer('year'); // Jahr
            $table->json('subjects_data')->nullable(); // JSON-Daten für Fächer
            $table->text('activities')->nullable(); // Aktivitäten
            $table->longText('unterweisungen')->nullable(); // Unterweisungen
            $table->date('date_from')->nullable(); // Startdatum
            $table->date('date_to')->nullable(); // Enddatum
            $table->unsignedBigInteger('instructor_id')->nullable(); // Beziehung zu Instruktoren
            $table->integer('berichtsnummer')->nullable(); // Berichtsnummer
            $table->longText('unterricht')->nullable(); // Unterricht
            $table->string('abteilung', 255)->nullable(); // Abteilung
            $table->timestamps(); // Zeitstempel "created_at" und "updated_at"

            // Fremdschlüssel-Beziehungen
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('instructor_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
