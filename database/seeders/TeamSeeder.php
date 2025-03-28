<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [
            ['name' => 'Entwicklung'],
            ['name' => 'Marketing'],
            ['name' => 'Vertrieb'],
            ['name' => 'Personal'],
            ['name' => 'Finanzen'],
        ];

        // Erstelle einen temporären Admin-Benutzer, um Teams zu erstellen
        $admin = User::create([
            'first_name' => 'Temp',
            'last_name' => 'Admin',
            'name' => 'Temp Admin',
            'email' => 'temp@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        foreach ($teams as $teamData) {
            $team = Team::create([
                'user_id' => $admin->id,
                'name' => $teamData['name'],
                'personal_team' => false,
            ]);
        }

        // Lösche den temporären Admin-Benutzer wieder
        $admin->delete();
    }
}

