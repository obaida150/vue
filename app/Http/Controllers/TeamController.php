<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    /**
     * Display a listing of the teams (departments).
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $teams = Team::where('personal_team', false)->get()->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'description' => $team->description
                ];
            });

            return response()->json($teams);
        } catch (\Exception $e) {
            Log::error('Fehler im TeamController::index: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

