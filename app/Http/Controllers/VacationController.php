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
     * KORRIGIERT für neue Übertrag-Felder
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
                    'used_days' => 0,
                    'carry_over_days' => 0,
                    'carry_over_used' => 0,
                    'carry_over_expires_at' => Carbon::create($currentYear, 7, 31), // 31. Juli
                    'max_carry_over' => 10
                ]);
            }

            // Verwende die neuen Datenbankfelder für Übertragstage
            $carryOver = $vacationBalance->carry_over_days ?? 0;
            $carryOverUsed = $vacationBalance->carry_over_used ?? 0;
            $carryOverExpires = $vacationBalance->carry_over_expires_at;

            // Berechne geplante Tage (genehmigte, aber noch nicht genommene Urlaubsanträge)
            $plannedDaysQuery = VacationRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('start_date', '>=', Carbon::now())
                ->get();

            $plannedDays = $plannedDaysQuery->sum(function($request) {
                return $this->calculateActualDays($request);
            });

            // Korrigierte Berechnung der verbleibenden Tage
            $baseEntitlement = $vacationBalance->total_days;
            $totalEntitlement = $baseEntitlement + $carryOver;

            // Verbleibende Tage = Gesamtkontingent - Bereits genommene Tage
            $totalUsedDays = $vacationBalance->used_days + $carryOverUsed;
            $remainingDays = $totalEntitlement - $totalUsedDays;
            $remainingDays = max(0, $remainingDays);

            // NEU: Detaillierte Aufschlüsselung der verbleibenden Tage
            $remainingCarryOver = max(0, $carryOver - $carryOverUsed);
            $remainingRegular = max(0, $baseEntitlement - $vacationBalance->used_days);

            // ERWEITERTE Statistik mit Übertrag-Details
            $stats = [
                'baseEntitlement' => $baseEntitlement,
                'carryOver' => $carryOver,
                'carryOverUsed' => $carryOverUsed,
                'carryOverRemaining' => $remainingCarryOver, // NEU
                'carryOverExpires' => $carryOverExpires ? $carryOverExpires->format('Y-m-d') : null,
                'totalAvailable' => $totalEntitlement,
                'total' => $totalEntitlement, // Für Rückwärtskompatibilität
                'used' => $totalUsedDays, // NEU: Gesamte verwendete Tage (regulär + Übertrag)
                'usedRegular' => $vacationBalance->used_days, // NEU: Nur reguläre verwendete Tage
                'usedCarryOver' => $carryOverUsed, // NEU: Nur verwendete Übertragstage
                'planned' => $plannedDays,
                'remaining' => $remainingDays,
                'remainingRegular' => $remainingRegular, // NEU: Verbleibende reguläre Tage
                'remainingCarryOver' => $remainingCarryOver // NEU: Verbleibende Übertragstage
            ];

            // Rest der Methode bleibt gleich...
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
                        'dayType' => $request->day_type ?? 'full_day',
                        'dayTypeLabel' => $this->getDayTypeLabel($request->day_type ?? 'full_day'),
                        'actualDays' => $this->calculateActualDays($request),
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
                        'end' => $request->end_date->format('Y-m-d'),
                        'userId' => $request->user_id,
                        'dayType' => $request->day_type ?? 'full_day',
                        'actualDays' => $this->calculateActualDays($request)
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

            // Urlaubshistorie für die letzten Jahre - KORRIGIERT
            $history = [];
            $startYear = $currentYear - 5;

            for ($year = $startYear; $year <= $currentYear; $year++) {
                $balance = VacationBalance::where('user_id', $user->id)
                    ->where('year', $year)
                    ->first();

                if ($balance) {
                    $yearCarryOver = $balance->carry_over_days ?? 0;
                    $yearCarryOverUsed = $balance->carry_over_used ?? 0;

                    $carryOverToNextYear = 0;
                    if ($year < $currentYear) {
                        $totalYearUsed = $balance->used_days + $yearCarryOverUsed;
                        $carryOverToNextYear = min(10, $balance->total_days + $yearCarryOver - $totalYearUsed);
                    } else {
                        $carryOverToNextYear = min(10, $remainingDays);
                    }

                    $history[] = [
                        'year' => $year,
                        'baseEntitlement' => $balance->total_days,
                        'carryOver' => $yearCarryOver,
                        'carryOverUsed' => $yearCarryOverUsed,
                        'totalEntitlement' => $balance->total_days + $yearCarryOver,
                        'used' => $balance->used_days + $yearCarryOverUsed, // Gesamte verwendete Tage
                        'usedRegular' => $balance->used_days, // Nur reguläre Tage
                        'usedCarryOver' => $yearCarryOverUsed, // Nur Übertragstage
                        'remaining' => $balance->total_days + $yearCarryOver - ($balance->used_days + $yearCarryOverUsed),
                        'carryOverToNextYear' => $carryOverToNextYear
                    ];
                }
            }

            // Rest der Methode bleibt unverändert...
            $yearlyStats = [];
            $yearVacationDetails = [];
            $monthlyStats = [];

            foreach ($history as $yearData) {
                $year = $yearData['year'];

                $yearRequests = VacationRequest::where('user_id', $user->id)
                    ->whereYear('start_date', $year)
                    ->with(['substitute', 'approver', 'rejector'])
                    ->orderBy('start_date')
                    ->get();

                $monthlyData = array_fill(0, 12, 0);

                $details = [];
                foreach ($yearRequests as $request) {
                    $actualDays = $this->calculateActualDays($request);

                    $details[] = [
                        'period' => $request->start_date->format('d.m.Y') . ' - ' . $request->end_date->format('d.m.Y'),
                        'days' => $request->days,
                        'dayType' => $request->day_type ?? 'full_day',
                        'dayTypeLabel' => $this->getDayTypeLabel($request->day_type ?? 'full_day'),
                        'actualDays' => $actualDays,
                        'status' => $request->status,
                        'requestDate' => $request->created_at->format('d.m.Y'),
                        'notes' => $request->notes
                    ];

                    if ($request->status === 'approved') {
                        $startMonth = $request->start_date->month - 1;
                        $endMonth = $request->end_date->month - 1;

                        if ($startMonth === $endMonth) {
                            $monthlyData[$startMonth] += $actualDays;
                        } else {
                            $currentDate = $request->start_date->copy();
                            while ($currentDate->lte($request->end_date)) {
                                $dayOfWeek = $currentDate->dayOfWeek;
                                if ($dayOfWeek !== 0 && $dayOfWeek !== 6) {
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
                'monthlyStats' => $monthlyStats,
                'userId' => $user->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getUserData: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get yearly vacation data
     * KORRIGIERT für neue Übertrag-Felder
     */
    public function getYearlyVacationData($year)
    {
        try {
            $user = Auth::user();

            $vacationBalance = VacationBalance::where('user_id', $user->id)
                ->where('year', $year)
                ->first();

            if (!$vacationBalance) {
                return response()->json([
                    'stats' => [
                        'baseEntitlement' => $user->vacation_days_per_year,
                        'carryOver' => 0,
                        'carryOverUsed' => 0,
                        'totalEntitlement' => $user->vacation_days_per_year,
                        'used' => 0,
                        'planned' => 0,
                        'remaining' => $user->vacation_days_per_year
                    ],
                    'details' => []
                ]);
            }

            // KORRIGIERT: Verwende die neuen Datenbankfelder
            $carryOver = $vacationBalance->carry_over_days ?? 0;
            $carryOverUsed = $vacationBalance->carry_over_used ?? 0;

            // Berechne geplante Tage für das angegebene Jahr
            $plannedDays = 0;
            $currentYear = Carbon::now()->year;

            if ($year === $currentYear) {
                $plannedRequests = VacationRequest::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->where('start_date', '>=', Carbon::now())
                    ->get();

                $plannedDays = $plannedRequests->sum(function($request) {
                    return $this->calculateActualDays($request);
                });
            }

            $totalEntitlement = $vacationBalance->total_days + $carryOver;
            $remainingDays = $totalEntitlement - $vacationBalance->used_days;
            $remainingDays = max(0, $remainingDays);

            $stats = [
                'baseEntitlement' => $vacationBalance->total_days,
                'carryOver' => $carryOver,
                'carryOverUsed' => $carryOverUsed,
                'totalEntitlement' => $totalEntitlement,
                'used' => $vacationBalance->used_days,
                'planned' => $plannedDays,
                'remaining' => $remainingDays
            ];

            // Rest der Methode bleibt gleich...
            $yearRequests = VacationRequest::where('user_id', $user->id)
                ->whereYear('start_date', $year)
                ->with(['substitute', 'approver', 'rejector'])
                ->orderBy('start_date')
                ->get();

            $details = [];
            foreach ($yearRequests as $request) {
                $details[] = [
                    'period' => $request->start_date->format('d.m.Y') . ' - ' . $request->end_date->format('d.m.Y'),
                    'days' => $request->days,
                    'dayType' => $request->day_type ?? 'full_day',
                    'dayTypeLabel' => $this->getDayTypeLabel($request->day_type ?? 'full_day'),
                    'actualDays' => $this->calculateActualDays($request),
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
            Log::error('Error in getYearlyVacationData: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get vacation requests for management
     * ERWEITERT für Mentor-System und Halbtags-Feature
     */
    public function getRequests()
    {
        try {
            $user = Auth::user();
            $role = $user->role ? $user->role->name : null;

            Log::info('Getting vacation requests for user', [
                'user_id' => $user->id,
                'role' => $role,
                'is_apprentice' => $user->is_apprentice ?? false,
                'mentor_id' => $user->mentor_id ?? null,
                'apprentices_count' => $user->apprentices->count()
            ]);

            // Verwende die neue Methode für zu genehmigende Anträge
            $requestsToApprove = $user->vacation_requests_to_approve;

            Log::info('Requests to approve from attribute', [
                'count' => $requestsToApprove->count()
            ]);

            // Wenn der User keine Anträge zu genehmigen hat und kein HR/Admin ist
            if ($requestsToApprove->isEmpty() && !in_array($role, ['HR', 'Admin', 'Personal'])) {
                // Zeige Anträge, bei denen er als Vertreter eingetragen ist
                $requestsToApprove = VacationRequest::where('substitute_id', $user->id)
                    ->where('status', 'pending')
                    ->with(['user', 'user.currentTeam', 'substitute'])
                    ->get();

                Log::info('Substitute requests found', [
                    'count' => $requestsToApprove->count()
                ]);
            }

            // Für HR und Admin: Alle Anträge anzeigen
            if (in_array($role, ['HR', 'Admin', 'Personal'])) {
                $allRequests = VacationRequest::with(['user', 'user.currentTeam', 'substitute'])
                    ->orderBy('created_at', 'desc')
                    ->get();

                Log::info('HR/Admin - showing all requests', [
                    'count' => $allRequests->count()
                ]);
            } else {
                // Für andere: Nur relevante Anträge
                $allRequests = $requestsToApprove;
            }

            $formattedRequests = $allRequests->map(function ($request) {
                return [
                    'id' => $request->id,
                    'employee' => [
                        'id' => $request->user->id,
                        'name' => $request->user->full_name,
                        'is_apprentice' => $request->user->is_apprentice ?? false
                    ],
                    'department' => $request->user->currentTeam ? $request->user->currentTeam->name : 'Keine Abteilung',
                    'startDate' => $request->start_date->format('Y-m-d'),
                    'endDate' => $request->end_date->format('Y-m-d'),
                    'days' => $request->days,
                    'dayType' => $request->day_type ?? 'full_day', // NEU
                    'dayTypeLabel' => $this->getDayTypeLabel($request->day_type ?? 'full_day'), // NEU
                    'actualDays' => $this->calculateActualDays($request), // NEU
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
            $pending = $formattedRequests->where('status', 'pending')->values();
            $approved = $formattedRequests->where('status', 'approved')->values();
            $rejected = $formattedRequests->where('status', 'rejected')->values();

            return response()->json([
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getRequests: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get all vacation requests for calendar
     * ERWEITERT für Halbtags-Feature
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
                        'dayType' => $request->day_type ?? 'full_day', // NEU
                        'dayTypeLabel' => $this->getDayTypeLabel($request->day_type ?? 'full_day'), // NEU
                        'actualDays' => $this->calculateActualDays($request), // NEU
                        'notes' => $request->notes,
                        'status' => $request->status,
                        'employee_name' => $request->user->full_name,
                        'department' => $request->user->currentTeam ? $request->currentTeam->name : 'Keine Abteilung'
                    ];
                });

            return response()->json($requests);
        } catch (\Exception $e) {
            Log::error('Error in getAllRequests: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get vacation requests for the current user
     * ERWEITERT für Halbtags-Feature
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
                        'dayType' => $request->day_type ?? 'full_day', // NEU
                        'dayTypeLabel' => $this->getDayTypeLabel($request->day_type ?? 'full_day'), // NEU
                        'actualDays' => $this->calculateActualDays($request), // NEU
                        'notes' => $request->notes,
                        'status' => $request->status
                    ];
                });

            return response()->json($requests);
        } catch (\Exception $e) {
            Log::error('Error in getUserRequests: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Submit a vacation request
     * ERWEITERT für Mentor-System und Halbtags-Feature
     */
    public function submitRequest(Request $request)
    {
        try {
            $user = Auth::user();

            // Debug-Informationen
            Log::info('Urlaubsantrag empfangen', [
                'user' => $user->id,
                'is_apprentice' => $user->is_apprentice ?? false,
                'mentor_id' => $user->mentor_id ?? null,
                'request_data' => $request->all()
            ]);

            // Validierung ERWEITERT für Halbtags-Feature
            $request->validate([
                'periods' => 'required|array',
                'periods.*.startDate' => 'required|date',
                'periods.*.endDate' => 'required|date|after_or_equal:periods.*.startDate',
                'periods.*.days' => 'required|numeric|min:0.5', // GEÄNDERT: Erlaubt 0.5 für Halbtage
                'periods.*.dayType' => 'nullable|in:full_day,morning,afternoon', // NEU
                'substitute' => 'nullable|integer',
                'notes' => 'nullable|string'
            ]);

            $teamId = $user->currentTeam ? $user->currentTeam->id : null;

            // Erstelle für jeden Zeitraum einen separaten Urlaubsantrag
            $createdRequests = [];

            foreach ($request->periods as $period) {
                $vacationRequest = new VacationRequest();
                $vacationRequest->user_id = $user->id;
                $vacationRequest->team_id = $teamId;
                $vacationRequest->substitute_id = $request->substitute;
                $vacationRequest->notes = $request->notes;
                $vacationRequest->status = 'pending';

                // Setze Start- und Enddatum für diesen Zeitraum
                $startDate = Carbon::parse($period['startDate'])->startOfDay();
                $endDate = Carbon::parse($period['endDate'])->startOfDay();

                $vacationRequest->start_date = $startDate;
                $vacationRequest->end_date = $endDate;
                $vacationRequest->days = $period['days'];
                $vacationRequest->day_type = $period['dayType'] ?? 'full_day'; // NEU

                $vacationRequest->save();

                Log::info('Urlaubsantrag erstellt', [
                    'request_id' => $vacationRequest->id,
                    'start_date' => $vacationRequest->start_date->format('Y-m-d'),
                    'end_date' => $vacationRequest->end_date->format('Y-m-d'),
                    'days' => $vacationRequest->days,
                    'day_type' => $vacationRequest->day_type, // NEU
                    'actual_days' => $this->calculateActualDays($vacationRequest) // NEU
                ]);

                $createdRequests[] = $vacationRequest;
            }

            // Lade den ersten Urlaubsantrag mit allen Beziehungen für die E-Mail
            $firstRequest = $createdRequests[0];
            $firstRequest->load(['user', 'substitute']);

            // Bestimme den Genehmiger basierend auf dem neuen System
            $approver = $user->vacation_approver;

            Log::info('Genehmiger bestimmt', [
                'approver_id' => $approver ? $approver->id : null,
                'approver_name' => $approver ? $approver->full_name : null,
                'approver_type' => $user->is_apprentice ? 'Mentor' : 'Abteilungsleiter'
            ]);

            // Fallback: Wenn kein Genehmiger gefunden wurde, sende an HR oder Admin
            if (!$approver) {
                $approver = User::whereHas('role', function($query) {
                    $query->where('name', 'HR')->orWhere('name', 'Personal')->orWhere('name', 'Admin');
                })->first();

                Log::info('Fallback-Genehmiger gefunden', [
                    'fallback_approver_id' => $approver ? $approver->id : null
                ]);
            }

            // Sende eine E-Mail an den Genehmiger
            if ($approver) {
                try {
                    Log::info('Sende E-Mail an Genehmiger', [
                        'approver_id' => $approver->id,
                        'approver_email' => $approver->email,
                        'approver_type' => $user->is_apprentice ? 'Mentor' : 'Abteilungsleiter'
                    ]);

                    // Finde überlappende Urlaubsanträge
                    $overlappingRequests = $this->getOverlappingRequests($firstRequest);

                    // CC-Empfänger definieren
                    $ccRecipients = [];

                    // Wenn ein Vertreter ausgewählt wurde, füge ihn als CC-Empfänger hinzu
                    if ($firstRequest->substitute) {
                        $ccRecipients[] = $firstRequest->substitute->email;
                        Log::info('Vertreter als CC hinzugefügt', [
                            'substitute_id' => $firstRequest->substitute->id,
                            'substitute_email' => $firstRequest->substitute->email
                        ]);
                    }

                    // Sende eine E-Mail mit allen Zeiträumen
                    $mail = Mail::to($approver->email);

                    // CC hinzufügen falls vorhanden
                    if (!empty($ccRecipients)) {
                        $mail->cc($ccRecipients);
                    }

                    $mail->send(new VacationRequestMail(
                        $firstRequest,
                        $user,
                        $overlappingRequests,
                        $firstRequest->substitute,
                        $createdRequests
                    ));

                    Log::info('E-Mail erfolgreich gesendet');
                } catch (\Exception $e) {
                    Log::error('Fehler beim Senden der E-Mail', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }

            // Erfolgreiche Antwort
            return response()->json([
                'message' => 'Urlaubsantrag erfolgreich eingereicht',
                'requests' => $createdRequests,
                'approver_type' => $user->is_apprentice ? 'Mentor' : 'Abteilungsleiter'
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
     * ERWEITERT für Mentor-System und Halbtags-Feature
     */
    public function approveRequest(Request $request, $id)
    {
        try {
            $user = Auth::user();

            Log::info('Urlaubsantrag-Genehmigung empfangen', [
                'approver_id' => $user->id,
                'request_id' => $id,
                'is_mentor' => $user->apprentices->count() > 0
            ]);

            // Urlaubsantrag finden
            $vacationRequest = VacationRequest::findOrFail($id);

            // Prüfen, ob der Antrag bereits bearbeitet wurde
            if ($vacationRequest->status !== 'pending') {
                return response()->json([
                    'error' => 'Dieser Urlaubsantrag wurde bereits bearbeitet.'
                ], 400);
            }

            // Prüfen, ob der Benutzer berechtigt ist, den Antrag zu genehmigen
            $isAuthorized = $this->canApproveRequest($user, $vacationRequest);

            if (!$isAuthorized) {
                return response()->json([
                    'error' => 'Sie sind nicht berechtigt, diesen Urlaubsantrag zu genehmigen.'
                ], 403);
            }

            // Antrag genehmigen
            $vacationRequest->status = 'approved';
            $vacationRequest->approved_by = $user->id;
            $vacationRequest->approved_date = now();
            $vacationRequest->save();

            // WICHTIG: Aktualisiere die Urlaubsbilanz mit korrekter Halbtags-Berechnung
            $this->updateVacationBalance($vacationRequest);

            // Antragsteller finden
            $employee = User::findOrFail($vacationRequest->user_id);

            // Benachrichtigung für den Antragsteller erstellen
            $actualDays = $this->calculateActualDays($vacationRequest);
            $dayTypeText = $this->getDayTypeLabel($vacationRequest->day_type ?? 'full_day');

            Notification::create([
                'user_id' => $employee->id,
                'title' => 'Urlaubsantrag genehmigt',
                'message' => "Ihr Urlaubsantrag vom {$vacationRequest->start_date->format('d.m.Y')} bis {$vacationRequest->end_date->format('d.m.Y')} ({$dayTypeText}, {$actualDays} Tag" . ($actualDays != 1 ? 'e' : '') . ") wurde genehmigt.",
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
                    'notes' => $vacationRequest->notes,
                    'day_type' => $vacationRequest->day_type,
                    'actual_days' => $actualDays
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

                    // Wenn der Genehmiger ein Vertreter ist, füge den Abteilungsleiter als CC hinzu
                    if ($vacationRequest->substitute_id === $user->id) {
                        // Finde den Abteilungsleiter
                        $departmentHead = null;
                        if ($employee->currentTeam) {
                            $departmentHead = User::whereHas('teams', function($query) use ($employee) {
                                $query->where('teams.id', $employee->currentTeam->id);
                            })
                                ->whereHas('role', function($query) {
                                    $query->where('name', 'Abteilungsleiter');
                                })
                                ->first();
                        }

                        if ($departmentHead) {
                            $ccRecipients[] = $departmentHead->email;
                            Log::info('Abteilungsleiter als CC hinzugefügt', [
                                'department_head_id' => $departmentHead->id,
                                'department_head_email' => $departmentHead->email
                            ]);
                        }
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
     * ERWEITERT für Halbtags-Feature
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

            // Prüfen, ob der Benutzer berechtigt ist, den Antrag abzulehnen
            $isAuthorized = $this->canApproveRequest($user, $vacationRequest);

            if (!$isAuthorized) {
                return response()->json([
                    'error' => 'Sie sind nicht berechtigt, diesen Urlaubsantrag abzulehnen.'
                ], 403);
            }

            // Antrag ablehnen
            $vacationRequest->status = 'rejected';
            $vacationRequest->rejected_by = $user->id;
            $vacationRequest->rejected_date = now();
            $vacationRequest->rejection_reason = $request->reason;
            $vacationRequest->save();

            // Antragsteller finden
            $employee = User::findOrFail($vacationRequest->user_id);

            // Benachrichtigung für den Antragsteller erstellen
            $actualDays = $this->calculateActualDays($vacationRequest);
            $dayTypeText = $this->getDayTypeLabel($vacationRequest->day_type ?? 'full_day');

            Notification::create([
                'user_id' => $employee->id,
                'title' => 'Urlaubsantrag abgelehnt',
                'message' => "Ihr Urlaubsantrag vom {$vacationRequest->start_date->format('d.m.Y')} bis {$vacationRequest->end_date->format('d.m.Y')} ({$dayTypeText}, {$actualDays} Tag" . ($actualDays != 1 ? 'e' : '') . ") wurde abgelehnt.",
                'type' => 'error',
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
                    'notes' => $vacationRequest->notes,
                    'day_type' => $vacationRequest->day_type,
                    'actual_days' => $actualDays
                ]);

                if ($employee->email) {
                    // CC und BCC Empfänger definieren
                    $ccRecipients = [];
                    $bccRecipients = [];

                    // Personalabteilung in BCC hinzufügen
                    $hrEmail = env('HR_EMAIL', 'personal@dittmeier.de');
                    if ($hrEmail) {
                        $bccRecipients[] = $hrEmail;
                    }

                    // Wenn der Ablehner ein Vertreter ist, füge den Abteilungsleiter als CC hinzu
                    if ($vacationRequest->substitute_id === $user->id) {
                        // Finde den Abteilungsleiter
                        $departmentHead = null;
                        if ($employee->currentTeam) {
                            $departmentHead = User::whereHas('teams', function($query) use ($employee) {
                                $query->where('teams.id', $employee->currentTeam->id);
                            })
                                ->whereHas('role', function($query) {
                                    $query->where('name', 'Abteilungsleiter');
                                })
                                ->first();
                        }

                        if ($departmentHead) {
                            $ccRecipients[] = $departmentHead->email;
                            Log::info('Abteilungsleiter als CC hinzugefügt', [
                                'department_head_id' => $departmentHead->id,
                                'department_head_email' => $departmentHead->email
                            ]);
                        }
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

    /**
     * Prüfe ob User berechtigt ist, den Antrag zu genehmigen
     * NEU für Mentor-System
     */
    private function canApproveRequest($approver, $vacationRequest)
    {
        $employee = $vacationRequest->user;
        $role = $approver->role ? $approver->role->name : null;

        // HR und Admin dürfen immer genehmigen
        if (in_array($role, ['HR', 'Admin', 'Personal'])) {
            return true;
        }

        // Ist der Mitarbeiter ein Azubi und der Approver sein Mentor?
        if ($employee->is_apprentice && $employee->mentor_id === $approver->id) {
            return true;
        }

        // Ist der Approver Abteilungsleiter des Mitarbeiters?
        if (!$employee->is_apprentice &&
            $role === 'Abteilungsleiter' &&
            $approver->current_team_id === $employee->current_team_id) {
            return true;
        }

        // Ist der Approver als Vertreter eingetragen?
        if ($vacationRequest->substitute_id === $approver->id) {
            return true;
        }

        return false;
    }

    /**
     * Aktualisiere die Urlaubsbilanz
     * ERWEITERT für Halbtags-Feature
     */
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
            [
                'total_days' => $vacationRequest->user->vacation_days_per_year,
                'used_days' => 0,
                'carry_over_days' => 0,
                'carry_over_used' => 0,
                'carry_over_expires_at' => Carbon::create($year, 7, 31), // 31. juli
                'max_carry_over' => 10
            ]
        );

        // WICHTIG: Verwende calculateActualDays() für korrekte Halbtags-Berechnung
        $actualDays = $this->calculateActualDays($vacationRequest);

        // NEUE LOGIK: Übertragstage zuerst verbrauchen (FIFO)
        $remainingDaysToDeduct = $actualDays;

        // 1. Zuerst übertragene Tage verbrauchen
        $availableCarryOverDays = $balance->carry_over_days - $balance->carry_over_used;

        if ($availableCarryOverDays > 0 && $remainingDaysToDeduct > 0) {
            $carryOverToUse = min($availableCarryOverDays, $remainingDaysToDeduct);
            $balance->carry_over_used += $carryOverToUse;
            $remainingDaysToDeduct -= $carryOverToUse;

            Log::info('Übertragstage verwendet', [
                'user_id' => $vacationRequest->user_id,
                'year' => $year,
                'carry_over_used' => $carryOverToUse,
                'total_carry_over_used' => $balance->carry_over_used,
                'remaining_carry_over' => $balance->carry_over_days - $balance->carry_over_used,
                'vacation_request_id' => $vacationRequest->id
            ]);
        }

        // 2. Dann reguläre Urlaubstage des aktuellen Jahres verbrauchen
        if ($remainingDaysToDeduct > 0) {
            $balance->used_days += $remainingDaysToDeduct;

            Log::info('Reguläre Urlaubstage verwendet', [
                'user_id' => $vacationRequest->user_id,
                'year' => $year,
                'regular_days_used' => $remainingDaysToDeduct,
                'total_used_days' => $balance->used_days,
                'vacation_request_id' => $vacationRequest->id
            ]);
        }

        $balance->save();

        Log::info('Urlaubsbilanz aktualisiert mit FIFO-Logik', [
            'user_id' => $vacationRequest->user_id,
            'year' => $year,
            'actual_days_total' => $actualDays,
            'carry_over_used_this_request' => min($availableCarryOverDays, $actualDays),
            'regular_days_used_this_request' => max(0, $actualDays - $availableCarryOverDays),
            'total_carry_over_used' => $balance->carry_over_used,
            'total_used_days' => $balance->used_days,
            'day_type' => $vacationRequest->day_type ?? 'full_day',
            'vacation_request_id' => $vacationRequest->id
        ]);
    }

    public function cancelApprovedRequest($id)
    {
        try {
            $user = Auth::user();

            // Urlaubsantrag laden - nur genehmigte Anträge können storniert werden
            $vacationRequest = VacationRequest::where('user_id', $user->id)
                ->where('id', $id)
                ->where('status', 'approved')
                ->firstOrFail();

            // Prüfen, ob der Urlaub bereits begonnen hat
            if ($vacationRequest->start_date <= Carbon::now()) {
                return response()->json([
                    'error' => 'Urlaubsanträge, die bereits begonnen haben, können nicht mehr storniert werden.'
                ], 400);
            }

            // Urlaubstage zurückgeben (umgekehrte FIFO-Logik)
            $this->refundVacationDays($vacationRequest);

            // Status auf storniert setzen (anstatt zu löschen)
            $vacationRequest->status = 'cancelled';
            $vacationRequest->save();

            return response()->json([
                'message' => 'Urlaubsantrag erfolgreich storniert und Urlaubstage zurückgegeben'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in cancelApprovedRequest: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function refundVacationDays(VacationRequest $vacationRequest)
    {
        $year = $vacationRequest->start_date->year;

        $balance = VacationBalance::where('user_id', $vacationRequest->user_id)
            ->where('year', $year)
            ->first();

        if (!$balance) {
            Log::warning('Keine Urlaubsbilanz gefunden für Rückerstattung', [
                'user_id' => $vacationRequest->user_id,
                'year' => $year,
                'vacation_request_id' => $vacationRequest->id
            ]);
            return;
        }

        $actualDays = $this->calculateActualDays($vacationRequest);
        $remainingDaysToRefund = $actualDays;

        // LIFO-Logik: Zuletzt verwendete Tage zuerst zurückgeben
        // 1. Zuerst reguläre Tage zurückgeben (falls welche verwendet wurden)
        if ($balance->used_days > 0 && $remainingDaysToRefund > 0) {
            $regularDaysToRefund = min($balance->used_days, $remainingDaysToRefund);
            $balance->used_days -= $regularDaysToRefund;
            $remainingDaysToRefund -= $regularDaysToRefund;

            Log::info('Reguläre Urlaubstage zurückgegeben', [
                'user_id' => $vacationRequest->user_id,
                'year' => $year,
                'regular_days_refunded' => $regularDaysToRefund,
                'total_used_days' => $balance->used_days,
                'vacation_request_id' => $vacationRequest->id
            ]);
        }

        // 2. Dann übertragene Tage zurückgeben (falls welche verwendet wurden)
        if ($balance->carry_over_used > 0 && $remainingDaysToRefund > 0) {
            $carryOverToRefund = min($balance->carry_over_used, $remainingDaysToRefund);
            $balance->carry_over_used -= $carryOverToRefund;
            $remainingDaysToRefund -= $carryOverToRefund;

            Log::info('Übertragstage zurückgegeben', [
                'user_id' => $vacationRequest->user_id,
                'year' => $year,
                'carry_over_refunded' => $carryOverToRefund,
                'total_carry_over_used' => $balance->carry_over_used,
                'vacation_request_id' => $vacationRequest->id
            ]);
        }

        $balance->save();

        Log::info('Urlaubstage erfolgreich zurückgegeben', [
            'user_id' => $vacationRequest->user_id,
            'year' => $year,
            'total_days_refunded' => $actualDays,
            'final_used_days' => $balance->used_days,
            'final_carry_over_used' => $balance->carry_over_used,
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
            Log::error('Error in cancelRequest: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Alle HR-Methoden bleiben unverändert, da sie bereits korrekt funktionieren
    public function getHROverview()
    {
        try {
            // Prüfen, ob der Benutzer HR oder Admin ist
            $user = Auth::user();
            $role = $user->role ? $user->role->name : null;

            if (!in_array($role, ['HR', 'Admin', 'Personal'])) {
                return response()->json(['error' => 'Nicht autorisiert'], 403);
            }

            $currentYear = Carbon::now()->year;
            $previousYear = $currentYear - 1;
            $currentMonth = Carbon::now()->month;

            // Alle aktiven Benutzer laden
            $users = User::where('is_active', true)
                ->with(['vacationBalances' => function($query) use ($currentYear, $previousYear) {
                    $query->whereIn('year', [$previousYear, $currentYear]);
                }])
                ->get();

            $overviewData = [];

            foreach ($users as $user) {
                // Urlaubskontingent für das aktuelle Jahr
                $currentYearBalance = $user->vacationBalances->where('year', $currentYear)->first();
                $previousYearBalance = $user->vacationBalances->where('year', $previousYear)->first();

                // Wenn keine Bilanz für das aktuelle Jahr existiert, erstellen wir einen Standardwert
                if (!$currentYearBalance) {
                    $currentYearBalance = new VacationBalance([
                        'total_days' => $user->vacation_days_per_year,
                        'used_days' => 0
                    ]);
                }

                // Wenn keine Bilanz für das Vorjahr existiert, erstellen wir einen Standardwert
                if (!$previousYearBalance) {
                    $previousYearBalance = new VacationBalance([
                        'total_days' => $user->vacation_days_per_year,
                        'used_days' => $user->vacation_days_per_year // Alle Tage verbraucht, keine Resttage
                    ]);
                }

                // Berechne Resttage aus dem Vorjahr (maximal 10)
                $carryOverFromPreviousYear = max(0, min(10, $previousYearBalance->total_days - $previousYearBalance->used_days));

                // Gesamtanspruch für das aktuelle Jahr
                $totalEntitlement = $currentYearBalance->total_days + $carryOverFromPreviousYear;

                // Monatliche Urlaubsanträge für das aktuelle Jahr laden
                $monthlyUsage = [];
                $remainingDays = $totalEntitlement;

                // Genehmigte Urlaubsanträge für das aktuelle Jahr nach Monaten gruppieren
                $vacationRequests = VacationRequest::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->whereYear('start_date', $currentYear)
                    ->orderBy('start_date')
                    ->get();

                // Initialisiere die monatlichen Nutzungsdaten
                for ($month = 1; $month <= 12; $month++) {
                    $monthlyUsage[$month] = 0;
                }

                // Berechne die monatliche Nutzung
                foreach ($vacationRequests as $request) {
                    $startMonth = $request->start_date->month;
                    $endMonth = $request->end_date->month;
                    $actualDays = $this->calculateActualDays($request); // NEU: Verwende actualDays

                    if ($startMonth === $endMonth) {
                        // Wenn der Urlaub im selben Monat ist
                        $monthlyUsage[$startMonth] += $actualDays;
                    } else {
                        // Wenn der Urlaub über mehrere Monate geht, verteile die Tage
                        $currentDate = $request->start_date->copy();
                        while ($currentDate->lte($request->end_date)) {
                            // Zähle nur Werktage (Mo-Fr)
                            $dayOfWeek = $currentDate->dayOfWeek;
                            if ($dayOfWeek !== 0 && $dayOfWeek !== 6) { // Nicht Sonntag und nicht Samstag
                                $monthlyUsage[$currentDate->month]++;
                            }
                            $currentDate->addDay();
                        }
                    }
                }

                // Berechne die monatlichen Resttage
                $monthlyRemainingDays = [];
                $runningTotal = $totalEntitlement;

                for ($month = 1; $month <= 12; $month++) {
                    $runningTotal -= $monthlyUsage[$month];
                    $monthlyRemainingDays[$month] = $runningTotal;
                }

                // Nur Monate bis zum aktuellen Monat - 1 anzeigen (oder bis April, wenn wir im Mai sind)
                $displayMonths = min($currentMonth - 1, 4); // Maximal bis April anzeigen

                // Ab 1. Juni auch Mai anzeigen
                if ($currentMonth >= 6) {
                    $displayMonths = 5; // Bis Mai anzeigen
                }

                // Lade alle Urlaubsanträge des Benutzers für das aktuelle Jahr
                $allVacationRequests = VacationRequest::where('user_id', $user->id)
                    ->whereYear('start_date', $currentYear)
                    ->orWhere(function($query) use ($user, $currentYear) {
                        $query->where('user_id', $user->id)
                            ->whereYear('end_date', $currentYear);
                    })
                    ->with('approver') // Lade den Genehmiger mit
                    ->orderBy('start_date', 'desc')
                    ->get()
                    ->map(function($request) {
                        // Formatiere die Daten für die Frontend-Anzeige
                        return [
                            'id' => $request->id,
                            'start_date' => $request->start_date->format('Y-m-d'),
                            'end_date' => $request->end_date->format('Y-m-d'),
                            'days' => $request->days,
                            'dayType' => $request->day_type ?? 'full_day', // NEU
                            'dayTypeLabel' => $this->getDayTypeLabel($request->day_type ?? 'full_day'), // NEU
                            'actualDays' => $this->calculateActualDays($request), // NEU
                            'status' => $request->status,
                            'status_text' => $this->getStatusText($request->status),
                            'status_color' => $this->getStatusColor($request->status),
                            'reason' => $request->reason,
                            'comment' => $request->comment,
                            'approver' => $request->approver ? $request->approver->name : null,
                            'created_at' => $request->created_at->format('Y-m-d H:i'),
                            'updated_at' => $request->updated_at->format('Y-m-d H:i')
                        ];
                    });

                $userData = [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'department' => $user->currentTeam ? $user->currentTeam->name : 'Keine Abteilung',
                    'vacation_days_per_year' => $user->vacation_days_per_year,
                    'carry_over_previous_year' => $carryOverFromPreviousYear,
                    'total_entitlement' => $totalEntitlement,
                    'used_days_total' => $currentYearBalance->used_days,
                    'remaining_days_total' => $totalEntitlement - $currentYearBalance->used_days,
                    'monthly_remaining' => [],
                    'vacation_requests' => $allVacationRequests, // Neue Eigenschaft für Urlaubsanträge
                    'is_apprentice' => $user->is_apprentice ?? false, // NEU für Mentor-System
                    'mentor_name' => $user->mentor ? $user->mentor->full_name : null // NEU für Mentor-System
                ];

                // Füge die monatlichen Resttage hinzu
                for ($month = 1; $month <= $displayMonths; $month++) {
                    $userData['monthly_remaining']["month_$month"] = $monthlyRemainingDays[$month];
                }

                $overviewData[] = $userData;
            }

            return response()->json([
                'data' => $overviewData,
                'current_month' => $currentMonth,
                'display_months' => min($currentMonth - 1, 4) + (($currentMonth >= 6) ? 1 : 0) // Anzahl der anzuzeigenden Monate
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getHROverview: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getVacationInfoList()
    {
        try {
            // Prüfen, ob der Benutzer HR oder Admin ist
            $user = Auth::user();
            $role = $user->role ? $user->role->name : null;

            if (!in_array($role, ['HR', 'Admin', 'Personal'])) {
                return response()->json(['error' => 'Nicht autorisiert'], 403);
            }

            $currentYear = Carbon::now()->year;
            $previousYear = $currentYear - 1;
            $currentMonth = Carbon::now()->month;
            $previousMonth = $currentMonth > 1 ? $currentMonth - 1 : 12;
            $previousMonthYear = $previousMonth == 12 ? $previousYear : $currentYear;

            // Alle aktiven Benutzer laden
            $users = User::where('is_active', true)
                ->with(['vacationBalances' => function($query) use ($currentYear, $previousYear) {
                    $query->whereIn('year', [$previousYear, $currentYear]);
                }])
                ->get();

            $infoListData = [];

            foreach ($users as $user) {
                // Urlaubskontingent für das aktuelle Jahr
                $currentYearBalance = $user->vacationBalances->where('year', $currentYear)->first();
                $previousYearBalance = $user->vacationBalances->where('year', $previousYear)->first();

                // Wenn keine Bilanz für das aktuelle Jahr existiert, erstellen wir einen Standardwert
                if (!$currentYearBalance) {
                    $currentYearBalance = new VacationBalance([
                        'total_days' => $user->vacation_days_per_year,
                        'used_days' => 0
                    ]);
                }

                // Wenn keine Bilanz für das Vorjahr existiert, erstellen wir einen Standardwert
                if (!$previousYearBalance) {
                    $previousYearBalance = new VacationBalance([
                        'total_days' => $user->vacation_days_per_year,
                        'used_days' => $user->vacation_days_per_year // Alle Tage verbraucht, keine Resttage
                    ]);
                }

                // Berechne Resttage aus dem Vorjahr (maximal 10)
                $carryOverFromPreviousYear = max(0, min(10, $previousYearBalance->total_days - $previousYearBalance->used_days));

                // Gesamtanspruch für das aktuelle Jahr
                $totalEntitlement = $currentYearBalance->total_days + $carryOverFromPreviousYear;

                // Genehmigte Urlaubsanträge für das aktuelle Jahr - ERWEITERT für Halbtage
                $approvedRequests = VacationRequest::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->whereYear('start_date', $currentYear)
                    ->get();

                $approvedVacationDays = $approvedRequests->sum(function($request) {
                    return $this->calculateActualDays($request);
                });

                // Genehmigte Urlaubsanträge für den aktuellen Monat - ERWEITERT für Halbtage
                $currentMonthRequests = VacationRequest::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->whereYear('start_date', $currentYear)
                    ->whereMonth('start_date', $currentMonth)
                    ->get();

                $approvedVacationDaysCurrentMonth = $currentMonthRequests->sum(function($request) {
                    return $this->calculateActualDays($request);
                });

                // Berechne Resttage für das aktuelle Jahr
                $remainingDaysCurrentYear = $totalEntitlement - $approvedVacationDays;

                // Berechne Resttage für den Vormonat - ERWEITERT für Halbtage
                $previousMonthRequests = VacationRequest::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->whereYear('start_date', '<=', $previousMonthYear)
                    ->whereMonth('start_date', '<=', $previousMonth)
                    ->get();

                $approvedVacationDaysPreviousMonth = $previousMonthRequests->sum(function($request) {
                    return $this->calculateActualDays($request);
                });

                $remainingDaysPreviousMonth = $totalEntitlement - $approvedVacationDaysPreviousMonth;

                $userData = [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'personnel_number' => $user->employee_number ?? '-', // Personalnummer aus employee_number
                    'department' => $user->currentTeam ? $user->currentTeam->name : 'Keine Abteilung',
                    'vacation_days_per_year' => $user->vacation_days_per_year,
                    'carry_over_previous_year' => $carryOverFromPreviousYear,
                    'total_entitlement' => $totalEntitlement,
                    'approved_days_current_year' => $approvedVacationDays,
                    'remaining_days_current_year' => $remainingDaysCurrentYear,
                    'current_month_days' => $approvedVacationDaysCurrentMonth, // Anzahl der genehmigten Urlaubstage im aktuellen Monat
                    'current_month_name' => $this->getMonthName($currentMonth), // Nur für Anzeigezwecke
                    'remaining_days_previous_month' => $remainingDaysPreviousMonth,
                    'is_apprentice' => $user->is_apprentice ?? false, // NEU für Mentor-System
                    'mentor_name' => $user->mentor ? $user->mentor->full_name : null // NEU für Mentor-System
                ];

                $infoListData[] = $userData;
            }

            return response()->json([
                'data' => $infoListData,
                'current_month' => $currentMonth,
                'previous_month' => $previousMonth
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getVacationInfoList: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Hilfsmethode, um den Monatsnamen zu erhalten
     */
    private function getMonthName($month)
    {
        $months = [
            1 => 'Januar',
            2 => 'Februar',
            3 => 'März',
            4 => 'April',
            5 => 'Mai',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'August',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Dezember'
        ];

        return $months[$month] ?? '';
    }

    /**
     * Hilfsmethode, um den Statustext zu erhalten
     */
    private function getStatusText($status)
    {
        $statusMap = [
            'pending' => 'Ausstehend',
            'approved' => 'Genehmigt',
            'rejected' => 'Abgelehnt',
            'canceled' => 'Storniert'
        ];

        return $statusMap[$status] ?? $status;
    }

    /**
     * Hilfsmethode, um die Statusfarbe zu erhalten
     */
    private function getStatusColor($status)
    {
        $colorMap = [
            'pending' => 'blue',
            'approved' => 'green',
            'rejected' => 'red',
            'canceled' => 'gray'
        ];

        return $colorMap[$status] ?? 'gray';
    }

    /**
     * Finde überlappende Urlaubsanträge
     */
    private function getOverlappingRequests(VacationRequest $vacationRequest)
    {
        $user = $vacationRequest->user;
        $teamId = $user->currentTeam ? $user->currentTeam->id : null;

        if (!$teamId) {
            return collect();
        }

        // Finde alle genehmigten Urlaubsanträge im gleichen Team, die mit dem neuen Antrag überlappen
        $overlappingRequests = VacationRequest::with(['user'])
            ->where('status', 'approved')
            ->where('user_id', '!=', $user->id)
            ->where('team_id', $teamId)
            ->where(function($query) use ($vacationRequest) {
                $query->where(function($q) use ($vacationRequest) {
                    $q->where('start_date', '<=', $vacationRequest->end_date)
                        ->where('end_date', '>=', $vacationRequest->start_date);
                });
            })
            ->get();

        return $overlappingRequests->map(function($request) {
            return [
                'employee_name' => $request->user->full_name,
                'start_date' => $request->start_date->format('d.m.Y'),
                'end_date' => $request->end_date->format('d.m.Y'),
                'day_type' => $request->day_type ?? 'full_day', // NEU
                'day_type_label' => $this->getDayTypeLabel($request->day_type ?? 'full_day'), // NEU
                'actual_days' => $this->calculateActualDays($request) // NEU
            ];
        });
    }

    /**
     * NEU: Hilfsmethode zur Berechnung der tatsächlichen Urlaubstage
     * Berücksichtigt Halbtage korrekt
     */
    private function calculateActualDays(VacationRequest $request)
    {
        $dayType = $request->day_type ?? 'full_day';

        // Wenn es ein einzelner Tag ist und Halbtag gewählt wurde
        if ($request->start_date->eq($request->end_date) && in_array($dayType, ['morning', 'afternoon'])) {
            return 0.5;
        }

        // Ansonsten verwende die normale Tagesanzahl
        return $request->days;
    }

    /**
     * NEU: Hilfsmethode zur Rückgabe des deutschen Labels für den Urlaubstyp
     */
    private function getDayTypeLabel($dayType)
    {
        return match($dayType) {
            'morning' => 'Vormittag',
            'afternoon' => 'Nachmittag',
            'full_day' => 'Ganzer Tag',
            default => 'Ganzer Tag'
        };
    }
}
