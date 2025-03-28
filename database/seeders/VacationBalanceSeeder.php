<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VacationBalance;
use App\Models\User;
use Carbon\Carbon;

class VacationBalanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $currentYear = Carbon::now()->year;

        foreach ($users as $user) {
            // Erstelle Urlaubssaldo für das aktuelle Jahr, falls noch nicht vorhanden
            VacationBalance::firstOrCreate(
                ['user_id' => $user->id, 'year' => $currentYear],
                [
                    'total_days' => $user->vacation_days_per_year,
                    'used_days' => 0
                ]
            );

            // Erstelle Urlaubssaldo für das letzte Jahr
            VacationBalance::firstOrCreate(
                ['user_id' => $user->id, 'year' => $currentYear - 1],
                [
                    'total_days' => $user->vacation_days_per_year,
                    'used_days' => rand(10, $user->vacation_days_per_year)
                ]
            );
        }
    }
}

