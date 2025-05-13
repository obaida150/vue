<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\VacationRequest;
use App\Models\VacationBalance;
use App\Models\Notification;
use App\Mail\VacationApprovedMail;
use App\Mail\VacationRejectedMail;
use App\Mail\VacationRequestMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
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

            // Berechne übertragene Tage aus dem Vorjahr
            $previousYearBalance = VacationBalance::where('user_id', $user->id)
                ->where('year', $currentYear - 1)
                ->first();

            $carryOver = 0;
            if ($previousYearBalance) {
                // Maximal 10 Tage können übertragen werden
                $carryOver = min(10, $previousYearBalance->total_days - $previousYearBalance->used_days);
            }

            // Korrigierte Berechnung der verbleibenden Tage
            // Gesamtkontingent = Basis + Übertrag
            $totalEntitlement = $vacationBalance->total_days + $carryOver;

            // Verbleibende Tage = Gesamtkontingent - Bereits genommene Tage
            $remainingDays = $totalEntitlement - $vacationBalance->used_days;

            // Stellen sicher, dass verbleibende Tage nicht negativ werden
            $remainingDays = max(0, $remainingDays);

            $stats = [
                'total' => $totalEntitlement,
                'used' => $vacationBalance->used_days,
                'planned' => $plannedDays,
                'remaining' => $remainingDays,
                'carryOver' => $carryOver
            ];

            // Urlaubsanträge des Benutzers
            $requests = VacationRequest::where('user_id', $user->id)
                ->with(['substitute', 'approver', 'rejector'])
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

            // Urlaubshistorie für die letzten Jahre
            $history = [];
            $startYear = $currentYear - 5;

            for ($year = $startYear; $year <= $currentYear; $year++) {
                $balance = VacationBalance::where('user_id', $user->id)
                    ->where('year', $year)
                    ->first();

                if ($balance) {
                    $prevYearBalance = VacationBalance::where('user_id', $user->id)
                        ->where('year', $year - 1)
                        ->first();

                    $carryOverFromPrevYear = 0;
                    if ($prevYearBalance) {
                        $carryOverFromPrevYear = min(10, $prevYearBalance->total_days - $prevYearBalance->used_days);
                    }

                    $carryOverToNextYear = 0;
                    if ($year < $currentYear) {
                        $carryOverToNextYear = min(10, $balance->total_days - $balance->used_days);
                    } else {
                        $carryOverToNextYear = min(10, $stats['remaining']);
                    }

                    $history[] = [
                        'year' => $year,
                        'baseEntitlement' => $balance->total_days,
                        'carryOver' => $carryOverFromPrevYear,
                        'totalEntitlement' => $balance->total_days + $carryOverFromPrevYear,
                        'used' => $balance->used_days,
                        'remaining' => $balance->total_days + $carryOverFromPrevYear - $balance->used_days,
                        'carryOverToNextYear' => $carryOverToNextYear
                    ];
                }
            }

            // Jahresstatistik für jedes Jahr
            $yearlyStats = [];
            $yearVacationDetails = [];
            $monthlyStats = [];

            foreach ($history as $yearData) {
                $year = $yearData['year'];

                // Urlaubsanträge für dieses Jahr
                $yearRequests = VacationRequest::where('user_id', $user->id)
                    ->whereYear('start_date', $year)
                    ->with(['substitute', 'approver', 'rejector'])
                    ->orderBy('start_date')
                    ->get();

                // Monatsstatistik initialisieren
                $monthlyData = array_fill(0, 12, 0);

                // Details für jeden Urlaubsantrag
                $details = [];
                foreach ($yearRequests as $request) {
                    $details[] = [
                        'period' => $request->start_date->format('d.m.Y') . ' - ' . $request->end_date->format('d.m.Y'),
                        'days' => $request->days,
                        'status' => $request->status,
                        'requestDate' => $request->created_at->format('d.m.Y'),
                        'notes' => $request->notes
                    ];

                    // Nur genehmigte Anträge für die Monatsstatistik zählen
                    if ($request->status === 'approved') {
                        // Verteile die Tage auf die entsprechenden Monate
                        $startMonth = $request->start_date->month - 1; // 0-basierter Index
                        $endMonth = $request->end_date->month - 1;

                        if ($startMonth === $endMonth) {
                            // Wenn der Urlaub im selben Monat ist
                            $monthlyData[$startMonth] += $request->days;
                        } else {
                            // Wenn der Urlaub über mehrere Monate geht, verteile die Tage
                            $currentDate = $request->start_date->copy();
                            while ($currentDate->lte($request->end_date)) {
                                // Zähle nur Werktage (Mo-Fr)
                                $dayOfWeek = $currentDate->dayOfWeek;
                                if ($dayOfWeek !== 0 && $dayOfWeek !== 6) { // Nicht Sonntag und nicht Samstag
                                    $monthlyData[$currentDate->month - 1]++;
                                }
                                $currentDate->addDay();
                            }
                        }
                    }
                }

                $yearlyStats[$year] = $yearData;
                $yearVacationDetails[$year] = $details;
                $monthlyStats[$year] = $monthlyData;
            }

            return response()->json([
                'stats' => $stats,
                'requests' => $requests,
                'bookedDates' => $bookedDates,
                'substitutes' => $substitutes,
                'history' => $history,
                'yearlyStats' => $yearlyStats,
                'yearVacationDetails' => $yearVacationDetails,
                'monthlyStats' => $monthlyStats
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get yearly vacation data
     */
    public function getYearlyVacationData($year)
    {
        try {
            $user = Auth::user();

            // Urlaubsstatistik für das angegebene Jahr
            $vacationBalance = VacationBalance::where('user_id', $user->id)
                ->where('year', $year)
                ->first();

            if (!$vacationBalance) {
                return response()->json([
                    'stats' => [
                        'baseEntitlement' => $user->vacation_days_per_year,
                        'carryOver' => 0,
                        'totalEntitlement' => $user->vacation_days_per_year,
                        'used' => 0,
                        'planned' => 0,
                        'remaining' => $user->vacation_days_per_year
                    ],
                    'details' => []
                ]);
            }

            // Berechne übertragene Tage aus dem Vorjahr
            $previousYearBalance = VacationBalance::where('user_id', $user->id)
                ->where('year', $year - 1)
                ->first();

            $carryOver = 0;
            if ($previousYearBalance) {
                // Maximal 10 Tage können übertragen werden
                $carryOver = min(10, $previousYearBalance->total_days - $previousYearBalance->used_days);
            }

            // Berechne geplante Tage für das angegebene Jahr
            $plannedDays = 0;
            $currentYear = Carbon::now()->year;

            if ($year === $currentYear) {
                $plannedDays = VacationRequest::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->where('start_date', '>=', Carbon::now())
                    ->sum('days');
            }

            // Korrigierte Berechnung der verbleibenden Tage
            $totalEntitlement = $vacationBalance->total_days + $carryOver;
            $remainingDays = $totalEntitlement - $vacationBalance->used_days;
            $remainingDays = max(0, $remainingDays);

            $stats = [
                'baseEntitlement' => $vacationBalance->total_days,
                'carryOver' => $carryOver,
                'totalEntitlement' => $totalEntitlement,
                'used' => $vacationBalance->used_days,
                'planned' => $plannedDays,
                'remaining' => $remainingDays
            ];

            // Urlaubsanträge für das angegebene Jahr
            $yearRequests = VacationRequest::where('user_id', $user->id)
                ->whereYear('start_date', $year)
                ->with(['substitute', 'approver', 'rejector'])
                ->orderBy('start_date')
                ->get();

            // Details für jeden Urlaubsantrag
            $details = [];
            foreach ($yearRequests as $request) {
                $details[] = [
                    'period' => $request->start_date->format('d.m.Y') . ' - ' . $request->end_date->format('d.m.Y'),
                    'days' => $request->days,
                    'status' => $request->status,
                    'requestDate' => $request->created_at->format('d.m.Y'),
                    'notes' => $request->notes
                ];
            }

            return response()->json([
                'stats' => $stats,
                'details' => $details
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
            $role = $user->role ? $user->role->name : null;

            // Alle Urlaubsanträge laden
            $query = VacationRequest::with(['user', 'user.currentTeam', 'substitute']);

            // Wenn der Benutzer Abteilungsleiter ist, nur Anträge seiner Abteilung anzeigen
            if ($role === 'Abteilungsleiter') {
                $teamId = $user->currentTeam ? $user->currentTeam->id : 0;
                $query->whereHas('user', function($q) use ($teamId) {
                    $q->whereHas('teams', function($q2) use ($teamId) {
                        $q2->where('teams.id', $teamId);
                    });
                });
            }
            // Wenn der Benutzer weder HR noch Admin noch Abteilungsleiter ist, keine Daten zurückgeben
            elseif ($role !== 'HR' && $role !== 'Admin') {
                return response()->json([
                    'pending' => [],
                    'approved' => [],
                    'rejected' => []
                ]);
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
     * Get all vacation requests for calendar
     */
    public function getAllRequests()
    {
        try {
            $requests = VacationRequest::with(['user', 'user.currentTeam'])
                ->where('status', 'approved')
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'user_id' => $request->user_id,
                        'start_date' => $request->start_date->format('Y-m-d'),
                        'end_date' => $request->end_date->format('Y-m-d'),
                        'days' => $request->days,
                        'notes' => $request->notes,
                        'status' => $request->status,
                        'employee_name' => $request->user->full_name,
                        'department' => $request->user->currentTeam ? $request->user->currentTeam->name : 'Keine Abteilung'
                    ];
                });

            return response()->json($requests);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get vacation requests for the current user
     */
    public function getUserRequests(Request $request)
    {
        try {
            $user = Auth::user();
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $query = VacationRequest::where('user_id', $user->id)
                ->where('status', 'approved');

            if ($startDate && $endDate) {
                $query->where(function($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function($q2) use ($startDate, $endDate) {
                            $q2->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                });
            }

            $requests = $query->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'start_date' => $request->start_date->format('Y-m-d'),
                        'end_date' => $request->end_date->format('Y-m-d'),
                        'days' => $request->days,
                        'notes' => $request->notes,
                        'status' => $request->status
                    ];
                });

            return response()->json($requests);
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

            // Debug-Informationen
            Log::info('Urlaubsantrag empfangen', [
                'user' => $user->id,
                'request_data' => $request->all(),
                'headers' => $request->header(),
                'csrf_token' => $request->header('X-CSRF-TOKEN'),
                'session_token' => csrf_token()
            ]);

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

                // Fix: Ensure dates are stored correctly without timezone adjustment
                // Convert the date strings to Carbon instances with the correct date
                $startDate = Carbon::parse($period['startDate'])->startOfDay();
                $endDate = Carbon::parse($period['endDate'])->startOfDay();

                $vacationRequest->start_date = $startDate;
                $vacationRequest->end_date = $endDate;

                // WICHTIG: Verwende die Tage, die vom Frontend berechnet wurden
                // Das Frontend berücksichtigt bereits Wochenenden und Feiertage
                $vacationRequest->days = $period['days'];

                $vacationRequest->substitute_id = $request->substitute;

                // Wichtig: Stelle sicher, dass die Anmerkungen korrekt gespeichert werden
                $vacationRequest->notes = $request->notes;

                $vacationRequest->status = 'pending';
                $vacationRequest->save();

                Log::info('Urlaubsantrag erstellt', [
                    'request_id' => $vacationRequest->id,
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'days' => $vacationRequest->days,
                    'notes' => $request->notes // Log der Anmerkungen
                ]);

                $createdRequests[] = $vacationRequest;

                // Rest des Codes bleibt unverändert...
            }

            // Erfolgreiche Antwort
            Log::info('Urlaubsantrag erfolgreich eingereicht', [
                'request_ids' => collect($createdRequests)->pluck('id')->toArray()
            ]);

            return response()->json([
                'message' => 'Urlaubsantrag erfolgreich eingereicht',
                'requests' => $createdRequests
            ], 201);
        } catch (\Exception $e) {
            Log::error('Fehler beim Einreichen des Urlaubsantrags', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Fehler beim Einreichen des Urlaubsantrags: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genehmigt einen Urlaubsantrag
     */
    public function approveRequest(Request $request, $id)
    {
        try {
            $user = Auth::user();

            // Debug-Logging
            Log::info('Urlaubsantrag-Genehmigung empfangen', [
                'approver_id' => $user->id,
                'request_id' => $id
            ]);

            // Urlaubsantrag finden
            $vacationRequest = VacationRequest::findOrFail($id);

            // Prüfen, ob der Antrag bereits bearbeitet wurde
            if ($vacationRequest->status !== 'pending') {
                return response()->json([
                    'error' => 'Dieser Urlaubsantrag wurde bereits bearbeitet.'
                ], 400);
            }

            // Antrag genehmigen
            $vacationRequest->status = 'approved';
            $vacationRequest->approved_by = $user->id;
            $vacationRequest->approved_date = now();
            $vacationRequest->save();

            // WICHTIG: Aktualisiere die Urlaubsbilanz für ALLE genehmigten Anträge
            // Entferne die Bedingung, dass der Urlaub in der Vergangenheit liegen muss
            $this->updateVacationBalance($vacationRequest);

            // Antragsteller finden
            $employee = User::findOrFail($vacationRequest->user_id);

            // Benachrichtigung für den Antragsteller erstellen
            Notification::create([
                'user_id' => $employee->id,
                'title' => 'Urlaubsantrag genehmigt',
                'message' => "Ihr Urlaubsantrag vom {$vacationRequest->start_date->format('d.m.Y')} bis {$vacationRequest->end_date->format('d.m.Y')} wurde genehmigt.",
                'type' => 'success',
                'is_read' => false,
                'related_entity_type' => 'vacation_request',
                'related_entity_id' => $vacationRequest->id,
                'created_at' => now()
            ]);

            // E-Mail an den Antragsteller senden
            try {
                Log::info('Sende Genehmigungs-E-Mail an Antragsteller', [
                    'employee_id' => $employee->id,
                    'employee_email' => $employee->email,
                    'notes' => $vacationRequest->notes // Log der Anmerkungen
                ]);

                // Mail senden mit verbesserter Fehlerbehandlung
                if ($employee->email) {
                    // CC und BCC Empfänger definieren
                    $ccRecipients = [];
                    $bccRecipients = [];

                    // Personalabteilung in BCC hinzufügen
                    $hrEmail = env('HR_EMAIL', 'personal@dittmeier.de');
                    if ($hrEmail) {
                        $bccRecipients[] = $hrEmail;
                    }

                    // Mail senden
                    $mail = Mail::to($employee->email);

                    // CC hinzufügen falls vorhanden
                    if (!empty($ccRecipients)) {
                        $mail->cc($ccRecipients);
                    }

                    // BCC hinzufügen falls vorhanden
                    if (!empty($bccRecipients)) {
                        $mail->bcc($bccRecipients);
                    }

                    $mail->send(new VacationApprovedMail(
                        $vacationRequest,
                        $employee,
                        $user
                    ));

                    Log::info('Genehmigungs-E-Mail erfolgreich gesendet an ' . $employee->email);
                } else {
                    Log::warning('Keine E-Mail-Adresse für den Mitarbeiter gefunden', [
                        'employee_id' => $employee->id
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Fehler beim Senden der Genehmigungs-E-Mail', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Wir werfen den Fehler nicht weiter, damit der Prozess fortgesetzt werden kann
            }

            // Erfolgreiche Antwort
            return response()->json([
                'message' => 'Urlaubsantrag erfolgreich genehmigt',
                'request' => $vacationRequest
            ]);
        } catch (\Exception $e) {
            Log::error('Fehler bei der Genehmigung des Urlaubsantrags', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Fehler bei der Genehmigung des Urlaubsantrags: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lehnt einen Urlaubsantrag ab
     */
    public function rejectRequest(Request $request, $id)
    {
        try {
            $user = Auth::user();

            // Validierung
            $request->validate([
                'reason' => 'nullable|string|max:255'
            ]);

            // Debug-Logging
            Log::info('Urlaubsantrag-Ablehnung empfangen', [
                'approver_id' => $user->id,
                'request_id' => $id,
                'reason' => $request->reason
            ]);

            // Urlaubsantrag finden
            $vacationRequest = VacationRequest::findOrFail($id);

            // Prüfen, ob der Antrag bereits bearbeitet wurde
            if ($vacationRequest->status !== 'pending') {
                return response()->json([
                    'error' => 'Dieser Urlaubsantrag wurde bereits bearbeitet.'
                ], 400);
            }

            // Antrag ablehnen
            $vacationRequest->status = 'rejected';
            $vacationRequest->rejected_by = $user->id;
            $vacationRequest->rejected_date = now();
            $vacationRequest->rejection_reason = $request->reason;

            // WICHTIG: Stelle sicher, dass alle anderen Felder unverändert bleiben
            // Wir müssen hier nichts explizit tun, da wir nur die oben genannten Felder aktualisieren
            // und die Methode save() nur die geänderten Felder aktualisiert

            $vacationRequest->save();

            // Antragsteller finden
            $employee = User::findOrFail($vacationRequest->user_id);

            // Benachrichtigung für den Antragsteller erstellen
            Notification::create([
                'user_id' => $employee->id,
                'title' => 'Urlaubsantrag abgelehnt',
                'message' => "Ihr Urlaubsantrag vom {$vacationRequest->start_date->format('d.m.Y')} bis {$vacationRequest->end_date->format('d.m.Y')} wurde abgelehnt.",
                'type' => 'error', // Ändere 'danger' zu 'error' oder einem anderen gültigen Wert
                'is_read' => false,
                'related_entity_type' => 'vacation_request',
                'related_entity_id' => $vacationRequest->id,
                'created_at' => now()
            ]);

            // E-Mail an den Antragsteller senden
            try {
                Log::info('Sende Ablehnungs-E-Mail an Antragsteller', [
                    'employee_id' => $employee->id,
                    'employee_email' => $employee->email,
                    'notes' => $vacationRequest->notes // Log der Anmerkungen
                ]);

                // Mail senden mit verbesserter Fehlerbehandlung
                if ($employee->email) {
                    // CC und BCC Empfänger definieren
                    $ccRecipients = [];
                    $bccRecipients = [];

                    // Personalabteilung in BCC hinzufügen
                    $hrEmail = env('HR_EMAIL', 'personal@dittmeier.de');
                    if ($hrEmail) {
                        $bccRecipients[] = $hrEmail;
                    }

                    // Mail senden
                    $mail = Mail::to($employee->email);

                    // CC hinzufügen falls vorhanden
                    if (!empty($ccRecipients)) {
                        $mail->cc($ccRecipients);
                    }

                    // BCC hinzufügen falls vorhanden
                    if (!empty($bccRecipients)) {
                        $mail->bcc($bccRecipients);
                    }

                    $mail->send(new VacationRejectedMail(
                        $vacationRequest,
                        $employee,
                        $request->reason,
                        $user
                    ));

                    Log::info('Ablehnungs-E-Mail erfolgreich gesendet an ' . $employee->email);
                } else {
                    Log::warning('Keine E-Mail-Adresse für den Mitarbeiter gefunden', [
                        'employee_id' => $employee->id
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Fehler beim Senden der Ablehnungs-E-Mail', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Wir werfen den Fehler nicht weiter, damit der Prozess fortgesetzt werden kann
            }

            // Erfolgreiche Antwort
            return response()->json([
                'message' => 'Urlaubsantrag erfolgreich abgelehnt',
                'request' => $vacationRequest
            ]);
        } catch (\Exception $e) {
            Log::error('Fehler bei der Ablehnung des Urlaubsantrags', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Fehler bei der Ablehnung des Urlaubsantrags: ' . $e->getMessage()
            ], 500);
        }
    }


    private function updateVacationBalance(VacationRequest $vacationRequest)
    {
        // Nur für genehmigte Anträge
        if ($vacationRequest->status !== 'approved') {
            return;
        }

        // Jahr des Urlaubsantrags bestimmen
        $year = $vacationRequest->start_date->year;

        // Finde oder erstelle die Urlaubsbilanz für dieses Jahr
        $balance = VacationBalance::firstOrCreate(
            ['user_id' => $vacationRequest->user_id, 'year' => $year],
            ['total_days' => $vacationRequest->user->vacation_days_per_year, 'used_days' => 0]
        );

        // Aktualisiere die genutzten Tage
        $balance->used_days += $vacationRequest->days;
        $balance->save();

        Log::info('Urlaubsbilanz aktualisiert', [
            'user_id' => $vacationRequest->user_id,
            'year' => $year,
            'used_days' => $balance->used_days,
            'vacation_request_id' => $vacationRequest->id
        ]);
    }

    /**
     * Cancel a vacation request
     */
    public function cancelRequest($id)
    {
        try {
            $user = Auth::user();

            // Urlaubsantrag laden
            $vacationRequest = VacationRequest::where('user_id', $user->id)
                ->where('id', $id)
                ->where('status', 'pending') // Nur ausstehende Anträge können zurückgezogen werden
                ->firstOrFail();

            // Antrag löschen
            $vacationRequest->delete();

            return response()->json([
                'message' => 'Urlaubsantrag erfolgreich zurückgezogen'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
