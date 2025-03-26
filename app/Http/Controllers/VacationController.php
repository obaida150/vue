<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\VacationRequest;
use App\Models\VacationBalance;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class VacationController extends Controller
{
    /**
     * Get user vacation data
     */
    public function getUserData()
    {
        try {
            $user = Auth::user();
            $currentYear = Carbon::now()->year;

            // Urlaubsstatistik für das aktuelle Jahr
            $vacationBalance = VacationBalance::where('user_id', $user->id)
                ->where('year', $currentYear)
                ->first();

            if (!$vacationBalance) {
                // Erstelle einen neuen Eintrag, falls keiner existiert
                $vacationBalance = VacationBalance::create([
                    'user_id' => $user->id,
                    'year' => $currentYear,
                    'total_days' => $user->vacation_days_per_year,
                    'used_days' => 0
                ]);
            }

            // Berechne geplante Tage (genehmigte, aber noch nicht genommene Urlaubsanträge)
            $plannedDays = VacationRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('start_date', '>=', Carbon::now())
                ->sum('days');

            $stats = [
                'total' => $vacationBalance->total_days,
                'used' => $vacationBalance->used_days,
                'planned' => $plannedDays,
                'remaining' => $vacationBalance->total_days - $vacationBalance->used_days - $plannedDays
            ];

            // Urlaubsanträge des Benutzers
            $requests = VacationRequest::where('user_id', $user->id)
                ->with('substitute')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'startDate' => $request->start_date->format('Y-m-d'),
                        'endDate' => $request->end_date->format('Y-m-d'),
                        'days' => $request->days,
                        'requestDate' => $request->created_at->format('Y-m-d H:i:s'),
                        'status' => $request->status,
                        'substitute' => $request->substitute ? [
                            'id' => $request->substitute->id,
                            'name' => $request->substitute->full_name
                        ] : null,
                        'notes' => $request->notes,
                        'approvedBy' => $request->approver ? $request->approver->full_name : null,
                        'approvedDate' => $request->approved_date ? $request->approved_date->format('Y-m-d H:i:s') : null,
                        'rejectedBy' => $request->rejector ? $request->rejector->full_name : null,
                        'rejectedDate' => $request->rejected_date ? $request->rejected_date->format('Y-m-d H:i:s') : null,
                        'rejectionReason' => $request->rejection_reason
                    ];
                });

            // Bereits gebuchte Urlaubstage
            $bookedDates = VacationRequest::where('status', 'approved')
                ->get()
                ->map(function ($request) {
                    return [
                        'start' => $request->start_date->format('Y-m-d'),
                        'end' => $request->end_date->format('Y-m-d')
                    ];
                });

            // Verfügbare Vertretungen (Teammitglieder)
            $substitutes = [];

            if ($user->currentTeam) {
                $substitutes = $user->currentTeam->users()
                    ->where('users.id', '!=', $user->id)
                    ->where('users.is_active', true)
                    ->get()
                    ->map(function ($teamUser) {
                        return [
                            'id' => $teamUser->id,
                            'name' => $teamUser->full_name,
                            'department' => $teamUser->currentTeam ? $teamUser->currentTeam->name : null
                        ];
                    });
            }

            return response()->json([
                'stats' => $stats,
                'requests' => $requests,
                'bookedDates' => $bookedDates,
                'substitutes' => $substitutes
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get vacation requests for management
     */
    public function getRequests()
    {
        try {
            // Prüfen, ob der Benutzer Manager oder HR ist
            $user = Auth::user();

            // Alle Urlaubsanträge laden
            $query = VacationRequest::with(['user', 'user.currentTeam', 'substitute']);

            // Wenn der Benutzer Abteilungsleiter ist, nur Anträge seiner Abteilung anzeigen
            if ($user->role->name === 'Abteilungsleiter') {
                $teamId = $user->currentTeam ? $user->currentTeam->id : 0;
                $query->where('team_id', $teamId);
            }

            $allRequests = $query->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'employee' => [
                            'id' => $request->user->id,
                            'name' => $request->user->full_name
                        ],
                        'department' => $request->user->currentTeam ? $request->user->currentTeam->name : 'Keine Abteilung',
                        'startDate' => $request->start_date->format('Y-m-d'),
                        'endDate' => $request->end_date->format('Y-m-d'),
                        'days' => $request->days,
                        'requestDate' => $request->created_at->format('Y-m-d H:i:s'),
                        'status' => $request->status,
                        'substitute' => $request->substitute ? [
                            'id' => $request->substitute->id,
                            'name' => $request->substitute->full_name
                        ] : null,
                        'notes' => $request->notes,
                        'approvedBy' => $request->approver ? $request->approver->full_name : null,
                        'approvedDate' => $request->approved_date ? $request->approved_date->format('Y-m-d H:i:s') : null,
                        'rejectedBy' => $request->rejector ? $request->rejector->full_name : null,
                        'rejectedDate' => $request->rejected_date ? $request->rejected_date->format('Y-m-d H:i:s') : null,
                        'rejectionReason' => $request->rejection_reason
                    ];
                });

            // Nach Status filtern
            $pending = $allRequests->where('status', 'pending')->values();
            $approved = $allRequests->where('status', 'approved')->values();
            $rejected = $allRequests->where('status', 'rejected')->values();

            return response()->json([
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Submit a vacation request
     */
    public function submitRequest(Request $request)
    {
        try {
            $user = Auth::user();

            // Validierung
            $request->validate([
                'periods' => 'required|array',
                'periods.*.startDate' => 'required|date',
                'periods.*.endDate' => 'required|date|after_or_equal:periods.*.startDate',
                'periods.*.days' => 'required|integer|min:1',
                'substitute' => 'nullable|integer',
                'notes' => 'nullable|string'
            ]);

            $createdRequests = [];
            $teamId = $user->currentTeam ? $user->currentTeam->id : null;

            // Für jeden Zeitraum einen Urlaubsantrag erstellen
            foreach ($request->periods as $period) {
                $vacationRequest = new VacationRequest();
                $vacationRequest->user_id = $user->id;
                $vacationRequest->team_id = $teamId;
                $vacationRequest->start_date = $period['startDate'];
                $vacationRequest->end_date = $period['endDate'];
                $vacationRequest->days = $period['days'];
                $vacationRequest->substitute_id = $request->substitute;
                $vacationRequest->notes = $request->notes;
                $vacationRequest->status = 'pending';
                $vacationRequest->save();

                $createdRequests[] = $vacationRequest;

                // Benachrichtigung für den Abteilungsleiter erstellen
                if ($teamId) {
                    $managers = User::whereHas('role', function ($query) {
                        $query->where('name', 'Abteilungsleiter');
                    })->whereHas('teams', function ($query) use ($teamId) {
                        $query->where('teams.id', $teamId);
                    })->get();

                    foreach ($managers as $manager) {
                        Notification::create([
                            'user_id' => $manager->id,
                            'title' => 'Neuer Urlaubsantrag',
                            'message' => "{$user->full_name} hat einen Urlaubsantrag für {$period['days']} Tage eingereicht.",
                            'type' => 'info',
                            'is_read' => false,
                            'related_entity_type' => 'vacation_request',
                            'related_entity_id' => $vacationRequest->id,
                            'created_at' => now()
                        ]);
                    }
                }
            }

            return response()->json([
                'message' => 'Urlaubsantrag erfolgreich eingereicht',
                'requests' => $createdRequests
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Approve a vacation request
     */
    public function approveRequest(Request $request, $id)
    {
        try {
            $user = Auth::user();

            // Urlaubsantrag laden
            $vacationRequest = VacationRequest::findOrFail($id);

            // Status aktualisieren
            $vacationRequest->status = 'approved';
            $vacationRequest->approved_by = $user->id;
            $vacationRequest->approved_date = now();
            $vacationRequest->notes = $request->notes ?? $vacationRequest->notes;
            $vacationRequest->save();

            // Benachrichtigung für den Mitarbeiter erstellen
            Notification::create([
                'user_id' => $vacationRequest->user_id,
                'title' => 'Urlaubsantrag genehmigt',
                'message' => "Ihr Urlaubsantrag vom {$vacationRequest->start_date->format('d.m.Y')} bis {$vacationRequest->end_date->format('d.m.Y')} wurde genehmigt.",
                'type' => 'success',
                'is_read' => false,
                'related_entity_type' => 'vacation_request',
                'related_entity_id' => $vacationRequest->id,
                'created_at' => now()
            ]);

            return response()->json([
                'message' => 'Urlaubsantrag genehmigt',
                'request' => $vacationRequest
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Reject a vacation request
     */
    public function rejectRequest(Request $request, $id)
    {
        try {
            $user = Auth::user();

            // Validierung
            $request->validate([
                'reason' => 'required|string'
            ]);

            // Urlaubsantrag laden
            $vacationRequest = VacationRequest::findOrFail($id);

            // Status aktualisieren
            $vacationRequest->status = 'rejected';
            $vacationRequest->rejected_by = $user->id;
            $vacationRequest->rejected_date = now();
            $vacationRequest->rejection_reason = $request->reason;
            $vacationRequest->save();

            // Benachrichtigung für den Mitarbeiter erstellen
            Notification::create([
                'user_id' => $vacationRequest->user_id,
                'title' => 'Urlaubsantrag abgelehnt',
                'message' => "Ihr Urlaubsantrag vom {$vacationRequest->start_date->format('d.m.Y')} bis {$vacationRequest->end_date->format('d.m.Y')} wurde abgelehnt.",
                'type' => 'error',
                'is_read' => false,
                'related_entity_type' => 'vacation_request',
                'related_entity_id' => $vacationRequest->id,
                'created_at' => now()
            ]);

            return response()->json([
                'message' => 'Urlaubsantrag abgelehnt',
                'request' => $vacationRequest
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

