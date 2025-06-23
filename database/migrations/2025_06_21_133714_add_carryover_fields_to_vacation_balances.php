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
            // Erweitere die vacation_balances Tabelle um Resturlaub-Felder
            $table->decimal('carry_over_days', 4, 1)->default(0.0)->comment('Übertragene Tage aus dem Vorjahr');
            $table->decimal('carry_over_used', 4, 1)->default(0.0)->comment('Verbrauchte übertragene Tage');
            $table->date('carry_over_expires_at')->nullable()->comment('Verfallsdatum für übertragene Tage (meist 31.03.)');
            $table->decimal('max_carry_over', 4, 1)->default(10.0)->comment('Maximale Übertragung pro Jahr');
            
            // Index für bessere Performance bei Abfragen
            $table->index(['carry_over_expires_at', 'carry_over_days'], 'idx_vacation_balances_carry_over');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacation_balances', function (Blueprint $table) {
            // Entferne den Index zuerst
            $table->dropIndex('idx_vacation_balances_carry_over');
            
            // Dann die Spalten
            $table->dropColumn([
                'carry_over_days',
                'carry_over_used', 
                'carry_over_expires_at',
                'max_carry_over'
            ]);
        });
    }
};
