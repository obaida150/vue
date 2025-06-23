<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Team;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Jetstream\Jetstream;
use Carbon\Carbon;
use App\Models\VacationBalance;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index()
    {
        try {
            // Prüfen, ob der Benutzer berechtigt ist
            $currentUser = Auth::user();
            $role = $currentUser->role ? $currentUser->role->name : null;

            if (!in_array($role, ['HR', 'Admin', 'Personal'])) {
                return response()->json(['error' => 'Nicht autorisiert'], 403);
            }

            $users = User::with(['role', 'currentTeam', 'mentor', 'apprentices', 'vacationBalances'])->get()->map(function ($user) {
                // NEU: Hole den aktuellen Urlaubssaldo
                $currentBalance = $user->vacationBalances()
                    ->where('year', Carbon::now()->year)
                    ->first();

                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'department' => $user->currentTeam ? [
                        'id' => $user->currentTeam->id,
                        'name' => $user->currentTeam->name
                    ] : null,
                    'role' => $user->role ? [
                        'id' => $user->role->id,
                        'name' => $user->role->name,
                        'description' => $user->role->description
                    ] : null,
                    'status' => $user->is_active,
                    'is_active' => $user->is_active,
                    'employee_number' => $user->employee_number,
                    'entry_date' => $user->entry_date ? $user->entry_date->format('Y-m-d') : null,
                    'vacation_days' => $user->vacation_days_per_year,
                    'vacation_days_per_year' => $user->vacation_days_per_year,
                    'initials' => $user->initials,
                    'birth_date' => $user->birth_date ? $user->birth_date->format('Y-m-d') : null,
                    // NEU: Mentor-System Felder
                    'is_apprentice' => $user->is_apprentice ?? false,
                    'mentor' => $user->mentor ? [
                        'id' => $user->mentor->id,
                        'name' => $user->mentor->full_name
                    ] : null,
                    'apprentices_count' => $user->apprentices->count(),
                    // NEU: Urlaubssaldo-Informationen (unterstützt Halbtage und Carry-Over)
                    'vacation_balance' => $currentBalance ? [
                        'total_days' => (float) $currentBalance->total_days,
                        'used_days' => (float) $currentBalance->used_days,
                        'remaining_days' => (float) $currentBalance->remaining_days,
                        'carry_over_days' => (float) $currentBalance->carry_over_days,
                        'carry_over_used' => (float) $currentBalance->carry_over_used,
                        'carry_over_expires_at' => $currentBalance->carry_over_expires_at ? $currentBalance->carry_over_expires_at->format('Y-m-d') : null,
                        'max_carry_over' => (float) $currentBalance->max_carry_over,
                        'available_carry_over' => (float) $currentBalance->getAvailableCarryOverDays(),
                        'total_available' => (float) $currentBalance->total_available_days,
                        'usage_percentage' => (float) $currentBalance->usage_percentage,
                        'is_fully_used' => $currentBalance->is_fully_used,
                        'description' => $currentBalance->description
                    ] : null,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $user->updated_at->format('Y-m-d H:i:s')
                ];
            });

            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('Error in UserController::index: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        try {
            // Prüfen, ob der Benutzer berechtigt ist
            $currentUser = Auth::user();
            $role = $currentUser->role ? $currentUser->role->name : null;

            if (!in_array($role, ['HR', 'Admin', 'Personal'])) {
                return response()->json(['error' => 'Nicht autorisiert'], 403);
            }

            $user = User::with(['role', 'currentTeam', 'mentor', 'apprentices', 'vacationBalances'])->findOrFail($id);

            // NEU: Hole alle Urlaubssalden für verschiedene Jahre
            $vacationBalances = $user->vacationBalances()
                ->orderBy('year', 'desc')
                ->get()
                ->map(function($balance) {
                    return [
                        'year' => $balance->year,
                        'total_days' => (float) $balance->total_days,
                        'used_days' => (float) $balance->used_days,
                        'remaining_days' => (float) $balance->remaining_days,
                        'usage_percentage' => (float) $balance->usage_percentage,
                        'is_fully_used' => $balance->is_fully_used,
                        'description' => $balance->description
                    ];
                });

            return response()->json([
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'name' => $user->name,
                'email' => $user->email,
                'birth_date' => $user->birth_date ? $user->birth_date->format('Y-m-d') : null,
                'department' => $user->currentTeam ? [
                    'id' => $user->currentTeam->id,
                    'name' => $user->currentTeam->name
                ] : null,
                'role' => $user->role ? [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                    'description' => $user->role->description
                ] : null,
                'status' => $user->is_active,
                'is_active' => $user->is_active,
                'employee_number' => $user->employee_number,
                'entry_date' => $user->entry_date ? $user->entry_date->format('Y-m-d') : null,
                'vacation_days' => $user->vacation_days_per_year,
                'vacation_days_per_year' => $user->vacation_days_per_year,
                'initials' => $user->initials,
                // NEU: Mentor-System Felder
                'is_apprentice' => $user->is_apprentice ?? false,
                'mentor' => $user->mentor ? [
                    'id' => $user->mentor->id,
                    'name' => $user->mentor->full_name
                ] : null,
                'mentor_id' => $user->mentor_id,
                'apprentices' => $user->apprentices->map(function($apprentice) {
                    return [
                        'id' => $apprentice->id,
                        'name' => $apprentice->full_name
                    ];
                }),
                // NEU: Urlaubssalden für alle Jahre (unterstützt Halbtage)
                'vacation_balances' => $vacationBalances
            ]);
        } catch (\Exception $e) {
            Log::error('Error in UserController::show: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Konvertiert eine Benutzerrolle in eine Team-Rolle
     */
    private function mapRoleToTeamRole($roleId)
    {
        // Hole die Rolle aus der Datenbank
        $role = Role::find($roleId);

        if (!$role) {
            Log::warning("Rolle mit ID {$roleId} nicht gefunden, verwende Standardrolle 'editor'");
            return 'editor'; // Standardrolle, falls keine Rolle gefunden wird
        }

        Log::info("Mappe Rolle: {$role->name} (ID: {$role->id})");

        // Mapping von Benutzerrollen zu Team-Rollen
        switch ($role->name) {
            case 'Admin':
                return 'admin';
            case 'Abteilungsleiter':
                return 'Abteilungsleiter';
            case 'HR':
            case 'Personal':
                return 'HR';
            case 'Mitarbeiter':
                return 'Mitarbeiter';
            case 'Azubi': // NEU für Mentor-System
                return 'Azubi';
            default:
                Log::warning("Unbekannte Rolle: {$role->name}, verwende Standardrolle 'editor'");
                return 'editor';
        }
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        try {
            // Prüfen, ob der Benutzer berechtigt ist
            $currentUser = Auth::user();
            $role = $currentUser->role ? $currentUser->role->name : null;

            if (!in_array($role, ['HR', 'Admin', 'Personal'])) {
                return response()->json(['error' => 'Nicht autorisiert'], 403);
            }

            // Validiere die Eingabedaten
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'current_team_id' => 'nullable|exists:teams,id', // Bleibt nullable für automatische Zuweisung
                'role_id' => 'required|exists:roles,id',
                'initials' => 'nullable|string|max:5',
                'employee_number' => 'nullable|string|max:50',
                'vacation_days_per_year' => 'nullable|numeric|min:0|max:50', // NEU: numeric statt integer für Dezimalwerte
                'entry_date' => 'nullable|date',
                'birth_date' => 'nullable|date',
                'status' => 'boolean',
                // NEU: Mentor-System Validierung
                'is_apprentice' => 'boolean',
                'mentor_id' => 'nullable|exists:users,id'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }

            // NEU: Mentor-System Validierung
            if ($request->is_apprentice && !$request->mentor_id) {
                return response()->json([
                    'error' => 'Ein Azubi muss einen Mentor haben.'
                ], 422);
            }

            if ($request->mentor_id && !$request->is_apprentice) {
                return response()->json([
                    'error' => 'Nur Azubis können einen Mentor haben.'
                ], 422);
            }

            // Prüfe, ob die Azubi-Rolle ausgewählt wurde
            $selectedRole = Role::find($request->role_id);
            if ($selectedRole && $selectedRole->name === 'Azubi') {
                $request->merge(['is_apprentice' => true]);

                if (!$request->mentor_id) {
                    return response()->json([
                        'error' => 'Für die Rolle "Azubi" muss ein Mentor ausgewählt werden.'
                    ], 422);
                }
            }

            // NEU: Bestimme Urlaubstage basierend auf Rolle (unterstützt Dezimalwerte)
            $defaultVacationDays = $request->is_apprentice ? 25.0 : 30.0;
            $vacationDaysPerYear = $request->vacation_days_per_year ?? $defaultVacationDays;

            // Erstelle den Benutzer
            $user = new User();
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->role_id = $request->role_id;
            $user->is_active = $request->status ?? true;
            $user->initials = $request->initials ?? strtoupper(substr($request->first_name, 0, 1) . substr($request->last_name, 0, 1));
            $user->employee_number = $request->employee_number;
            $user->vacation_days_per_year = $vacationDaysPerYear;
            $user->entry_date = $request->entry_date;
            $user->birth_date = $request->birth_date;

            // NEU: Mentor-System Felder
            $user->is_apprentice = $request->is_apprentice ?? false;
            $user->mentor_id = $request->mentor_id;

            // Setze das current_team_id (kann für Azubis null sein)
            $user->current_team_id = $request->current_team_id;

            $user->save();

            // Stelle sicher, dass current_team_id immer einen Wert hat
            if (!$request->current_team_id) {
                // Für Azubis: Suche ein Standard-Team oder erstelle ein "Azubi"-Team
                if ($request->is_apprentice || ($selectedRole && $selectedRole->name === 'Azubi')) {
                    $azubiTeam = Team::where('name', 'Azubis')->where('personal_team', false)->first();

                    if (!$azubiTeam) {
                        // Erstelle ein Standard-Azubi-Team falls es nicht existiert
                        $azubiTeam = Team::create([
                            'name' => 'Azubis',
                            'personal_team' => false,
                            'user_id' => Auth::id()
                        ]);
                        Log::info("Azubi-Team erstellt: {$azubiTeam->id}");
                    }

                    $user->current_team_id = $azubiTeam->id;
                } else {
                    // Für normale Mitarbeiter: Verwende das erste verfügbare Team
                    $defaultTeam = Team::where('personal_team', false)->first();
                    if (!$defaultTeam) {
                        return response()->json([
                            'error' => 'Es muss mindestens ein Team existieren, um Benutzer zu erstellen.'
                        ], 422);
                    }
                    $user->current_team_id = $defaultTeam->id;
                }
            } else {
                $user->current_team_id = $request->current_team_id;
            }

            // Team-Zuordnung (nur wenn ein Team ausgewählt wurde)
            if ($request->current_team_id) {
                $team = Team::find($request->current_team_id);
                if ($team) {
                    // Konvertiere die Benutzerrolle in eine Team-Rolle
                    $teamRole = $this->mapRoleToTeamRole($request->role_id);

                    $user->teams()->attach($team, ['role' => $teamRole]);
                    Log::info("Benutzer {$user->id} wurde dem Team {$team->id} mit der Rolle {$teamRole} hinzugefügt");
                }
            }

            // NEU: Erstelle einen Urlaubssaldo für den Benutzer (unterstützt Dezimalwerte)
            $currentYear = Carbon::now()->year;
            VacationBalance::create([
                'user_id' => $user->id,
                'year' => $currentYear,
                'total_days' => (float) $vacationDaysPerYear,
                'used_days' => 0.0
            ]);

            Log::info("Urlaubssaldo für Benutzer {$user->id} erstellt: {$vacationDaysPerYear} Tage für {$currentYear}");

            // Lade den User mit Beziehungen für die Antwort
            $user->load(['role', 'currentTeam', 'mentor', 'vacationBalances']);

            return response()->json([
                'message' => 'Benutzer erfolgreich erstellt',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error in UserController::store: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Prüfen, ob der Benutzer berechtigt ist
            $currentUser = Auth::user();
            $role = $currentUser->role ? $currentUser->role->name : null;

            if (!in_array($role, ['HR', 'Admin', 'Personal'])) {
                return response()->json(['error' => 'Nicht autorisiert'], 403);
            }

            $user = User::findOrFail($id);

            // Validiere die Eingabedaten
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:8',
                'current_team_id' => 'required|exists:teams,id', // REQUIRED statt nullable
                'role_id' => 'required|exists:roles,id',
                'initials' => 'nullable|string|max:5',
                'employee_number' => 'nullable|string|max:50',
                'vacation_days_per_year' => 'nullable|numeric|min:0|max:50', // NEU: numeric statt integer
                'entry_date' => 'nullable|date',
                'birth_date' => 'nullable|date',
                'status' => 'boolean',
                'is_apprentice' => 'boolean',
                'mentor_id' => 'nullable|exists:users,id',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }

            // NEU: Mentor-System Validierung
            if ($request->is_apprentice && !$request->mentor_id) {
                return response()->json([
                    'error' => 'Ein Azubi muss einen Mentor haben.'
                ], 422);
            }

            if ($request->mentor_id && !$request->is_apprentice) {
                return response()->json([
                    'error' => 'Nur Azubis können einen Mentor haben.'
                ], 422);
            }

            // Prüfe, ob die Azubi-Rolle ausgewählt wurde
            $selectedRole = Role::find($request->role_id);
            if ($selectedRole && $selectedRole->name === 'Azubi') {
                $request->merge(['is_apprentice' => true]);

                if (!$request->mentor_id) {
                    return response()->json([
                        'error' => 'Für die Rolle "Azubi" muss ein Mentor ausgewählt werden.'
                    ], 422);
                }
            }

            // Speichere das aktuelle Team-ID und die Rolle, um später zu prüfen, ob sie sich geändert haben
            $oldTeamId = $user->current_team_id;
            $oldRoleId = $user->role_id;
            $oldVacationDays = $user->vacation_days_per_year;
            $newTeamId = $request->current_team_id;
            $newRoleId = $request->role_id;
            $newVacationDays = $request->vacation_days_per_year ?? 30.0;

            // Stelle sicher, dass current_team_id nie null wird
            $newTeamId = $request->current_team_id;
            if (!$newTeamId) {
                return response()->json([
                    'error' => 'Ein Team muss ausgewählt werden.'
                ], 422);
            }

            // Aktualisiere den Benutzer
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->name = $request->name;
            $user->email = $request->email;
            if ($request->password) {
                $user->password = Hash::make($request->password);
            }
            $user->role_id = $newRoleId;
            $user->is_active = $request->status ?? $request->is_active ?? true;
            $user->initials = $request->initials ?? strtoupper(substr($request->first_name, 0, 1) . substr($request->last_name, 0, 1));
            $user->employee_number = $request->employee_number;
            $user->vacation_days_per_year = (float) $newVacationDays; // NEU: Cast zu float
            $user->entry_date = $request->entry_date;
            $user->birth_date = $request->birth_date;

            // NEU: Mentor-System Felder
            $user->is_apprentice = $request->is_apprentice ?? false;
            $user->mentor_id = $request->mentor_id;

            // Direkt das current_team_id-Feld aktualisieren
            $user->current_team_id = $newTeamId;

            $user->save();

            // NEU: Aktualisiere Urlaubssaldo wenn sich die Urlaubstage geändert haben
            if ($oldVacationDays != $newVacationDays) {
                $currentYear = Carbon::now()->year;
                $currentBalance = VacationBalance::where('user_id', $user->id)
                    ->where('year', $currentYear)
                    ->first();

                if ($currentBalance) {
                    // Berechne die Differenz
                    $difference = (float) $newVacationDays - (float) $oldVacationDays;
                    $currentBalance->total_days = (float) $newVacationDays;
                    $currentBalance->save();

                    Log::info("Urlaubssaldo für Benutzer {$user->id} aktualisiert: {$oldVacationDays} -> {$newVacationDays} Tage (Differenz: {$difference})");
                } else {
                    // Erstelle einen neuen Urlaubssaldo falls keiner existiert
                    VacationBalance::create([
                        'user_id' => $user->id,
                        'year' => $currentYear,
                        'total_days' => (float) $newVacationDays,
                        'used_days' => 0.0
                    ]);

                    Log::info("Neuer Urlaubssaldo für Benutzer {$user->id} erstellt: {$newVacationDays} Tage für {$currentYear}");
                }
            }

            // Team-Zuordnung aktualisieren (nur wenn ein Team ausgewählt wurde)
            if ($newTeamId) {
                // Konvertiere die Benutzerrolle in eine Team-Rolle
                $teamRole = $this->mapRoleToTeamRole($newRoleId);

                // Aktualisiere die team_user-Tabelle, wenn sich das Team oder die Rolle geändert hat
                if ($oldTeamId != $newTeamId || $oldRoleId != $newRoleId) {
                    $newTeam = Team::find($newTeamId);

                    if ($newTeam) {
                        // Zuerst zum neuen Team hinzufügen oder die Rolle aktualisieren
                        $user->teams()->syncWithoutDetaching([$newTeamId => ['role' => $teamRole]]);

                        Log::info("Benutzer {$user->id} wurde dem Team {$newTeamId} mit der Rolle {$teamRole} hinzugefügt oder aktualisiert");

                        // Wenn sich nur die Rolle geändert hat, aber das Team gleich geblieben ist
                        if ($oldTeamId == $newTeamId && $oldRoleId != $newRoleId) {
                            $user->teams()->updateExistingPivot($newTeamId, ['role' => $teamRole]);
                            Log::info("Rolle des Benutzers {$user->id} im Team {$newTeamId} wurde auf {$teamRole} aktualisiert");
                        }

                        // Wenn sich das Team geändert hat
                        if ($oldTeamId != $newTeamId) {
                            if ($oldTeamId) {
                                $oldTeam = Team::find($oldTeamId);
                                if ($oldTeam && !$oldTeam->personal_team) {
                                    if ($user->teams()->count() > 1) {
                                        $user->teams()->detach($oldTeamId);
                                        Log::info("Benutzer {$user->id} wurde aus dem Team {$oldTeamId} entfernt");
                                    } else {
                                        Log::warning("Benutzer {$user->id} wurde NICHT aus dem Team {$oldTeamId} entfernt, da es sein einziges Team ist");
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $user->teams()->updateExistingPivot($newTeamId, ['role' => $teamRole]);
                    Log::info("Rolle des Benutzers {$user->id} im Team {$newTeamId} wurde auf {$teamRole} aktualisiert (keine Änderung)");
                }
            }

            // Lade den User mit Beziehungen für die Antwort
            $user->load(['role', 'currentTeam', 'mentor', 'vacationBalances']);

            return response()->json([
                'message' => 'Benutzer erfolgreich aktualisiert',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            Log::error('Error in UserController::update: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get potential mentors (users who are not apprentices)
     */
    public function getPotentialMentors()
    {
        try {
            $mentors = User::where('is_active', true)
                ->where('is_apprentice', false)
                ->whereHas('role', function($query) {
                    $query->whereIn('name', ['Mitarbeiter', 'Abteilungsleiter', 'Personal', 'Admin']);
                })
                ->get()
                ->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->full_name,
                        'role' => $user->role ? $user->role->name : null,
                        'department' => $user->currentTeam ? $user->currentTeam->name : null
                    ];
                });

            return response()->json($mentors);
        } catch (\Exception $e) {
            Log::error('Error in UserController::getPotentialMentors: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy($id)
    {
        try {
            // Prüfen, ob der Benutzer berechtigt ist
            $currentUser = Auth::user();
            $role = $currentUser->role ? $currentUser->role->name : null;

            if (!in_array($role, ['HR', 'Admin', 'Personal'])) {
                return response()->json(['error' => 'Nicht autorisiert'], 403);
            }

            $user = User::findOrFail($id);

            // Prüfen, ob der Benutzer sich selbst löschen will
            if ($user->id === $currentUser->id) {
                return response()->json([
                    'error' => 'Sie können sich nicht selbst löschen.'
                ], 422);
            }

            // Prüfen, ob der Benutzer noch aktive Urlaubsanträge hat
            $activeVacationRequests = $user->vacationRequests()
                ->where('status', 'pending')
                ->count();

            if ($activeVacationRequests > 0) {
                return response()->json([
                    'error' => 'Der Benutzer hat noch aktive Urlaubsanträge und kann nicht gelöscht werden.'
                ], 422);
            }

            // Prüfen, ob der Benutzer Azubis betreut
            $apprenticesCount = $user->apprentices->count();
            if ($apprenticesCount > 0) {
                return response()->json([
                    'error' => 'Der Benutzer betreut noch Azubis und kann nicht gelöscht werden. Bitte weisen Sie die Azubis einem anderen Mentor zu.'
                ], 422);
            }

            // Benutzer als inaktiv markieren statt löschen
            $user->update([
                'is_active' => false,
                'email' => $user->email . '_deleted_' . time()
            ]);

            return response()->json([
                'message' => 'Benutzer erfolgreich deaktiviert'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in UserController::destroy: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get user statistics
     */
    public function getStatistics()
    {
        try {
            // Prüfen, ob der Benutzer berechtigt ist
            $currentUser = Auth::user();
            $role = $currentUser->role ? $currentUser->role->name : null;

            if (!in_array($role, ['HR', 'Admin', 'Personal'])) {
                return response()->json(['error' => 'Nicht autorisiert'], 403);
            }

            $currentYear = Carbon::now()->year;

            $statistics = [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'inactive_users' => User::where('is_active', false)->count(),
                'apprentices' => User::where('is_apprentice', true)->where('is_active', true)->count(),
                'mentors' => User::whereHas('apprentices')->where('is_active', true)->count(),
                'users_by_role' => [],
                'users_by_department' => [],
                // NEU: Urlaubsstatistiken (unterstützt Halbtage)
                'vacation_statistics' => [
                    'total_vacation_days' => 0,
                    'used_vacation_days' => 0,
                    'remaining_vacation_days' => 0,
                    'average_usage_percentage' => 0
                ]
            ];

            // Benutzer nach Rollen
            $roleStats = User::with('role')
                ->where('is_active', true)
                ->get()
                ->groupBy(function($user) {
                    return $user->role ? $user->role->name : 'Keine Rolle';
                })
                ->map(function($users) {
                    return $users->count();
                });

            $statistics['users_by_role'] = $roleStats;

            // Benutzer nach Abteilungen
            $departmentStats = User::with('currentTeam')
                ->where('is_active', true)
                ->get()
                ->groupBy(function($user) {
                    return $user->currentTeam ? $user->currentTeam->name : 'Keine Abteilung';
                })
                ->map(function($users) {
                    return $users->count();
                });

            $statistics['users_by_department'] = $departmentStats;

            // NEU: Urlaubsstatistiken berechnen
            $vacationBalances = VacationBalance::where('year', $currentYear)->get();

            if ($vacationBalances->count() > 0) {
                $totalDays = $vacationBalances->sum('total_days');
                $usedDays = $vacationBalances->sum('used_days');
                $remainingDays = $totalDays - $usedDays;
                $averageUsage = $totalDays > 0 ? ($usedDays / $totalDays) * 100 : 0;

                $statistics['vacation_statistics'] = [
                    'total_vacation_days' => (float) $totalDays,
                    'used_vacation_days' => (float) $usedDays,
                    'remaining_vacation_days' => (float) $remainingDays,
                    'average_usage_percentage' => round($averageUsage, 1)
                ];
            }

            return response()->json($statistics);
        } catch (\Exception $e) {
            Log::error('Error in UserController::getStatistics: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * NEU: Hole Urlaubssaldo für einen Benutzer
     */
    public function getVacationBalance($userId, $year = null)
    {
        try {
            $currentUser = Auth::user();
            $role = $currentUser->role ? $currentUser->role->name : null;

            // Prüfe Berechtigung (Benutzer kann seinen eigenen Saldo sehen oder HR/Admin können alle sehen)
            if ($currentUser->id != $userId && !in_array($role, ['HR', 'Admin', 'Personal'])) {
                return response()->json(['error' => 'Nicht autorisiert'], 403);
            }

            $year = $year ?? Carbon::now()->year;
            $user = User::findOrFail($userId);

            $balance = VacationBalance::where('user_id', $userId)
                ->where('year', $year)
                ->first();

            if (!$balance) {
                // Erstelle einen neuen Urlaubssaldo falls keiner existiert
                $balance = VacationBalance::create([
                    'user_id' => $userId,
                    'year' => $year,
                    'total_days' => (float) $user->vacation_days_per_year,
                    'used_days' => 0.0
                ]);
            }

            return response()->json([
                'user_id' => $balance->user_id,
                'year' => $balance->year,
                'total_days' => (float) $balance->total_days,
                'used_days' => (float) $balance->used_days,
                'remaining_days' => (float) $balance->remaining_days,
                'carry_over_days' => (float) $balance->carry_over_days,
                'carry_over_used' => (float) $balance->carry_over_used,
                'carry_over_expires_at' => $balance->carry_over_expires_at ? $balance->carry_over_expires_at->format('Y-m-d') : null,
                'max_carry_over' => (float) $balance->max_carry_over,
                'available_carry_over' => (float) $balance->getAvailableCarryOverDays(),
                'total_available' => (float) $balance->total_available_days,
                'usage_percentage' => (float) $balance->usage_percentage,
                'is_fully_used' => $balance->is_fully_used,
                'description' => $balance->description,
                'formatted_total' => VacationBalance::formatDays($balance->total_days),
                'formatted_used' => VacationBalance::formatDays($balance->used_days),
                'formatted_remaining' => VacationBalance::formatDays($balance->remaining_days),
                'formatted_carry_over' => VacationBalance::formatDays($balance->carry_over_days),
                'formatted_available_carry_over' => VacationBalance::formatDays($balance->getAvailableCarryOverDays())
            ]);
        } catch (\Exception $e) {
            Log::error('Error in UserController::getVacationBalance: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
