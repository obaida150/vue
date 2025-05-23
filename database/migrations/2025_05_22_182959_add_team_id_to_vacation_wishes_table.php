<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTeamIdToVacationWishesTable extends Migration
{
    /**
     * Run the migration.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vacation_wishes', function (Blueprint $table) {
            // Zuerst prüfen, ob die Spalte noch nicht existiert
            if (!Schema::hasColumn('vacation_wishes', 'team_id')) {
                $table->foreignId('team_id')->nullable()->constrained()->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migration.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vacation_wishes', function (Blueprint $table) {
            // Prüfen, ob die Spalte existiert, bevor sie entfernt wird
            if (Schema::hasColumn('vacation_wishes', 'team_id')) {
                $table->dropForeign(['team_id']);
                $table->dropColumn('team_id');
            }
        });
    }
}
