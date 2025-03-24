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
        Schema::create('urlaub_antraege', function (Blueprint $table) {
            $table->id(); // Primärschlüssel
            $table->enum('antrag', ['urlaub', 'krankheit', 'sonstiges'])->nullable(); // Art des Antrags
            $table->text('anmerkungen')->nullable(); // Anmerkungen
            $table->date('datum')->nullable(); // Datum
            $table->enum('art', ['ganztag', 'vormittag', 'nachmittag'])->nullable(); // Art des Urlaubs
            $table->enum('status', ['AN', 'U', 'AB'])->default('AN'); // Status des Antrags
            $table->boolean('leiter_bestaetigung')->default(false); // Leiter-Bestätigung
            $table->text('leiter_anmerkungen')->nullable(); // Leiter-Anmerkungen
            $table->unsignedBigInteger('user_id')->nullable(); // Beziehung zu Benutzern
            $table->unsignedBigInteger('vertreter_id')->nullable(); // Beziehung zum Vertreter
            $table->timestamps(); // Zeitstempel "created_at" und "updated_at"
            $table->softDeletes(); // Soft-Deletes "deleted_at"

            // Fremdschlüssel-Beziehungen
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('vertreter_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('urlaub_antraege');
    }
};
