<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Strategie: Die Unique Constraint entfernen, ohne Foreign Keys zu stören
        // Dies wird dynamisch prüfen, welche Foreign Keys und Constraints existieren

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // 1. Prüfe und entferne die Unique Constraint (nicht die Foreign Keys!)
        // Die Unique Constraint war zu restriktiv und verhinderte mehrere Reservierungen am selben Tag
        $constraints = DB::select("
            SELECT CONSTRAINT_NAME
            FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'parking_reservations'
            AND CONSTRAINT_TYPE = 'UNIQUE'
        ");

        if (!empty($constraints)) {
            try {
                DB::statement('ALTER TABLE parking_reservations DROP INDEX parking_reservations_parking_spot_id_reservation_date_unique');
            } catch (\Exception $e) {
                // Versuche mit anderem Namen
                try {
                    DB::statement('ALTER TABLE parking_reservations DROP CONSTRAINT parking_reservations_parking_spot_id_reservation_date_unique');
                } catch (\Exception $e2) {
                    // Constraint existiert möglicherweise nicht
                }
            }
        }

        // 2. Füge Performance-Indizes hinzu
        try {
            DB::statement('CREATE INDEX idx_parking_reservations_spot_time
                           ON parking_reservations(parking_spot_id, start_time, end_time)');
        } catch (\Exception $e) {
            // Index existiert möglicherweise bereits
        }

        try {
            DB::statement('CREATE INDEX idx_parking_reservations_user_date
                           ON parking_reservations(user_id, reservation_date)');
        } catch (\Exception $e) {
            // Index existiert möglicherweise bereits
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        // Stelle die Unique Constraint wieder her
        try {
            DB::statement('ALTER TABLE parking_reservations ADD UNIQUE parking_reservations_parking_spot_id_reservation_date_unique (parking_spot_id, reservation_date)');
        } catch (\Exception $e) {
            // Constraint existiert möglicherweise bereits
        }

        // Entferne die Performance-Indizes
        try {
            DB::statement('DROP INDEX idx_parking_reservations_spot_time ON parking_reservations');
        } catch (\Exception $e) {
            // Index existiert möglicherweise nicht
        }

        try {
            DB::statement('DROP INDEX idx_parking_reservations_user_date ON parking_reservations');
        } catch (\Exception $e) {
            // Index existiert möglicherweise nicht
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};
