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

            // Bereits gebuchte Urlaubstage - HIER IST DER FEHLER
            // Wir müssen die Benutzer-ID mit zurückgeben, damit das Frontend filtern kann
            $bookedDates = VacationRequest::where('status', 'approved')
                ->get()
                ->map(function ($request) {
                    return [
                        'start' => $request->start_date->format('Y-m-d'),
                        'end' => $request->end_date->format('Y-m-d'),
                        'userId' => $request->user_id // Benutzer-ID hinzufügen
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
                'monthlyStats' => $monthlyStats,
                'userId' => $user->id // Aktuelle Benutzer-ID zurückgeben
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
            Log::error('Error in getYearlyVacationData: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
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
            // Wenn der Benutzer ein normaler Mitarbeiter ist, zeige nur Anträge, bei denen er als Vertreter eingetragen ist
            elseif ($role !== 'HR' && $role !== 'Admin') {
                $query->where('substitute_id', $user->id);

                // Wenn keine Anträge gefunden werden, bei denen der Benutzer als Vertreter eingetragen ist,
                // gib leere Arrays zurück
                if ($query->count() === 0) {
                    return response()->json([
                        'pending' => [],
                        'approved' => [],
                        'rejected' => []
                    ]);
                }
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
            Log::error('Error in getRequests: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
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
            Log::error('Error in getAllRequests: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
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
            Log::error('Error in getUserRequests: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getHROverview()
    {
        try {
            // Prüfen, ob der Benutzer HR oder Admin ist
            $user = Auth::user();
            $role = $user->role ? $user->role->name : null;

            if (!in_array($role, ['HR', 'Admin'])) {
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

                    if ($startMonth === $endMonth) {
                        // Wenn der Urlaub im selben Monat ist
                        $monthlyUsage[$startMonth] += $request->days;
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
                    'vacation_requests' => $allVacationRequests // Neue Eigenschaft für Urlaubsanträge
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
     * Submit a vacation request
     */
    public function submitRequest(Request $request)
    {
        try {
            $user = Auth::user();

            // Debug-Informationen
            Log::info('Urlaubsantrag empfangen', [
                'user' => $user->id,
                'request_data' => $request->all()
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

                $vacationRequest->save();

                Log::info('Urlaubsantrag erstellt', [
                    'request_id' => $vacationRequest->id,
                    'start_date' => $vacationRequest->start_date->format('Y-m-d'),
                    'end_date' => $vacationRequest->end_date->format('Y-m-d'),
                    'days' => $vacationRequest->days
                ]);

                $createdRequests[] = $vacationRequest;
            }

            // Lade den ersten Urlaubsantrag mit allen Beziehungen für die E-Mail
            $firstRequest = $createdRequests[0];
            $firstRequest->load(['user', 'substitute']);

            // Finde den Abteilungsleiter
            $departmentHead = null;
            if ($user->currentTeam) {
                // FIXED: Use the role_id column or relationship instead of 'role'
                // Assuming there's a role_id column or a role relationship
                $departmentHead = User::whereHas('teams', function($query) use ($user) {
                    $query->where('teams.id', $user->currentTeam->id);
                })
                    ->whereHas('role', function($query) {
                        $query->where('name', 'Abteilungsleiter');
                    })
                    ->first();

                // If there's no role relationship but a role_id column, use this instead:
                // ->where('role_id', function($query) {
                //     $query->select('id')->from('roles')->where('name', 'Abteilungsleiter');
                // })
            }

            // Fallback: Wenn kein Abteilungsleiter gefunden wurde, sende an HR oder Admin
            if (!$departmentHead) {
                // FIXED: Use the role relationship or role_id column
                $departmentHead = User::whereHas('role', function($query) {
                    $query->where('name', 'HR')->orWhere('name', 'Admin');
                })->first();

                // If there's no role relationship but a role_id column, use this instead:
                // $departmentHead = User::whereIn('role_id', function($query) {
                //     $query->select('id')->from('roles')->whereIn('name', ['HR', 'Admin']);
                // })->first();
            }

            // Sende eine E-Mail an den Abteilungsleiter
            if ($departmentHead) {
                try {
                    Log::info('Sende E-Mail an Abteilungsleiter', [
                        'department_head_id' => $departmentHead->id,
                        'department_head_email' => $departmentHead->email
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
                    $mail = Mail::to($departmentHead->email);

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
                'end_date' => $request->end_date->format('d.m.Y')
            ];
        });
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

            // Prüfen, ob der Benutzer berechtigt ist, den Antrag zu genehmigen
            $role = $user->role ? $user->role->name : null;
            $isAuthorized = false;

            // Abteilungsleiter, HR oder Admin dürfen immer genehmigen
            if (in_array($role, ['Abteilungsleiter', 'HR', 'Admin'])) {
                $isAuthorized = true;
            }
            // Vertreter darf nur genehmigen, wenn er als Vertreter eingetragen ist
            elseif ($vacationRequest->substitute_id === $user->id) {
                $isAuthorized = true;
            }

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
            $role = $user->role ? $user->role->name : null;
            $isAuthorized = false;

            // Abteilungsleiter, HR oder Admin dürfen immer ablehnen
            if (in_array($role, ['Abteilungsleiter', 'HR', 'Admin'])) {
                $isAuthorized = true;
            }
            // Vertreter darf nur ablehnen, wenn er als Vertreter eingetragen ist
            elseif ($vacationRequest->substitute_id === $user->id) {
                $isAuthorized = true;
            }

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
            Notification::create([
                'user_id' => $employee->id,
                'title' => 'Urlaubsantrag abgelehnt',
                'message' => "Ihr Urlaubsantrag vom {$vacationRequest->start_date->format('d.m.Y')} bis {$vacationRequest->end_date->format('d.m.Y')} wurde abgelehnt.",
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
                    'notes' => $vacationRequest->notes
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
            Log::error('Error in cancelRequest: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
