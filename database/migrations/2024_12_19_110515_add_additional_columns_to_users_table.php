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
        Schema::table('users', function (Blueprint $table) {
            $table->string('vorname', 255)->nullable()->after('name');
            $table->string('kuerzel', 255)->nullable()->after('vorname');
            $table->boolean('ist_aktiv')->default(true)->after('kuerzel');
            $table->boolean('ist_personal')->default(false)->after('ist_aktiv');
            $table->boolean('ist_admin')->default(false)->after('ist_personal');
            $table->timestamp('login_date')->nullable()->after('ist_admin');
            $table->double('urlaub_anzahl', 8, 2)->nullable()->after('login_date');
            $table->date('geburtsdatum')->nullable()->after('urlaub_anzahl');
            $table->date('diensteintritt')->nullable()->after('geburtsdatum');
            $table->string('color', 255)->nullable()->after('diensteintritt');
            $table->string('pers_nr', 255)->nullable()->after('color');
            $table->boolean('is_ausbilder')->default(false)->after('pers_nr');
            $table->boolean('show_birthday_color')->default(true)->after('is_ausbilder');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'vorname',
                'kuerzel',
                'ist_aktiv',
                'ist_personal',
                'ist_admin',
                'login_date',
                'urlaub_anzahl',
                'geburtsdatum',
                'diensteintritt',
                'color',
                'pers_nr',
                'is_ausbilder',
                'show_birthday_color',
            ]);
        });
    }
};
