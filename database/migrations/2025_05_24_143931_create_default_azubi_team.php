<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class CreateDefaultAzubiTeam extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Prüfen ob das Azubi-Team bereits existiert
        $azubiTeamExists = DB::table('teams')->where('name', 'Azubis')->where('personal_team', false)->exists();

        if (!$azubiTeamExists) {
            // Finde einen Admin oder HR-Benutzer als Team-Owner
            $adminUser = User::whereHas('role', function($query) {
                $query->whereIn('name', ['Admin', 'HR', 'Personal']);
            })->first();

            // Falls kein Admin gefunden wird, nimm den ersten aktiven Benutzer
            if (!$adminUser) {
                $adminUser = User::where('is_active', true)->first();
            }

            // Falls immer noch kein Benutzer gefunden wird, erstelle einen System-Benutzer
            if (!$adminUser) {
                // Erstelle einen System-Benutzer für das Team
                $adminUser = User::create([
                    'first_name' => 'System',
                    'last_name' => 'Administrator',
                    'name' => 'System Administrator',
                    'email' => 'system@' . (config('app.url') ? parse_url(config('app.url'), PHP_URL_HOST) : 'company.com'),
                    'password' => bcrypt('system-password-' . time()),
                    'role_id' => 1, // Annahme: Admin-Rolle hat ID 1
                    'is_active' => true,
                    'vacation_days_per_year' => 0,
                    'current_team_id' => 1 // Wird später aktualisiert
                ]);
            }

            // Erstelle das Azubi-Team
            $azubiTeam = DB::table('teams')->insertGetId([
                'name' => 'Azubis',
                'personal_team' => false,
                'user_id' => $adminUser->id,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Aktualisiere den System-Benutzer falls er erstellt wurde
            if ($adminUser->name === 'System Administrator') {
                $adminUser->update(['current_team_id' => $azubiTeam]);
            }

            // Füge den Admin-Benutzer zum Team hinzu
            DB::table('team_user')->insert([
                'team_id' => $azubiTeam,
                'user_id' => $adminUser->id,
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            echo "Azubi-Team erstellt mit ID: {$azubiTeam}\n";
        } else {
            echo "Azubi-Team existiert bereits\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Lösche das Azubi-Team
        $azubiTeam = DB::table('teams')->where('name', 'Azubis')->where('personal_team', false)->first();

        if ($azubiTeam) {
            // Lösche zuerst die Team-Benutzer-Zuordnungen
            DB::table('team_user')->where('team_id', $azubiTeam->id)->delete();

            // Lösche das Team
            DB::table('teams')->where('id', $azubiTeam->id)->delete();

            echo "Azubi-Team gelöscht\n";
        }
    }
}
