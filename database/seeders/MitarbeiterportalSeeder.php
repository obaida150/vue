<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Team;
use App\Models\Role;
use App\Models\HomeofficeRule;
use App\Models\VacationBalance;
use App\Models\VacationRequest;
use App\Models\Event;
use Laravel\Jetstream\Jetstream;

class MitarbeiterportalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Erstelle Admin-Benutzer
        $admin = User::create([
            'first_name' => 'Max',
            'last_name' => 'Mustermann',
            'name' => 'Max Mustermann',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'birth_date' => '1980-01-15',
            'role_id' => 1, // Admin
            'vacation_days_per_year' => 30,
            'initials' => 'MM',
            'entry_date' => '2010-01-01',
            'employee_number' => 'EMP001',
            'email_verified_at' => now(),
        ]);

        // Erstelle Teams (Abteilungen)
        $itTeam = $admin->ownedTeams()->create([
            'name' => 'IT',
            'personal_team' => false,
        ]);

        $marketingTeam = $admin->ownedTeams()->create([
            'name' => 'Marketing',
            'personal_team' => false,
        ]);

        $vertriebTeam = $admin->ownedTeams()->create([
            'name' => 'Vertrieb',
            'personal_team' => false,
        ]);

        $personalTeam = $admin->ownedTeams()->create([
            'name' => 'Personal',
            'personal_team' => false,
        ]);

        $finanzenTeam = $admin->ownedTeams()->create([
            'name' => 'Finanzen',
            'personal_team' => false,
        ]);

        // Admin zu allen Teams hinzufügen
        foreach ([$itTeam, $marketingTeam, $vertriebTeam, $personalTeam, $finanzenTeam] as $team) {
            $admin->teams()->attach($team, ['role' => 'admin']);
        }

        // Erstelle Personal-Mitarbeiter
        $personal = User::create([
            'first_name' => 'Anna',
            'last_name' => 'Schmidt',
            'name' => 'Anna Schmidt',
            'email' => 'personal@example.com',
            'password' => Hash::make('password'),
            'birth_date' => '1985-05-20',
            'role_id' => 2, // Personal
            'vacation_days_per_year' => 30,
            'initials' => 'AS',
            'entry_date' => '2012-03-15',
            'employee_number' => 'EMP002',
            'email_verified_at' => now(),
        ]);

        // Personal zum Personal-Team hinzufügen
        $personal->teams()->attach($personalTeam, ['role' => 'admin']);
        // Personal zu allen anderen Teams hinzufügen für Zugriff
        foreach ([$itTeam, $marketingTeam, $vertriebTeam, $finanzenTeam] as $team) {
            $personal->teams()->attach($team, ['role' => 'editor']);
        }

        // Erstelle Abteilungsleiter
        $itLeiter = User::create([
            'first_name' => 'Thomas',
            'last_name' => 'Müller',
            'name' => 'Thomas Müller',
            'email' => 'it-leiter@example.com',
            'password' => Hash::make('password'),
            'birth_date' => '1978-11-10',
            'role_id' => 3, // Abteilungsleiter
            'vacation_days_per_year' => 30,
            'initials' => 'TM',
            'entry_date' => '2011-06-01',
            'employee_number' => 'EMP003',
            'email_verified_at' => now(),
        ]);

        $marketingLeiter = User::create([
            'first_name' => 'Julia',
            'last_name' => 'Weber',
            'name' => 'Julia Weber',
            'email' => 'marketing-leiter@example.com',
            'password' => Hash::make('password'),
            'birth_date' => '1982-07-25',
            'role_id' => 3, // Abteilungsleiter
            'vacation_days_per_year' => 30,
            'initials' => 'JW',
            'entry_date' => '2013-09-01',
            'employee_number' => 'EMP004',
            'email_verified_at' => now(),
        ]);

        $vertriebLeiter = User::create([
            'first_name' => 'Michael',
            'last_name' => 'Fischer',
            'name' => 'Michael Fischer',
            'email' => 'vertrieb-leiter@example.com',
            'password' => Hash::make('password'),
            'birth_date' => '1975-03-30',
            'role_id' => 3, // Abteilungsleiter
            'vacation_days_per_year' => 30,
            'initials' => 'MF',
            'entry_date' => '2010-11-15',
            'employee_number' => 'EMP005',
            'email_verified_at' => now(),
        ]);

        // Abteilungsleiter zu ihren Teams hinzufügen
        $itLeiter->teams()->attach($itTeam, ['role' => 'admin']);
        $marketingLeiter->teams()->attach($marketingTeam, ['role' => 'admin']);
        $vertriebLeiter->teams()->attach($vertriebTeam, ['role' => 'admin']);

        // Erstelle Mitarbeiter
        $itMitarbeiter = User::create([
            'first_name' => 'Sarah',
            'last_name' => 'Becker',
            'name' => 'Sarah Becker',
            'email' => 'it-mitarbeiter@example.com',
            'password' => Hash::make('password'),
            'birth_date' => '1990-09-12',
            'role_id' => 4, // Mitarbeiter
            'vacation_days_per_year' => 30,
            'initials' => 'SB',
            'entry_date' => '2015-02-01',
            'employee_number' => 'EMP006',
            'email_verified_at' => now(),
        ]);

        $marketingMitarbeiter = User::create([
            'first_name' => 'Markus',
            'last_name' => 'Klein',
            'name' => 'Markus Klein',
            'email' => 'marketing-mitarbeiter@example.com',
            'password' => Hash::make('password'),
            'birth_date' => '1988-12-05',
            'role_id' => 4, // Mitarbeiter
            'vacation_days_per_year' => 30,
            'initials' => 'MK',
            'entry_date' => '2016-05-15',
            'employee_number' => 'EMP007',
            'email_verified_at' => now(),
        ]);

        $vertriebMitarbeiter = User::create([
            'first_name' => 'Laura',
            'last_name' => 'Schulz',
            'name' => 'Laura Schulz',
            'email' => 'vertrieb-mitarbeiter@example.com',
            'password' => Hash::make('password'),
            'birth_date' => '1992-04-18',
            'role_id' => 4, // Mitarbeiter
            'vacation_days_per_year' => 30,
            'initials' => 'LS',
            'entry_date' => '2017-08-01',
            'employee_number' => 'EMP008',
            'email_verified_at' => now(),
        ]);

        // Mitarbeiter zu ihren Teams hinzufügen
        $itMitarbeiter->teams()->attach($itTeam, ['role' => 'editor']);
        $marketingMitarbeiter->teams()->attach($marketingTeam, ['role' => 'editor']);
        $vertriebMitarbeiter->teams()->attach($vertriebTeam, ['role' => 'editor']);

        // Homeoffice-Regeln erstellen
        HomeofficeRule::create([
            'team_id' => $itTeam->id,
            'max_days_per_week' => 3,
            'description' => 'IT-Abteilung erlaubt maximal 3 Tage Homeoffice pro Woche',
            'created_by' => $itLeiter->id,
        ]);

        HomeofficeRule::create([
            'team_id' => $marketingTeam->id,
            'max_days_per_week' => 2,
            'description' => 'Marketing-Abteilung erlaubt maximal 2 Tage Homeoffice pro Woche',
            'created_by' => $marketingLeiter->id,
        ]);

        HomeofficeRule::create([
            'team_id' => $vertriebTeam->id,
            'max_days_per_week' => 1,
            'description' => 'Vertrieb-Abteilung erlaubt maximal 1 Tag Homeoffice pro Woche',
            'created_by' => $vertriebLeiter->id,
        ]);

        HomeofficeRule::create([
            'team_id' => $personalTeam->id,
            'max_days_per_week' => 2,
            'description' => 'Personal-Abteilung erlaubt maximal 2 Tage Homeoffice pro Woche',
            'created_by' => $personal->id,
        ]);

        // Urlaubsguthaben für 2025
        $users = User::all();
        foreach ($users as $user) {
            VacationBalance::create([
                'user_id' => $user->id,
                'year' => 2025,
                'total_days' => 30,
                'used_days' => rand(0, 15),
            ]);
        }

        // Urlaubsanträge
        VacationRequest::create([
            'user_id' => $itMitarbeiter->id,
            'team_id' => $itTeam->id,
            'start_date' => '2025-07-15',
            'end_date' => '2025-07-25',
            'days' => 9,
            'substitute_id' => $itLeiter->id,
            'notes' => 'Sommerurlaub',
            'status' => 'approved',
            'approved_by' => $itLeiter->id,
            'approved_date' => '2025-06-01 10:30:00',
        ]);

        VacationRequest::create([
            'user_id' => $marketingMitarbeiter->id,
            'team_id' => $marketingTeam->id,
            'start_date' => '2025-08-10',
            'end_date' => '2025-08-20',
            'days' => 9,
            'substitute_id' => $marketingLeiter->id,
            'notes' => 'Familienurlaub',
            'status' => 'approved',
            'approved_by' => $marketingLeiter->id,
            'approved_date' => '2025-07-05 14:15:00',
        ]);

        VacationRequest::create([
            'user_id' => $vertriebMitarbeiter->id,
            'team_id' => $vertriebTeam->id,
            'start_date' => '2025-09-05',
            'end_date' => '2025-09-15',
            'days' => 7,
            'substitute_id' => $vertriebLeiter->id,
            'notes' => 'Erholungsurlaub',
            'status' => 'pending',
        ]);

        // Kalendereinträge
        Event::create([
            'user_id' => $itMitarbeiter->id,
            'team_id' => $itTeam->id,
            'event_type_id' => 1, // Homeoffice
            'title' => 'Homeoffice',
            'description' => 'Arbeit von zu Hause',
            'start_date' => '2025-06-10',
            'end_date' => '2025-06-10',
            'created_by' => $itMitarbeiter->id,
        ]);

        Event::create([
            'user_id' => $marketingMitarbeiter->id,
            'team_id' => $marketingTeam->id,
            'event_type_id' => 1, // Homeoffice
            'title' => 'Homeoffice',
            'description' => 'Arbeit von zu Hause',
            'start_date' => '2025-06-10',
            'end_date' => '2025-06-10',
            'created_by' => $marketingMitarbeiter->id,
        ]);

        Event::create([
            'user_id' => $vertriebMitarbeiter->id,
            'team_id' => $vertriebTeam->id,
            'event_type_id' => 3, // Außendienst
            'title' => 'Außendienst',
            'description' => 'Kundenbesuch in München',
            'start_date' => '2025-06-12',
            'end_date' => '2025-06-13',
            'created_by' => $vertriebMitarbeiter->id,
        ]);
    }
}
