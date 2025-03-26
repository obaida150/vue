<?php
//
//namespace App\Http\Controllers;
//
//use App\Models\Team;
//use App\Models\User;
//use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Auth;
//
//class TeamController extends Controller
//{
//    /**
//     * Alle Teams abrufen, zu denen der Benutzer gehört
//     */
//    public function getUserTeams()
//    {
//        $user = Auth::user();
//        $teams = $user->allTeams();
//
//        return response()->json($teams);
//    }
//
//    /**
//     * Alle Mitglieder eines Teams abrufen
//     */
//    public function getTeamMembers(Team $team)
//    {
//        $user = Auth::user();
//
//        // Prüfen, ob der Benutzer Mitglied des Teams ist
//        if (!$user->belongsToTeam($team)) {
//            return response()->json(['message' => 'Nicht autorisiert'], 403);
//        }
//
//        $members = $team->users;
//
//        return response()->json($members);
//    }
//
//    /**
//     * Homeoffice-Regeln für ein Team abrufen
//     */
//    public function getTeamHomeofficeRules(Team $team)
//    {
//        $user = Auth::user();
//
//        // Prüfen, ob der Benutzer Mitglied des Teams ist
//        if (!$user->belongsToTeam($team)) {
//            return response()->json(['message' => 'Nicht autorisiert'], 403);
//        }
//
//        $rules = $team->homeofficeRules()->with('creator')->first();
//
//        return response()->json($rules);
//    }
//}
//


namespace App\Http\Controllers;

use App\Models\Team;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    /**
     * Display a listing of the departments (teams).
     */
    public function index()
    {
        try {
            $departments = Team::where('personal_team', false)->get();
            return response()->json($departments);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

