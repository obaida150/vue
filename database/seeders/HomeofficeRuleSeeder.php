<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HomeofficeRule;
use App\Models\Team;

class HomeofficeRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = Team::where('personal_team', false)->get();

        foreach ($teams as $team) {
            HomeofficeRule::create([
                'team_id' => $team->id,
                'max_days_per_week' => rand(2, 5),
                'description' => "Homeoffice-Regelung für {$team->name}",
                'created_by' => 1, // Admin
            ]);
        }
    }
}

