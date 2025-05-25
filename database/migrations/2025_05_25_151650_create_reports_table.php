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
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['Betrieb', 'Berufsschule']);
            $table->integer('year');
            $table->json('subjects_data')->nullable();
            $table->longText('activities')->nullable();
            $table->longText('unterweisungen')->nullable();
            $table->date('date_from');
            $table->date('date_to');
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->date('erstellungsdatum');
            $table->integer('berichtsnummer');
            $table->longText('unterricht')->nullable();
            $table->string('abteilung')->nullable();
            $table->timestamps();
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
