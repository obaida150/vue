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
        Schema::table('teams', function (Blueprint $table) {
            $table->integer('ho_anzahl')->nullable()->after('personal_team'); // Anzahl der Homeoffice-Tage
            $table->unsignedBigInteger('vertreter_1')->nullable()->after('ho_anzahl'); // Vertreter 1
            $table->unsignedBigInteger('vertreter_2')->nullable()->after('vertreter_1'); // Vertreter 2
            $table->unsignedBigInteger('vertreter_3')->nullable()->after('vertreter_2'); // Vertreter 3
            $table->boolean('urlaubswunsch_sperre')->default(false)->after('vertreter_3'); // Urlaubswunsch-Sperre

            // Fremdschlüssel-Beziehungen
            $table->foreign('vertreter_1')->references('id')->on('users')->onDelete('set null');
            $table->foreign('vertreter_2')->references('id')->on('users')->onDelete('set null');
            $table->foreign('vertreter_3')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['vertreter_1']);
            $table->dropForeign(['vertreter_2']);
            $table->dropForeign(['vertreter_3']);
            $table->dropColumn(['ho_anzahl', 'vertreter_1', 'vertreter_2', 'vertreter_3', 'urlaubswunsch_sperre']);
        });
    }
};
