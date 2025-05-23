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

            Log::info("Getting my wishes for user $userId for year $year");

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

            Log::info("Found " . $wishes->count() . " wishes for user $userId");
            return response()->json($wishes);
        } catch (\Exception $e) {
            Log::error('Error in getMyWishes: ' . $e->getMessage());
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

            // Direkt aus der Datenbank den Benutzer mit current_team_id abrufen
            $user = User::find($userId);

            Log::info("=== TEAM WISHES DEBUG START ===");
            Log::info("Getting team wishes for user {$userId} for year $year");
            Log::info("User current_team_id from DB: " . ($user->current_team_id ?? 'NULL'));

            // Prüfen, ob der Benutzer eine Team-ID hat
            if (!$user || !$user->current_team_id) {
                // Fallback: Direkt aus der Datenbank abfragen
                $userRecord = DB::table('users')->where('id', $userId)->first();
                $teamId = $userRecord->current_team_id ?? null;

                Log::info("Fallback: User current_team_id from direct DB query: " . ($teamId ?? 'NULL'));

                if (!$teamId) {
                    Log::warning('User has no current_team_id in getTeamWishes, even after direct DB query');
                    return response()->json([]);
                }
            } else {
                $teamId = $user->current_team_id;
            }

            // Debug: Alle Urlaubswünsche für das Team anzeigen (unabhängig vom Jahr)
            $allTeamWishes = VacationWish::where('team_id', $teamId)->get();
            Log::info("All team wishes for team {$teamId} (any year): " . $allTeamWishes->count());

            foreach ($allTeamWishes as $wish) {
                Log::info("Team wish: ID={$wish->id}, user_id={$wish->user_id}, team_id={$wish->team_id}, start_date={$wish->start_date}");
            }

            // Debug: Alle Urlaubswünsche für das Jahr anzeigen (unabhängig vom Team)
            $allYearWishes = VacationWish::whereYear('start_date', $year)->get();
            Log::info("All wishes for year $year (any team): " . $allYearWishes->count());

            // Urlaubswünsche aus demselben Team abrufen (ohne den aktuellen Benutzer)
            $wishes = VacationWish::where('team_id', $teamId)
                ->where('user_id', '!=', $userId)  // Nur andere Teammitglieder
                ->whereYear('start_date', $year)
                ->with('user')
                ->get();

            Log::info("Found " . $wishes->count() . " team wishes for team {$teamId} and year $year (excluding current user)");

            foreach ($wishes as $wish) {
                Log::info("Team wish found: ID={$wish->id}, user_id={$wish->user_id}, user_name=" . ($wish->user ? $wish->user->name : 'NULL'));
            }

            // Wünsche in das erwartete Format umwandeln
            $formattedWishes = $wishes->map(function ($wish) {
                $userName = $wish->user ? $wish->user->name : 'Unbekannt';
                Log::info("Formatting wish ID {$wish->id} for user {$userName} (user_id: {$wish->user_id})");

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

            Log::info("Returning " . $formattedWishes->count() . " formatted team wishes");
            Log::info("=== TEAM WISHES DEBUG END ===");

            return response()->json($formattedWishes->values());
        } catch (\Exception $e) {
            Log::error('Error in getTeamWishes: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([]);
        }
    }

    /**
     * Urlaubswunsch speichern
     */
    public function store(Request $request)
    {
        try {
            Log::info('Vacation wish store request received', $request->all());

            $request->validate([
                'startDate' => 'required|date',
                'endDate' => 'required|date|after_or_equal:startDate',
                'days' => 'required|integer|min:1',
                'notes' => 'nullable|string|max:255'
            ]);

            $userId = Auth::id();

            // Direkt aus der Datenbank den Benutzer mit current_team_id abrufen
            $user = User::find($userId);
            $year = Carbon::parse($request->startDate)->year;

            if (!$user) {
                Log::error('User not found in store method');
                return response()->json([
                    'message' => 'Benutzer nicht gefunden'
                ], 404);
            }

            // Verfügbare Urlaubstage prüfen
            $vacationBalance = $this->getUserVacationBalance($userId, $year);

            // Bereits geplante Urlaubswunschtage berechnen
            $plannedDays = VacationWish::where('user_id', $userId)
                ->whereYear('start_date', $year)
                ->sum('days');

            // Prüfen, ob genügend Urlaubstage verfügbar sind
            if ($plannedDays + $request->days > $vacationBalance) {
                return response()->json([
                    'message' => 'Nicht genügend Urlaubstage verfügbar. Sie haben ' . $vacationBalance . ' Tage verfügbar und bereits ' . $plannedDays . ' Tage geplant.'
                ], 400);
            }

            // Team-ID des Benutzers abrufen
            $teamId = $user->current_team_id;

            // Wenn nicht vorhanden, versuche es direkt aus der Datenbank
            if (!$teamId) {
                Log::warning("User $userId has no current_team_id in User model, trying direct DB query");

                // Fallback: Direkte DB-Abfrage
                $userRecord = DB::table('users')->where('id', $userId)->first();

                if ($userRecord && isset($userRecord->current_team_id)) {
                    $teamId = $userRecord->current_team_id;
                    Log::info("Found current_team_id $teamId for user $userId from direct DB query");
                } else {
                    Log::warning("Could not find current_team_id for user $userId, using null");
                }
            } else {
                Log::info("Using current_team_id $teamId from User model");
            }

            // Urlaubswunsch speichern
            $wish = new VacationWish();
            $wish->user_id = $userId;
            $wish->team_id = $teamId; // Kann null sein, wenn keine Team-ID gefunden wurde
            $wish->start_date = $request->startDate;
            $wish->end_date = $request->endDate;
            $wish->days = $request->days;
            $wish->notes = $request->notes;

            // Überprüfen, ob alle erforderlichen Felder gesetzt sind
            Log::info('About to save vacation wish with data:', [
                'user_id' => $wish->user_id,
                'team_id' => $wish->team_id,
                'start_date' => $wish->start_date,
                'end_date' => $wish->end_date,
                'days' => $wish->days,
                'notes' => $wish->notes
            ]);

            $wish->save();

            Log::info('Vacation wish created successfully', ['id' => $wish->id]);

            return response()->json($wish, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in store: ' . json_encode($e->errors()));
            return response()->json([
                'message' => 'Validierungsfehler',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in store: ' . $e->getMessage());

            return response()->json([
                'message' => 'Datenbankfehler beim Speichern des Urlaubswunsches: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            Log::error('Error in store: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

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
            Log::error('Error in destroy: ' . $e->getMessage());
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

            Log::info("Getting vacation balance for user $userId for year $year");

            // Urlaubskontingent aus der vacation_balances Tabelle abrufen
            $balance = VacationBalance::where('user_id', $userId)
                ->where('year', $year)
                ->first();

            if (!$balance) {
                Log::info("No vacation balance found for user $userId for year $year, creating default");

                // Wenn kein Eintrag gefunden wurde, erstellen wir einen neuen mit Standardwerten
                $user = User::find($userId);
                $defaultDays = $user->vacation_days_per_year ?? 30; // Fallback auf 30 Tage

                $balance = VacationBalance::create([
                    'user_id' => $userId,
                    'year' => $year,
                    'total_days' => $defaultDays,
                    'used_days' => 0
                ]);

                Log::info("Created default vacation balance for user $userId for year $year with $defaultDays days");
            }

            // Berechne verfügbare Tage
            $availableDays = $balance->total_days - $balance->used_days;

            $response = [
                'year' => (int)$year,
                'total_days' => $balance->total_days,
                'used_days' => $balance->used_days,
                'available_days' => max(0, $availableDays), // Stelle sicher, dass es nicht negativ wird
            ];

            Log::info("Returning vacation balance for user $userId: " . json_encode($response));

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error in getVacationBalance: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            // Fallback: Standardwerte zurückgeben
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
                // Erstelle einen neuen Eintrag mit Standardwerten
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
            Log::error('Error in getUserVacationBalance: ' . $e->getMessage());
            return 30; // Standardwert im Fehlerfall
        }
    }

    /**
     * Feiertage abrufen
     */
    public function getHolidays(Request $request, $year = null)
    {
        try {
            $year = $year ?? date('Y');

            // Prüfen, ob die Holidays-Tabelle existiert
            if (Schema::hasTable('holidays')) {
                $holidays = Holiday::whereYear('date', $year)->get();
            } else {
                // Fallback: Leere Liste zurückgeben
                $holidays = [];
            }

            return response()->json($holidays);
        } catch (\Exception $e) {
            Log::error('Error in getHolidays: ' . $e->getMessage());
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

            // Direkt aus der Datenbank den Benutzer mit current_team_id abrufen
            $user = User::find($userId);

            if (!$user) {
                Log::error("User not found in getUserInfo: $userId");
                return response()->json([
                    'id' => $userId,
                    'name' => 'Unbekannt',
                    'email' => '',
                    'team_id' => null,
                    'role_id' => null
                ]);
            }

            // Wenn current_team_id null ist, versuche es direkt aus der Datenbank
            if ($user->current_team_id === null) {
                $userRecord = DB::table('users')->where('id', $userId)->first();
                if ($userRecord && isset($userRecord->current_team_id)) {
                    $user->current_team_id = $userRecord->current_team_id;
                    Log::info("Found current_team_id {$user->current_team_id} for user $userId from direct DB query in getUserInfo");
                }
            }

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'team_id' => $user->current_team_id, // Hier verwenden wir current_team_id, aber geben es als team_id zurück
                'role_id' => $user->role_id
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getUserInfo: ' . $e->getMessage());
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
            Log::error('Error in getAllWishes: ' . $e->getMessage());
            return response()->json([]);
        }
    }
}
