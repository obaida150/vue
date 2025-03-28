<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Team;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin-Benutzer erstellen
        $admin = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', 'Admin')->first()->id,
            'is_active' => true,
            'initials' => 'AU',
            'entry_date' => Carbon::now()->subYears(2),
            'vacation_days_per_year' => 30,
        ]);

        // Persönliches Team für Admin erstellen
        $adminTeam = Team::forceCreate([
            'user_id' => $admin->id,
            'name' => 'Admin\'s Team',
            'personal_team' => true,
        ]);

        // Admin zum Entwicklungsteam hinzufügen
        $devTeam = Team::where('name', 'Entwicklung')->first();
        $admin->teams()->attach($devTeam, ['role' => 'admin']);
        $admin->switchTeam($devTeam);

        // Weitere Benutzer erstellen
        $users = [
            [
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
                'email' => 'max@example.com',
                'password' => Hash::make('password'),
                'team' => 'Entwicklung',
                'role' => 'Mitarbeiter',
                'is_active' => true,
                'entry_date' => Carbon::now()->subYears(1),
            ],
            [
                'first_name' => 'Anna',
                'last_name' => 'Schmidt',
                'email' => 'anna@example.com',
                'password' => Hash::make('password'),
                'team' => 'Marketing',
                'role' => 'Abteilungsleiter',
                'is_active' => true,
                'entry_date' => Carbon::now()->subMonths(18),
            ],
            [
                'first_name' => 'Thomas',
                'last_name' => 'Müller',
                'email' => 'thomas@example.com',
                'password' => Hash::make('password'),
                'team' => 'Vertrieb',
                'role' => 'Mitarbeiter',
                'is_active' => true,
                'entry_date' => Carbon::now()->subMonths(8),
            ],
            [
                'first_name' => 'Julia',
                'last_name' => 'Weber',
                'email' => 'julia@example.com',
                'password' => Hash::make('password'),
                'team' => 'Personal',
                'role' => 'Personal',
                'is_active' => true,
                'entry_date' => Carbon::now()->subYears(3),
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Fischer',
                'email' => 'sarah@example.com',
                'password' => Hash::make('password'),
                'team' => 'Finanzen',
                'role' => 'Mitarbeiter',
                'is_active' => false,
                'entry_date' => Carbon::now()->subMonths(4),
            ],
        ];

        foreach ($users as $userData) {
            $user = User::create([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'name' => $userData['first_name'] . ' ' . $userData['last_name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
                'role_id' => Role::where('name', $userData['role'])->first()->id,
                'is_active' => $userData['is_active'],
                'initials' => strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)),
                'entry_date' => $userData['entry_date'],
                'vacation_days_per_year' => 30,
            ]);

            // Persönliches Team erstellen
            $personalTeam = Team::forceCreate([
                'user_id' => $user->id,
                'name' => $user->name . '\'s Team',
                'personal_team' => true,
            ]);

            // Benutzer zum entsprechenden Team hinzufügen
            $team = Team::where('name', $userData['team'])->first();
            $user->teams()->attach($team, ['role' => 'member']);
            $user->switchTeam($team);
        }
    }
}

