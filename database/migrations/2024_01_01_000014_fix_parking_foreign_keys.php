<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Prüfen und reparieren der Foreign Keys
        
        // 1. Prüfen ob parking_spots Tabelle existiert und korrekt ist
        if (!Schema::hasTable('parking_spots')) {
            Schema::create('parking_spots', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parking_location_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->string('type');
                $table->string('identifier')->unique();
                $table->boolean('is_active')->default(true);
                $table->boolean('requires_approval')->default(false);
                $table->json('restrictions')->nullable();
                $table->integer('sort_order')->default(0);
                $table->json('visual_position')->nullable();
                $table->timestamps();
            });
        }

        // 2. Foreign Key für parking_reservations hinzufügen falls nicht vorhanden
        if (Schema::hasTable('parking_reservations') && Schema::hasTable('parking_spots')) {
            // Prüfen ob Foreign Key bereits existiert
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'parking_reservations' 
                AND CONSTRAINT_NAME LIKE '%foreign%'
            ");

            if (empty($foreignKeys)) {
                Schema::table('parking_reservations', function (Blueprint $table) {
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    $table->foreign('parking_spot_id')->references('id')->on('parking_spots')->onDelete('cascade');
                });
            }
        }

        // 3. Unique Constraint hinzufügen falls nicht vorhanden
        $uniqueConstraints = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'parking_reservations' 
            AND CONSTRAINT_TYPE = 'UNIQUE'
        ");

        if (empty($uniqueConstraints)) {
            Schema::table('parking_reservations', function (Blueprint $table) {
                $table->unique(['parking_spot_id', 'reservation_date']);
            });
        }
    }

    public function down()
    {
        Schema::table('parking_reservations', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['parking_spot_id']);
            $table->dropUnique(['parking_spot_id', 'reservation_date']);
        });
    }
};
