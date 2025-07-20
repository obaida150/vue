<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // Stellen Sie sicher, dass dies Ihr User-Modell ist
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\Team;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin-Benutzer erstellen oder finden
        $adminRole = \App\Models\Role::where('name', 'Admin')->first();
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'role_id' => $adminRole ? $adminRole->id : null, // Stellen Sie sicher, dass die Rolle existiert
                'is_active' => true,
                'initials' => 'AU',
                'entry_date' => Carbon::now()->subYears(2),
                'vacation_days_per_year' => 30,
            ]
        );
        // Persönliches Team für Admin erstellen oder finden
        Team::firstOrCreate(
            ['user_id' => $admin->id, 'personal_team' => true],
            [
                'name' => 'Admin\'s Team',
            ]
        );
        // Admin zum Entwicklungsteam hinzufügen (falls noch nicht geschehen)
        $devTeam = Team::where('name', 'Entwicklung')->first();
        if ($devTeam && !$admin->teams->contains($devTeam->id)) {
            $admin->teams()->attach($devTeam, ['role' => 'admin']);
        }
        $admin->switchTeam($devTeam);
        $admin->assignRole('Admin'); // Spatie-Rolle zuweisen

        // Weitere Benutzer erstellen oder finden
        $usersData = [
            [
                'first_name' => 'Max',
                'last_name' => 'Mustermann',
                'email' => 'max@example.com',
                'password' => Hash::make('password'),
                'team_name' => 'Entwicklung',
                'role_name' => 'Mitarbeiter',
                'is_active' => true,
                'entry_date' => Carbon::now()->subYears(1),
            ],
            [
                'first_name' => 'Anna',
                'last_name' => 'Schmidt',
                'email' => 'anna@example.com',
                'password' => Hash::make('password'),
                'team_name' => 'Marketing',
                'role_name' => 'Abteilungsleiter',
                'is_active' => true,
                'entry_date' => Carbon::now()->subMonths(18),
            ],
            [
                'first_name' => 'Thomas',
                'last_name' => 'Müller',
                'email' => 'thomas@example.com',
                'password' => Hash::make('password'),
                'team_name' => 'Vertrieb',
                'role_name' => 'Mitarbeiter',
                'is_active' => true,
                'entry_date' => Carbon::now()->subMonths(8),
            ],
            [
                'first_name' => 'Julia',
                'last_name' => 'Weber',
                'email' => 'julia@example.com',
                'password' => Hash::make('password'),
                'team_name' => 'Personal',
                'role_name' => 'Personal',
                'is_active' => true,
                'entry_date' => Carbon::now()->subYears(3),
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Fischer',
                'email' => 'sarah@example.com',
                'password' => Hash::make('password'),
                'team_name' => 'Finanzen',
                'role_name' => 'Mitarbeiter',
                'is_active' => false,
                'entry_date' => Carbon::now()->subMonths(4),
            ],
        ];

        foreach ($usersData as $userData) {
            $role = \App\Models\Role::where('name', $userData['role_name'])->first();
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'name' => $userData['first_name'] . ' ' . $userData['last_name'],
                    'password' => $userData['password'],
                    'role_id' => $role ? $role->id : null,
                    'is_active' => $userData['is_active'],
                    'initials' => strtoupper(substr($userData['first_name'], 0, 1) . substr($userData['last_name'], 0, 1)),
                    'entry_date' => $userData['entry_date'],
                    'vacation_days_per_year' => 30,
                ]
            );

            // Persönliches Team erstellen oder finden
            Team::firstOrCreate(
                ['user_id' => $user->id, 'personal_team' => true],
                [
                    'name' => $user->name . '\'s Team',
                ]
            );

            // Benutzer zum entsprechenden Team hinzufügen (falls noch nicht geschehen)
            $team = Team::where('name', $userData['team_name'])->first();
            if ($team && !$user->teams->contains($team->id)) {
                $user->teams()->attach($team, ['role' => 'member']);
            }
            $user->switchTeam($team);
            $user->assignRole($userData['role_name']); // Spatie-Rolle zuweisen
        }
    }
}
