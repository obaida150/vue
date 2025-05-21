<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Team;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
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
            $users = User::with(['role', 'currentTeam'])->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'department' => $user->currentTeam ? [
                        'id' => $user->currentTeam->id,
                        'name' => $user->currentTeam->name
                    ] : null,
                    'role' => $user->role,
                    'status' => $user->is_active,
                    'employee_number' => $user->employee_number,
                    'entry_date' => $user->entry_date ? $user->entry_date->format('Y-m-d') : null,
                    'vacation_days' => $user->vacation_days_per_year,
                    'initials' => $user->initials,
                    'birth_date' => $user->birth_date ? $user->birth_date->format('Y-m-d') : null
                ];
            });

            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        try {
            $user = User::with(['role', 'currentTeam'])->findOrFail($id);

            return response()->json([
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'birth_date' => $user->birth_date ? $user->birth_date->format('Y-m-d') : null,
                'department' => $user->currentTeam ? [
                    'id' => $user->currentTeam->id,
                    'name' => $user->currentTeam->name
                ] : null,
                'role' => $user->role,
                'status' => $user->is_active,
                'employee_number' => $user->employee_number,
                'entry_date' => $user->entry_date ? $user->entry_date->format('Y-m-d') : null,
                'vacation_days' => $user->vacation_days_per_year,
                'initials' => $user->initials
            ]);
        } catch (\Exception $e) {
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
            \Log::warning("Rolle mit ID {$roleId} nicht gefunden, verwende Standardrolle 'editor'");
            return 'editor'; // Standardrolle, falls keine Rolle gefunden wird
        }

        \Log::info("Mappe Rolle: {$role->name} (ID: {$role->id})");

        // Mapping von Benutzerrollen zu Team-Rollen
        switch ($role->name) {
            case 'Admin':
                return 'admin';
            case 'Abteilungsleiter':
                return 'Abteilungsleiter';
            case 'HR':
                return 'HR';
            case 'Mitarbeiter':
                return 'Mitarbeiter';
            default:
                \Log::warning("Unbekannte Rolle: {$role->name}, verwende Standardrolle 'editor'");
                return 'editor';
        }
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validiere die Eingabedaten
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'current_team_id' => 'required|exists:teams,id',
                'role_id' => 'required|exists:roles,id',
                'initials' => 'nullable|string|max:5',
                'employee_number' => 'nullable|string|max:50',
                'vacation_days_per_year' => 'nullable|integer|min:0|max:50',
                'entry_date' => 'nullable|date',
                'birth_date' => 'nullable|date',
                'status' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }

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
            $user->vacation_days_per_year = $request->vacation_days_per_year ?? 30;
            $user->entry_date = $request->entry_date;
            $user->birth_date = $request->birth_date;

            // Setze das current_team_id direkt
            $user->current_team_id = $request->current_team_id;

            $user->save();

            // Kein persönliches Team mehr erstellen
            // Stattdessen nur zum ausgewählten Team hinzufügen
            $team = Team::find($request->current_team_id);
            if ($team) {
                // Konvertiere die Benutzerrolle in eine Team-Rolle
                $teamRole = $this->mapRoleToTeamRole($request->role_id);

                $user->teams()->attach($team, ['role' => $teamRole]);
                \Log::info("Benutzer {$user->id} wurde dem Team {$team->id} mit der Rolle {$teamRole} hinzugefügt");
            }

            // Erstelle einen Urlaubssaldo für den Benutzer
            $currentYear = Carbon::now()->year;
            VacationBalance::create([
                'user_id' => $user->id,
                'year' => $currentYear,
                'total_days' => $user->vacation_days_per_year,
                'used_days' => 0
            ]);

            return response()->json($user, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            // Validiere die Eingabedaten
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:8',
                'current_team_id' => 'required|exists:teams,id',
                'role_id' => 'required|exists:roles,id',
                'initials' => 'nullable|string|max:5',
                'employee_number' => 'nullable|string|max:50',
                'vacation_days_per_year' => 'nullable|integer|min:0|max:50',
                'entry_date' => 'nullable|date',
                'birth_date' => 'nullable|date',
                'status' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }

            // Speichere das aktuelle Team-ID und die Rolle, um später zu prüfen, ob sie sich geändert haben
            $oldTeamId = $user->current_team_id;
            $oldRoleId = $user->role_id;
            $newTeamId = $request->current_team_id;
            $newRoleId = $request->role_id;

            // Aktualisiere den Benutzer
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->name = $request->name;
            $user->email = $request->email;
            if ($request->password) {
                $user->password = Hash::make($request->password);
            }
            $user->role_id = $newRoleId;
            $user->is_active = $request->status ?? true;
            $user->initials = $request->initials ?? strtoupper(substr($request->first_name, 0, 1) . substr($request->last_name, 0, 1));
            $user->employee_number = $request->employee_number;
            $user->vacation_days_per_year = $request->vacation_days_per_year ?? 30;
            $user->entry_date = $request->entry_date;
            $user->birth_date = $request->birth_date;

            // Direkt das current_team_id-Feld aktualisieren
            $user->current_team_id = $newTeamId;

            $user->save();

            // Konvertiere die Benutzerrolle in eine Team-Rolle
            $teamRole = $this->mapRoleToTeamRole($newRoleId);

            // Aktualisiere die team_user-Tabelle, wenn sich das Team oder die Rolle geändert hat
            if ($oldTeamId != $newTeamId || $oldRoleId != $newRoleId) {
                $newTeam = Team::find($newTeamId);

                if ($newTeam) {
                    // Zuerst zum neuen Team hinzufügen oder die Rolle aktualisieren
                    $user->teams()->syncWithoutDetaching([$newTeamId => ['role' => $teamRole]]);

                    // Protokolliere den Vorgang
                    \Log::info("Benutzer {$user->id} wurde dem Team {$newTeamId} mit der Rolle {$teamRole} hinzugefügt oder aktualisiert");

                    // Wenn sich nur die Rolle geändert hat, aber das Team gleich geblieben ist
                    if ($oldTeamId == $newTeamId && $oldRoleId != $newRoleId) {
                        // Aktualisiere die Rolle im aktuellen Team
                        $user->teams()->updateExistingPivot($newTeamId, ['role' => $teamRole]);
                        \Log::info("Rolle des Benutzers {$user->id} im Team {$newTeamId} wurde auf {$teamRole} aktualisiert");
                    }

                    // Wenn sich das Team geändert hat
                    if ($oldTeamId != $newTeamId) {
                        // Optional: Entferne den Benutzer aus dem alten Team, wenn es nicht das persönliche Team ist
                        if ($oldTeamId) {
                            $oldTeam = Team::find($oldTeamId);
                            if ($oldTeam && !$oldTeam->personal_team) {
                                // Prüfe, ob der Benutzer mehr als ein Team hat, bevor wir ihn aus dem alten entfernen
                                if ($user->teams()->count() > 1) {
                                    $user->teams()->detach($oldTeamId);
                                    \Log::info("Benutzer {$user->id} wurde aus dem Team {$oldTeamId} entfernt");
                                } else {
                                    \Log::warning("Benutzer {$user->id} wurde NICHT aus dem Team {$oldTeamId} entfernt, da es sein einziges Team ist");
                                }
                            }
                        }
                    }
                }
            } else {
                // Wenn sich weder Team noch Rolle geändert haben, aktualisiere trotzdem die Rolle im Team
                // Dies stellt sicher, dass die Rolle in der team_user-Tabelle immer korrekt ist
                $user->teams()->updateExistingPivot($newTeamId, ['role' => $teamRole]);
                \Log::info("Rolle des Benutzers {$user->id} im Team {$newTeamId} wurde auf {$teamRole} aktualisiert (keine Änderung)");
            }

            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
