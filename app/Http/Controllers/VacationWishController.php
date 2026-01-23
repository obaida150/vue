<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VacationWish;
use App\Models\VacationBalance;
use App\Models\User;
use App\Models\Holiday;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class VacationWishController extends Controller
{
    /**
     * Meine Urlaubswünsche abrufen
     */
    public function getMyWishes(Request $request, $year = null)
    {
        try {
            $year = $year ?? date('Y');
            $userId = Auth::id();

            $wishes = VacationWish::where('user_id', $userId)
                ->whereYear('start_date', $year)
                ->orderBy('start_date')
                ->get()
                ->map(function ($wish) {
                    return [
                        'id' => $wish->id,
                        'start_date' => $wish->start_date->format('Y-m-d'),
                        'end_date' => $wish->end_date->format('Y-m-d'),
                        'days' => $wish->days,
                        'notes' => $wish->notes,
                        'created_at' => $wish->created_at
                    ];
                });

            return response()->json($wishes);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    /**
     * Team-Urlaubswünsche abrufen
     */
    public function getTeamWishes(Request $request, $year = null)
    {
        try {
            $year = $year ?? date('Y');
            $userId = Auth::id();

            $user = User::find($userId);

            if (!$user || !$user->current_team_id) {
                $userRecord = DB::table('users')->where('id', $userId)->first();
                $teamId = $userRecord->current_team_id ?? null;

                if (!$teamId) {
                    return response()->json([]);
                }} else {
                $teamId = $user->current_team_id;
            }

            $wishes = VacationWish::where('team_id', $teamId)
                ->where('user_id', '!=', $userId)
                ->whereYear('start_date', $year)
                ->with('user')
                ->get();

            $formattedWishes = $wishes->map(function ($wish) {
                $userName = $wish->user ? $wish->user->name : 'Unbekannt';

                return [
                    'id' => $wish->id,
                    'user_id' => $wish->user_id,
                    'start_date' => $wish->start_date->format('Y-m-d'),
                    'end_date' => $wish->end_date->format('Y-m-d'),
                    'days' => $wish->days,
                    'notes' => $wish->notes,
                    'created_at' => $wish->created_at,
                    'employee_name' => $userName
                ];
            });

            return response()->json($formattedWishes->values());
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    /**
     * Urlaubswunsch speichern
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'startDate' => 'required|date',
                'endDate' => 'required|date|after_or_equal:startDate',
                'days' => 'required|integer|min:1',
                'notes' => 'nullable|string|max:255'
            ]);

            $userId = Auth::id();
            $user = User::find($userId);
            $year = Carbon::parse($request->startDate)->year;

            if (!$user) {
                return response()->json([
                    'message' => 'Benutzer nicht gefunden'
                ], 404);
            }

            $vacationBalance = $this->getUserVacationBalance($userId, $year);

            $plannedDays = VacationWish::where('user_id', $userId)
                ->whereYear('start_date', $year)
                ->sum('days');

            if ($plannedDays + $request->days > $vacationBalance) {
                return response()->json([
                    'message' => 'Nicht genügend Urlaubstage verfügbar. Sie haben ' . $vacationBalance . ' Tage verfügbar und bereits ' . $plannedDays . ' Tage geplant.'
                ], 400);
            }

            $teamId = $user->current_team_id;

            if (!$teamId) {
                $userRecord = DB::table('users')->where('id', $userId)->first();
                if ($userRecord && isset($userRecord->current_team_id)) {
                    $teamId = $userRecord->current_team_id;
                }
            }

            $wish = new VacationWish();
            $wish->user_id = $userId;
            $wish->team_id = $teamId;
            $wish->start_date = $request->startDate;
            $wish->end_date = $request->endDate;
            $wish->days = $request->days;
            $wish->notes = $request->notes;
            $wish->save();

            return response()->json($wish, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validierungsfehler',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'message' => 'Datenbankfehler beim Speichern des Urlaubswunsches: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Der Urlaubswunsch konnte nicht gespeichert werden: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Urlaubswunsch löschen
     */
    public function destroy($id)
    {
        try {
            $userId = Auth::id();
            $wish = VacationWish::where('id', $id)
                ->where('user_id', $userId)
                ->first();

            if (!$wish) {
                return response()->json(['message' => 'Urlaubswunsch nicht gefunden'], 404);
            }

            $wish->delete();

            return response()->json(['message' => 'Urlaubswunsch erfolgreich gelöscht']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Der Urlaubswunsch konnte nicht gelöscht werden: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verfügbare Urlaubstage abrufen
     */
    public function getVacationBalance(Request $request, $year = null)
    {
        try {
            $year = $year ?? date('Y');
            $userId = Auth::id();

            $balance = VacationBalance::where('user_id', $userId)
                ->where('year', $year)
                ->first();

            if (!$balance) {
                $user = User::find($userId);
                $defaultDays = $user->vacation_days_per_year ?? 30;

                $balance = VacationBalance::create([
                    'user_id' => $userId,
                    'year' => $year,
                    'total_days' => $defaultDays,
                    'used_days' => 0
                ]);
            }

            $availableDays = $balance->total_days - $balance->used_days;

            return response()->json([
                'year' => (int)$year,
                'total_days' => $balance->total_days,
                'used_days' => $balance->used_days,
                'available_days' => max(0, $availableDays),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'year' => (int)($year ?? date('Y')),
                'total_days' => 30,
                'used_days' => 0,
                'available_days' => 30,
            ]);
        }
    }

    /**
     * Hilfsmethode: Verfügbare Urlaubstage für einen Benutzer abrufen
     */
    private function getUserVacationBalance($userId, $year)
    {
        try {
            $balance = VacationBalance::where('user_id', $userId)
                ->where('year', $year)
                ->first();

            if (!$balance) {
                $user = User::find($userId);
                $defaultDays = $user->vacation_days_per_year ?? 30;

                $balance = VacationBalance::create([
                    'user_id' => $userId,
                    'year' => $year,
                    'total_days' => $defaultDays,
                    'used_days' => 0
                ]);
            }

            return max(0, $balance->total_days - $balance->used_days);
        } catch (\Exception $e) {
            return 30;
        }
    }

    /**
     * Feiertage abrufen
     */
    public function getHolidays(Request $request, $year = null)
    {
        try {
            $year = $year ?? date('Y');

            if (Schema::hasTable('holidays')) {
                $holidays = Holiday::whereYear('date', $year)->get();
            } else {
                $holidays = [];
            }

            return response()->json($holidays);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    /**
     * Benutzerinformationen abrufen
     */
    public function getUserInfo()
    {
        try {
            $userId = Auth::id();
            $user = User::find($userId);

            if (!$user) {
                return response()->json([
                    'id' => $userId,
                    'name' => 'Unbekannt',
                    'email' => '',
                    'team_id' => null,
                    'role_id' => null
                ]);
            }

            if ($user->current_team_id === null) {
                $userRecord = DB::table('users')->where('id', $userId)->first();
                if ($userRecord && isset($userRecord->current_team_id)) {
                    $user->current_team_id = $userRecord->current_team_id;
                }
            }

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'team_id' => $user->current_team_id,
                'role_id' => $user->role_id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'id' => Auth::id(),
                'name' => 'Unbekannt',
                'email' => '',
                'team_id' => null,
                'role_id' => null
            ]);
        }
    }

    /**
     * Alle Urlaubswünsche abrufen (nur für Debug-Zwecke)
     */
    public function getAllWishes()
    {
        try {
            $wishes = VacationWish::with('user')->get();
            return response()->json($wishes);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }
}
