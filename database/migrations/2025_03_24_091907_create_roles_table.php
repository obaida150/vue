<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Einfügen der Standard-Rollen
        DB::table('roles')->insert([
            ['name' => 'Admin', 'description' => 'Vollständige Administrationsrechte für das System', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Personal', 'description' => 'Personalverwaltung und Mitarbeiterbetreuung', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Abteilungsleiter', 'description' => 'Leitung einer Abteilung und Teammanagement', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mitarbeiter', 'description' => 'Standardbenutzer ohne erweiterte Rechte', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
