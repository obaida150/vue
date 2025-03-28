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
                    'vacation_days' => $user->vacation_days_per_year
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

    // Aktualisiere die store-Methode, um alle erforderlichen Felder zu verarbeiten

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
            $user->save();

            // Erstelle ein persönliches Team für den Benutzer
            $personalTeam = Team::forceCreate([
                'user_id' => $user->id,
                'name' => $user->name . '\'s Team',
                'personal_team' => true,
            ]);

            // Füge den Benutzer zum ausgewählten Team hinzu
            $team = Team::find($request->current_team_id);
            if ($team) {
                $user->teams()->attach($team, ['role' => 'member']);
                $user->switchTeam($team);
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

            // Aktualisiere den Benutzer
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->name = $request->name;
            $user->email = $request->email;
            if ($request->password) {
                $user->password = Hash::make($request->password);
            }
            $user->role_id = $request->role_id;
            $user->is_active = $request->status ?? true;
            $user->initials = $request->initials ?? strtoupper(substr($request->first_name, 0, 1) . substr($request->last_name, 0, 1));
            $user->employee_number = $request->employee_number;
            $user->vacation_days_per_year = $request->vacation_days_per_year ?? 30;
            $user->entry_date = $request->entry_date;
            $user->birth_date = $request->birth_date;
            $user->save();

            // Aktualisiere das Team des Benutzers, wenn es sich geändert hat
            $currentTeamId = $user->currentTeam ? $user->currentTeam->id : null;
            if ($request->current_team_id != $currentTeamId) {
                $team = Team::find($request->current_team_id);
                if ($team) {
                    // Prüfe, ob der Benutzer bereits Mitglied des Teams ist
                    if (!$user->belongsToTeam($team)) {
                        $user->teams()->attach($team, ['role' => 'member']);
                    }
                    $user->switchTeam($team);
                }
            }

            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

