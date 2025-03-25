<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VacationBalance;
use App\Models\VacationRequest;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VacationController extends Controller
{
    /**
     * Urlaubsguthaben des aktuellen Benutzers abrufen
     */
    public function getUserBalance()
    {
        $user = Auth::user();
        $currentYear = date('Y');

        $balance = VacationBalance::firstOrCreate(
            ['user_id' => $user->id, 'year' => $currentYear],
            ['total_days' => $user->vacation_days_per_year, 'used_days' => 0]
        );

        return response()->json($balance);
    }

    /**
     * Urlaubsanträge des aktuellen Benutzers abrufen
     */
    public function getUserRequests()
    {
        $user = Auth::user();
        $requests = VacationRequest::where('user_id', $user->id)
            ->with(['substitute', 'approver', 'rejecter'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    /**
     * Urlaubsanträge für ein Team abrufen
     */
    public function getTeamRequests(Team $team)
    {
        $user = Auth::user();

        // Prüfen, ob der Benutzer Mitglied des Teams ist und Manager oder Admin ist
        if (!$user->belongsToTeam($team) || (!$user->isAdmin() && !$user->isManager() && !$user->isPersonal())) {
            return response()->json(['message' => 'Nicht autorisiert'], 403);
        }

        $requests = VacationRequest::where('team_id', $team->id)
            ->with(['user', 'substitute', 'approver', 'rejecter'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($requests);
    }

    /**
     * Einen neuen Urlaubsantrag erstellen
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'team_id' => 'required|exists:teams,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'days' => 'required|integer|min:1',
            'substitute_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Prüfen, ob der Benutzer Mitglied des Teams ist
        $team = Team::find($request->team_id);
        if (!$user->belongsToTeam($team)) {
            return response()->json(['message' => 'Nicht autorisiert'], 403);
        }

        // Prüfen, ob genügend Urlaubstage verfügbar sind
        $currentYear = date('Y');
        $balance = VacationBalance::firstOrCreate(
            ['user_id' => $user->id, 'year' => $currentYear],
            ['total_days' => $user->vacation_days_per_year, 'used_days' => 0]
        );

        $remainingDays = $balance->total_days - $balance->used_days;
        if ($request->days > $remainingDays) {
            return response()->json(['message' => 'Nicht genügend Urlaubstage verfügbar'], 422);
        }

        $vacationRequest = VacationRequest::create([
            'user_id' => $user->id,
            'team_id' => $request->team_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'days' => $request->days,
            'substitute_id' => $request->substitute_id,
            'notes' => $request->notes,
            'status' => 'pending',
        ]);

        return response()->json($vacationRequest->load(['substitute']), 201);
    }

    /**
     * Einen Urlaubsantrag genehmigen
     */
    public function approve(Request $request, VacationRequest $vacationRequest)
    {
        $user = Auth::user();

        // Prüfen, ob der Benutzer den Antrag genehmigen darf
        if (!$user->isAdmin() && !$user->isManager() && !$user->isPersonal()) {
            return response()->json(['message' => 'Nicht autorisiert'], 403);
        }

        // Prüfen, ob der Antrag noch ausstehend ist
        if (!$vacationRequest->isPending()) {
            return response()->json(['message' => 'Der Antrag ist nicht mehr ausstehend'], 422);
        }

        $vacationRequest->status = 'approved';
        $vacationRequest->approved_by = $user->id;
        $vacationRequest->approved_date = now();
        $vacationRequest->save();

        // Urlaubstage vom Guthaben abziehen
        $currentYear = date('Y');
        $balance = VacationBalance::where('user_id', $vacationRequest->user_id)
            ->where('year', $currentYear)
            ->first();

        if ($balance) {
            $balance->used_days += $vacationRequest->days;
            $balance->save();
        }

        return response()->json($vacationRequest->load(['approver']));
    }

    /**
     * Einen Urlaubsantrag ablehnen
     */
    public function reject(Request $request, VacationRequest $vacationRequest)
    {
        $user = Auth::user();

        // Prüfen, ob der Benutzer den Antrag ablehnen darf
        if (!$user->isAdmin() && !$user->isManager() && !$user->isPersonal()) {
            return response()->json(['message' => 'Nicht autorisiert'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Prüfen, ob der Antrag noch ausstehend ist
        if (!$vacationRequest->isPending()) {
            return response()->json(['message' => 'Der Antrag ist nicht mehr ausstehend'], 422);
        }

        $vacationRequest->status = 'rejected';
        $vacationRequest->rejected_by = $user->id;
        $vacationRequest->rejected_date = now();
        $vacationRequest->rejection_reason = $request->rejection_reason;
        $vacationRequest->save();

        return response()->json($vacationRequest->load(['rejecter']));
    }

    /**
     * Einen Urlaubsantrag zurückziehen
     */
    public function cancel(VacationRequest $vacationRequest)
    {
        $user = Auth::user();

        // Prüfen, ob der Benutzer den Antrag zurückziehen darf
        if ($vacationRequest->user_id !== $user->id) {
            return response()->json(['message' => 'Nicht autorisiert'], 403);
        }

        // Prüfen, ob der Antrag noch ausstehend ist
        if (!$vacationRequest->isPending()) {
            return response()->json(['message' => 'Der Antrag ist nicht mehr ausstehend'], 422);
        }

        $vacationRequest->delete();

        return response()->json(['message' => 'Urlaubsantrag zurückgezogen']);
    }

    /**
     * Verfügbare Vertretungen abrufen
     */
    public function getAvailableSubstitutes(Request $request)
    {
        $user = Auth::user();
        $teamId = $request->input('team_id');

        // Prüfen, ob der Benutzer Mitglied des Teams ist
        $team = Team::find($teamId);
        if (!$team || !$user->belongsToTeam($team)) {
            return response()->json(['message' => 'Nicht autorisiert'], 403);
        }

        // Alle Teammitglieder außer dem aktuellen Benutzer abrufen
        $substitutes = $team->users()
            ->where('users.id', '!=', $user->id)
            ->where('users.is_active', true)
            ->select('users.id', 'users.name', 'users.first_name', 'users.last_name')
            ->get();

        return response()->json($substitutes);
    }
}

