<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\User;
use App\Models\EventType;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('is_active', true)->get();
        $eventTypes = EventType::all();

        foreach ($users as $user) {
            // Ereignisse für die aktuelle Woche erstellen
            $startOfWeek = Carbon::now()->startOfWeek();

            for ($i = 0; $i < 5; $i++) { // Montag bis Freitag
                $date = $startOfWeek->copy()->addDays($i);

                // Zufälligen Event-Typ auswählen
                $type = $eventTypes->random();

                Event::create([
                    'user_id' => $user->id,
                    'team_id' => $user->currentTeam ? $user->currentTeam->id : 1,
                    'event_type_id' => $type->id,
                    'title' => $type->name,
                    'description' => "Notiz für {$type->name} am {$date->format('d.m.Y')}",
                    'start_date' => $date,
                    'end_date' => $date,
                    'is_all_day' => true,
                    'status' => 'approved',
                    'created_by' => $user->id,
                ]);
            }

            // Einige Ereignisse für die nächste Woche erstellen
            $startOfNextWeek = Carbon::now()->startOfWeek()->addWeek();

            for ($i = 0; $i < 3; $i++) { // Montag bis Mittwoch
                $date = $startOfNextWeek->copy()->addDays($i);

                // Zufälligen Event-Typ auswählen
                $type = $eventTypes->random();

                Event::create([
                    'user_id' => $user->id,
                    'team_id' => $user->currentTeam ? $user->currentTeam->id : 1,
                    'event_type_id' => $type->id,
                    'title' => $type->name,
                    'description' => "Notiz für {$type->name} am {$date->format('d.m.Y')}",
                    'start_date' => $date,
                    'end_date' => $date,
                    'is_all_day' => true,
                    'status' => 'approved',
                    'created_by' => $user->id,
                ]);
            }
        }
    }
}

