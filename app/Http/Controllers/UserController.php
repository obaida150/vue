<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Team;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Jetstream\Jetstream;

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

    // Andere Methoden bleiben unverändert...
}

