<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $roles = Role::all()->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description
                ];
            });

            return response()->json($roles);
        } catch (\Exception $e) {
            Log::error('Fehler im RoleController::index: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

