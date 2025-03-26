<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Team;
use App\Models\Event;
use App\Models\EventType;

class CalendarController extends Controller
{
    /**
     * Get company calendar data
     */
    public function getCompanyData()
    {
        try {
            // Mitarbeiter mit ihren Teams (Abteilungen) laden
            $employees = User::with(['currentTeam', 'events.eventType'])->get()->map(function ($user) {
                // Ereignisse für den Mitarbeiter mappen
                $events = $user->events->map(function ($event) {
                    return [
                        'date' => $event->start_date->format('Y-m-d'),
                        'type' => [
                            'name' => $event->eventType->name,
                            'value' => strtolower($event->eventType->name),
                            'color' => $event->eventType->color
                        ],
                        'notes' => $event->description
                    ];
                });

                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'department' => $user->currentTeam ? $user->currentTeam->name : 'Keine Abteilung',
                    'events' => $events
                ];
            });

            // Teams (Abteilungen) laden
            $departments = Team::where('personal_team', false)->get()->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name
                ];
            });

            // Event-Typen laden
            $eventTypes = EventType::all()->map(function ($type) {
                return [
                    'name' => $type->name,
                    'value' => strtolower($type->name),
                    'color' => $type->color
                ];
            });

            return response()->json([
                'employees' => $employees,
                'departments' => $departments,
                'eventTypes' => $eventTypes
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

