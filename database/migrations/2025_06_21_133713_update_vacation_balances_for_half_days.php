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
        Schema::table('vacation_balances', function (Blueprint $table) {
            // Ändere integer zu decimal für Halbtage-Unterstützung
            $table->decimal('total_days', 4, 1)->default(0.0)->change();
            $table->decimal('used_days', 4, 1)->default(0.0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacation_balances', function (Blueprint $table) {
            // Zurück zu integer (Achtung: Datenverlust bei Dezimalwerten!)
            $table->integer('total_days')->change();
            $table->integer('used_days')->default(0)->change();
        });
    }
};
