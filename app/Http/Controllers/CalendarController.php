<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Team;
use App\Models\EventType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\VacationRequest;
use Illuminate\Support\Facades\DB;

class CalendarController extends Controller
{
    /**
     * Get company calendar data including birthdays
     */
    public function getCompanyData()
    {
        try {
            $employees = User::with(['currentTeam', 'events.eventType'])
                ->where('is_active', true)
                ->get();

            $mappedEmployees = $employees->map(function ($user) {
                $events = [];

                if ($user->events && $user->events->isNotEmpty()) {
                    $events = $user->events->map(function ($event) {
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

                if ($user->birth_date) {
                    $currentYear = Carbon::now()->year;
                    $birthdayThisYear = Carbon::createFromDate(
                        $currentYear,
                        $user->birth_date->month,
                        $user->birth_date->day
                    );

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
                                'color' => '#FF4500'
                            ],
                            'notes' => $user->full_name . ' hat Geburtstag! '
                        ];
                    }

                    $events[] = [
                        'date' => $birthdayThisYear->format('Y-m-d'),
                        'start_date' => $birthdayThisYear->format('Y-m-d'),
                        'end_date' => $birthdayThisYear->format('Y-m-d'),
                        'type' => [
                            'name' => 'Geburtstag',
                            'value' => 'birthday',
                            'color' => '#FF4500'
                        ],
                        'notes' => $user->full_name . ' hat Geburtstag! '
                    ];
                }

                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'department' => $user->currentTeam ? $user->currentTeam->name : 'Keine Abteilung',
                    'team_id' => $user->current_team_id,
                    'initials' => $user->initials,
                    'events' => $events,
                    'birth_date' => $user->birth_date ? $user->birth_date->format('Y-m-d') : null
                ];
            });

            $departments = Team::where('personal_team', false)->get()->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name
                ];
            });

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
                $eventTypes = [
                    ['name' => 'Homeoffice', 'value' => 'homeoffice', 'color' => '#4CAF50'],
                    ['name' => 'Büro', 'value' => 'office', 'color' => '#2196F3'],
                    ['name' => 'Außendienst', 'value' => 'field', 'color' => '#FF9800'],
                    ['name' => 'Krank', 'value' => 'sick', 'color' => '#F44336'],
                    ['name' => 'Urlaub', 'value' => 'vacation', 'color' => '#9C27B0'],
                    ['name' => 'Sonstiges', 'value' => 'other', 'color' => '#607D8B']
                ];
            }

            $hasBirthdayType = collect($eventTypes)->contains('value', 'birthday');
            if (!$hasBirthdayType) {
                $eventTypes[] = [
                    'name' => 'Geburtstag',
                    'value' => 'birthday',
                    'color' => '#FF4500'
                ];
            }

            $hasVacationType = collect($eventTypes)->contains('value', 'vacation');
            if (!$hasVacationType) {
                $eventTypes[] = [
                    'name' => 'Urlaub',
                    'value' => 'vacation',
                    'color' => '#9C27B0'
                ];
            }

            try {
                $vacationRequests = VacationRequest::with(['user', 'user.currentTeam'])
                    ->where('status', 'approved')
                    ->get()
                    ->map(function ($request) {
                        $startDate = Carbon::parse($request->start_date);
                        $endDate = Carbon::parse($request->end_date);
                        $workDays = [];

                        $currentDate = $startDate->copy();
                        while ($currentDate->lte($endDate)) {
                            $dayOfWeek = $currentDate->dayOfWeek;
                            if ($dayOfWeek !== 0 && $dayOfWeek !== 6) {
                                $workDays[] = $currentDate->format('Y-m-d');
                            }
                            $currentDate->addDay();
                        }

                        return [
                            'id' => $request->id,
                            'user_id' => $request->user_id,
                            'start_date' => $request->start_date->format('Y-m-d'),
                            'end_date' => $request->end_date->format('Y-m-d'),
                            'work_days' => $workDays,
                            'day_type' => $request->day_type,
                            'days' => $request->days,
                            'notes' => $request->notes,
                            'status' => $request->status,
                            'employee_name' => $request->user ? $request->user->full_name : 'Unbekannt',
                            'department' => $request->user && $request->user->currentTeam ? $request->user->currentTeam->name : 'Keine Abteilung'
                        ];
                    });

                foreach ($vacationRequests as $request) {
                    $employeeIndex = $mappedEmployees->search(function ($item) use ($request) {
                        return $item['id'] === $request['user_id'];
                    });

                    if ($employeeIndex !== false) {
                        foreach ($request['work_days'] as $workDay) {
                            $vacationName = 'Urlaub';
                            if (isset($request['day_type']) && $request['day_type'] === 'morning') {
                                $vacationName = 'Vormittag';
                            } elseif (isset($request['day_type']) && $request['day_type'] === 'afternoon') {
                                $vacationName = 'Nachmittag';
                            }

                            $employee = $mappedEmployees[$employeeIndex];
                            $employee['events'][] = [
                                'date' => $workDay,
                                'start_date' => $workDay,
                                'end_date' => $workDay,
                                'type' => [
                                    'name' => $vacationName,
                                    'value' => 'vacation',
                                    'color' => '#9C27B0'
                                ],
                                'notes' => $request['notes'] ?: 'Genehmigter Urlaub'
                            ];
                            $mappedEmployees[$employeeIndex] = $employee;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Urlaubsanträge konnten nicht geladen werden
            }

            return response()->json([
                'employees' => $mappedEmployees,
                'departments' => $departments,
                'eventTypes' => $eventTypes,
                'currentUserTeamId' => Auth::user()->currentTeam ? Auth::user()->currentTeam->id : null
            ]);
        } catch (\Exception $e) {
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
                return response()->json(['error' => 'Benutzer nicht authentifiziert'], 401);
            }

            $isTeamManager = false;
            $teamId = null;
            $teamMembers = [];

            if ($user->currentTeam) {
                $teamId = $user->currentTeam->id;

                $teamUserRole = DB::table('team_user')
                    ->where('team_id', $teamId)
                    ->where('user_id', $user->id)
                    ->value('role');

                $isTeamManager = ($teamUserRole === 'Abteilungsleiter');

                if ($isTeamManager) {
                    $teamMembers = User::whereHas('teams', function($query) use ($teamId) {
                        $query->where('teams.id', $teamId);
                    })
                        ->where('is_active', true)
                        ->get()
                        ->map(function($member) {
                            return [
                                'id' => $member->id,
                                'name' => $member->full_name ?? $member->name,
                                'email' => $member->email,
                            ];
                        })
                        ->toArray();
                }
            }

            return response()->json([
                'user_id' => $user->id,
                'role_id' => $user->role_id,
                'role_name' => $user->role ? $user->role->name : null,
                'is_team_manager' => $isTeamManager,
                'team_id' => $teamId,
                'team_members' => $teamMembers,
                'has_hr_permissions' => $user->has_hr_permissions ?? false,
            ]);
        } catch (\Exception $e) {
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

            $isHR = $user->role_id === 1 || $user->role_id === 2;

            $isTeamManager = false;
            $teamId = null;

            if ($user->currentTeam) {
                $teamId = $user->currentTeam->id;
                $teamUserRole = DB::table('team_user')
                    ->where('team_id', $teamId)
                    ->where('user_id', $user->id)
                    ->first();

                $isTeamManager = $teamUserRole && $teamUserRole->role === 'Abteilungsleiter';
            }

            if (!$isHR && !$isTeamManager) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $query = User::where('is_active', true);

            if ($isTeamManager && !$isHR) {
                $query->where('current_team_id', $teamId);
            }

            $employees = $query->select('id', 'first_name', 'last_name', 'current_team_id')
                ->get()
                ->map(function ($emp) {
                    return [
                        'id' => $emp->id,
                        'name' => $emp->first_name . ' ' . $emp->last_name,
                        'team_id' => $emp->current_team_id
                    ];
                });

            return response()->json($employees);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all vacation requests for calendar display
     */
    public function getAllVacationRequests()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $isHR = $user->role_id === 2;
            $isTeamManager = false;
            $teamId = null;

            if ($user->currentTeam) {
                $teamId = $user->currentTeam->id;
                $teamUserRole = DB::table('team_user')
                    ->where('team_id', $teamId)
                    ->where('user_id', $user->id)
                    ->value('role');
                $isTeamManager = ($teamUserRole === 'Abteilungsleiter');
            }

            $query = VacationRequest::with(['user', 'user.currentTeam'])
                ->where('status', 'approved');

            if ($isTeamManager && !$isHR) {
                $query->whereHas('user', function($q) use ($teamId) {
                    $q->whereHas('teams', function($teamQuery) use ($teamId) {
                        $teamQuery->where('teams.id', $teamId);
                    });
                });
            }

            $vacationRequests = $query->get()->map(function ($request) {
                $startDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);
                $workDays = [];

                $currentDate = $startDate->copy();
                while ($currentDate->lte($endDate)) {
                    $dayOfWeek = $currentDate->dayOfWeek;
                    if ($dayOfWeek !== 0 && $dayOfWeek !== 6) {
                        $workDays[] = $currentDate->format('Y-m-d');
                    }
                    $currentDate->addDay();
                }

                return [
                    'id' => $request->id,
                    'user_id' => $request->user_id,
                    'start_date' => $request->start_date->format('Y-m-d'),
                    'end_date' => $request->end_date->format('Y-m-d'),
                    'work_days' => $workDays,
                    'days' => $request->days,
                    'day_type' => $request->day_type ?? 'full_day',
                    'notes' => $request->notes,
                    'status' => $request->status,
                    'employee_name' => $request->user ? $request->user->full_name : 'Unbekannt',
                    'department' => $request->user && $request->user->currentTeam ? $request->user->currentTeam->name : 'Keine Abteilung'
                ];
            });

            return response()->json($vacationRequests);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
