<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Team;
use App\Models\EventType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\VacationRequest;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    /**
     * Get company calendar data including birthdays
     */
    public function getCompanyData()
    {
        try {
            // Debug-Logging
            Log::info('CalendarController::getCompanyData wurde aufgerufen');

            // Mitarbeiter mit ihren Teams (Abteilungen) laden
            $employees = User::with(['currentTeam', 'events.eventType'])
                ->where('is_active', true)
                ->get();

            Log::info('Mitarbeiter geladen: ' . $employees->count());

            $mappedEmployees = $employees->map(function ($user) {
                // Ereignisse für den Mitarbeiter mappen
                $events = [];

                if ($user->events && $user->events->isNotEmpty()) {
                    $events = $user->events->map(function ($event) {
                        // Prüfen, ob eventType existiert
                        if (!$event->eventType) {
                            return null;
                        }

                        return [
                            'date' => $event->start_date->format('Y-m-d'),
                            'start_date' => $event->start_date->format('Y-m-d'),
                            'end_date' => $event->end_date->format('Y-m-d'),
                            'type' => [
                                'name' => $event->eventType->name,
                                'value' => strtolower($event->eventType->name),
                                'color' => $event->eventType->color
                            ],
                            'notes' => $event->description
                        ];
                    })->filter()->toArray();
                }

                // Geburtstag als Ereignis hinzufügen, wenn vorhanden
                if ($user->birth_date) {
                    $currentYear = Carbon::now()->year;
                    $birthdayThisYear = Carbon::createFromDate(
                        $currentYear,
                        $user->birth_date->month,
                        $user->birth_date->day
                    );

                    // Wenn der Geburtstag dieses Jahr bereits vorbei ist, zeige auch den nächsten an
                    if ($birthdayThisYear->isPast()) {
                        $nextYear = $currentYear + 1;
                        $birthdayNextYear = Carbon::createFromDate(
                            $nextYear,
                            $user->birth_date->month,
                            $user->birth_date->day
                        );

                        $events[] = [
                            'date' => $birthdayNextYear->format('Y-m-d'),
                            'start_date' => $birthdayNextYear->format('Y-m-d'),
                            'end_date' => $birthdayNextYear->format('Y-m-d'),
                            'type' => [
                                'name' => 'Geburtstag',
                                'value' => 'birthday',
                                'color' => '#FF4500' // Orange-Rot für Geburtstage
                            ],
                            'notes' => $user->full_name . ' hat Geburtstag! (' . ($user->birth_date->age + 1) . ' Jahre)'
                        ];
                    }

                    // Aktuelles Jahr Geburtstag
                    $events[] = [
                        'date' => $birthdayThisYear->format('Y-m-d'),
                        'start_date' => $birthdayThisYear->format('Y-m-d'),
                        'end_date' => $birthdayThisYear->format('Y-m-d'),
                        'type' => [
                            'name' => 'Geburtstag',
                            'value' => 'birthday',
                            'color' => '#FF4500' // Orange-Rot für Geburtstage
                        ],
                        'notes' => $user->full_name . ' hat Geburtstag! (' . $user->birth_date->age . ' Jahre)'
                    ];
                }

                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'department' => $user->currentTeam ? $user->currentTeam->name : 'Keine Abteilung',
                    'events' => $events,
                    'birth_date' => $user->birth_date ? $user->birth_date->format('Y-m-d') : null
                ];
            });

            // Teams (Abteilungen) laden
            $departments = Team::where('personal_team', false)->get()->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name
                ];
            });

            // Event-Typen laden und Geburtstag hinzufügen
            $eventTypes = [];
            try {
                $eventTypes = EventType::all()->map(function ($type) {
                    return [
                        'name' => $type->name,
                        'value' => strtolower($type->name),
                        'color' => $type->color
                    ];
                })->toArray();
            } catch (\Exception $e) {
                Log::error('Fehler beim Laden der EventTypes: ' . $e->getMessage());
                // Fallback für EventTypes
                $eventTypes = [
                    ['name' => 'Homeoffice', 'value' => 'homeoffice', 'color' => '#4CAF50'],
                    ['name' => 'Büro', 'value' => 'office', 'color' => '#2196F3'],
                    ['name' => 'Außendienst', 'value' => 'field', 'color' => '#FF9800'],
                    ['name' => 'Krank', 'value' => 'sick', 'color' => '#F44336'],
                    ['name' => 'Urlaub', 'value' => 'vacation', 'color' => '#9C27B0'],
                    ['name' => 'Sonstiges', 'value' => 'other', 'color' => '#607D8B']
                ];
            }

            // Geburtstags-Eventtyp hinzufügen, falls noch nicht vorhanden
            $hasBirthdayType = collect($eventTypes)->contains('value', 'birthday');
            if (!$hasBirthdayType) {
                $eventTypes[] = [
                    'name' => 'Geburtstag',
                    'value' => 'birthday',
                    'color' => '#FF4500'
                ];
            }

            // Urlaubsanträge laden
            try {
                $vacationRequests = VacationRequest::with(['user', 'user.currentTeam'])
                    ->where('status', 'approved')
                    ->get()
                    ->map(function ($request) {
                        // Berechne die tatsächlichen Arbeitstage (Mo-Fr) im Urlaubszeitraum
                        $startDate = Carbon::parse($request->start_date);
                        $endDate = Carbon::parse($request->end_date);
                        $workDays = [];

                        $currentDate = $startDate->copy();
                        while ($currentDate->lte($endDate)) {
                            // Nur Werktage (Mo-Fr) hinzufügen
                            $dayOfWeek = $currentDate->dayOfWeek;
                            if ($dayOfWeek !== 0 && $dayOfWeek !== 6) { // Nicht Sonntag und nicht Samstag
                                $workDays[] = $currentDate->format('Y-m-d');
                            }
                            $currentDate->addDay();
                        }

                        return [
                            'id' => $request->id,
                            'user_id' => $request->user_id,
                            'start_date' => $request->start_date->format('Y-m-d'),
                            'end_date' => $request->end_date->format('Y-m-d'),
                            'work_days' => $workDays, // Liste der tatsächlichen Arbeitstage
                            'days' => $request->days,
                            'notes' => $request->notes,
                            'status' => $request->status,
                            'employee_name' => $request->user ? $request->user->full_name : 'Unbekannt',
                            'department' => $request->user && $request->user->currentTeam ? $request->user->currentTeam->name : 'Keine Abteilung'
                        ];
                    });

                // Für jeden Urlaubsantrag die Werktage berechnen und als separate Ereignisse hinzufügen
                foreach ($vacationRequests as $request) {
                    // Finde den entsprechenden Mitarbeiter
                    $employee = $mappedEmployees->firstWhere('id', $request['user_id']);
                    if ($employee) {
                        foreach ($request['work_days'] as $workDay) {
                            $employee['events'][] = [
                                'date' => $workDay,
                                'start_date' => $workDay,
                                'end_date' => $workDay,
                                'type' => [
                                    'name' => 'Urlaub',
                                    'value' => 'vacation',
                                    'color' => '#9C27B0'
                                ],
                                'notes' => $request['notes'] ?: 'Genehmigter Urlaub'
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Fehler beim Laden der Urlaubsanträge: ' . $e->getMessage());
            }

            return response()->json([
                'employees' => $mappedEmployees,
                'departments' => $departments,
                'eventTypes' => $eventTypes,
                'currentUserTeamId' => Auth::user()->currentTeam ? Auth::user()->currentTeam->id : null
            ]);
        } catch (\Exception $e) {
            Log::error('Fehler im CalendarController: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get the current user's role ID
     */
    public function getUserRole()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            return response()->json([
                'role_id' => $user->role_id,
                'user_id' => $user->id,
                'role_name' => $user->role ? $user->role->name : null
            ]);
        } catch (\Exception $e) {
            Log::error('Fehler beim Abrufen der Benutzerrolle: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all active employees
     */
    public function getEmployees()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Prüfen, ob der Benutzer die HR-Rolle hat
            if ($user->role_id !== 2) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Alle aktiven Mitarbeiter laden
            $employees = User::where('is_active', true)
                ->select('id', 'first_name', 'last_name')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name
                    ];
                });

            return response()->json($employees);
        } catch (\Exception $e) {
            Log::error('Fehler beim Abrufen der Mitarbeiterliste: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
