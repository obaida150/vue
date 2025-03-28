<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\VacationRequest;
use App\Models\User;
use App\Models\VacationBalance;
use Carbon\Carbon;

class VacationRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('is_active', true)->get();
        $currentYear = Carbon::now()->year;

        foreach ($users as $user) {
            // Genehmigte Urlaubsanträge
            $startDate = Carbon::now()->addDays(30);
            $endDate = Carbon::now()->addDays(35);
            $days = 5;

            VacationRequest::create([
                'user_id' => $user->id,
                'team_id' => $user->currentTeam ? $user->currentTeam->id : 1,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $days,
                'substitute_id' => $this->getRandomSubstitute($user->id),
                'notes' => 'Sommerurlaub',
                'status' => 'approved',
                'approved_by' => 1, // Admin
                'approved_date' => Carbon::now()->subDays(5),
            ]);

            // Aktualisiere den Urlaubssaldo
            $this->updateVacationBalance($user->id, $currentYear, $days);

            // Abgelehnte Urlaubsanträge
            VacationRequest::create([
                'user_id' => $user->id,
                'team_id' => $user->currentTeam ? $user->currentTeam->id : 1,
                'start_date' => Carbon::now()->addDays(10),
                'end_date' => Carbon::now()->addDays(12),
                'days' => 3,
                'substitute_id' => $this->getRandomSubstitute($user->id),
                'notes' => 'Kurzurlaub',
                'status' => 'rejected',
                'rejected_by' => 1, // Admin
                'rejected_date' => Carbon::now()->subDays(2),
                'rejection_reason' => 'Personalmangel in diesem Zeitraum',
            ]);

            // Ausstehende Urlaubsanträge
            VacationRequest::create([
                'user_id' => $user->id,
                'team_id' => $user->currentTeam ? $user->currentTeam->id : 1,
                'start_date' => Carbon::now()->addDays(60),
                'end_date' => Carbon::now()->addDays(70),
                'days' => 10,
                'substitute_id' => $this->getRandomSubstitute($user->id),
                'notes' => 'Winterurlaub',
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Get a random substitute user ID.
     */
    private function getRandomSubstitute($userId)
    {
        $substitute = User::where('id', '!=', $userId)
            ->where('is_active', true)
            ->inRandomOrder()
            ->first();
        return $substitute ? $substitute->id : null;
    }

    /**
     * Update the vacation balance for a user.
     */
    private function updateVacationBalance($userId, $year, $days)
    {
        $balance = VacationBalance::firstOrCreate(
            ['user_id' => $userId, 'year' => $year],
            ['total_days' => 30, 'used_days' => 0]
        );

        $balance->used_days += $days;
        $balance->save();
    }
}

