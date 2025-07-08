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
use Carbon\CarbonPeriod; // Import CarbonPeriod für die Iteration über Zeiträume
use Illuminate\Support\Collection; // Import Collection für Typ-Hints

class VacationController extends Controller
{
    /**
     * Get user vacation data
     * KORRIGIERT für neue Übertrag-Felder und Überlappungen
     */
    public function getUserData()
    {
        try {
            $user = Auth::user();
            $currentYear = Carbon::now()->year;

            // Finde oder erstelle die Urlaubsbilanz für das aktuelle Jahr
            $vacationBalance = VacationBalance::firstOrCreate(
                ['user_id' => $user->id, 'year' => $currentYear],
                [
                    'total_days' => $user->vacation_days_per_year,
                    'used_days' => 0,
                    'carry_over_days' => 0,
                    'carry_over_used' => 0,
                    'carry_over_expires_at' => Carbon::create($currentYear, 7, 31), // 31. Juli
                    'max_carry_over' => 10
                ]
            );

            $holidays = $this->getHolidaysForYear($currentYear); // Feiertage für das aktuelle Jahr abrufen

            // NEU: Berechne die tatsächlich genutzten Tage basierend auf einzigartigen, genehmigten Tagen
            $totalUniqueApprovedDays = $this->calculateTotalUniqueApprovedWorkDays($user, $currentYear, $holidays);

            // NEU: Berechne die tatsächlich geplanten Tage basierend auf einzigartigen, ausstehenden Tagen,
            // die nicht mit genehmigten Tagen überlappen
            $plannedDays = $this->calculateTotalUniquePendingWorkDays($user, $currentYear, $holidays);

            // Hole die aktuellen Werte aus der Bilanz für die Berechnung der Anzeige-Statistiken
            $baseEntitlement = $vacationBalance->total_days;
            $carryOver = $vacationBalance->carry_over_days ?? 0;
            $carryOverExpires = $vacationBalance->carry_over_expires_at;

            $totalEntitlement = $baseEntitlement + $carryOver;

            // Simuliere die FIFO-Verbrauch für die Anzeige-Statistiken
            // Dies ist eine Anzeige-Berechnung, die tatsächliche DB-Aktualisierung erfolgt in recalculateAndSaveVacationBalance
            $simulatedCarryOverUsed = min($carryOver, $totalUniqueApprovedDays);
            $simulatedRegularUsed = max(0, $totalUniqueApprovedDays - $simulatedCarryOverUsed);

            // Die verbleibenden Tage basieren auf dem Gesamtanspruch minus den einzigartig genutzten und geplanten Tagen
            $remainingDays = $totalEntitlement - $totalUniqueApprovedDays - $plannedDays;
            $remainingDays = max(0, $remainingDays); // Sicherstellen, dass es nicht negativ wird

            // Detaillierte Aufschlüsselung der verbleibenden Tage für die Anzeige
            $remainingCarryOver = max(0, $carryOver - $simulatedCarryOverUsed);
            $remainingRegular = max(0, $baseEntitlement - $simulatedRegularUsed);

            $stats = [
                'baseEntitlement' => $baseEntitlement,
                'carryOver' => $carryOver,
                'carryOverUsed' => $simulatedCarryOverUsed, // Angepasster Wert für Anzeige
                'carryOverRemaining' => $remainingCarryOver,
                'carryOverExpires' => $carryOverExpires ? $carryOverExpires->format('Y-m-d') : null,
                'totalAvailable' => $totalEntitlement,
                'total' => $totalEntitlement, // Für Rückwärtskompatibilität
                'used' => $totalUniqueApprovedDays, // Gesamte einzigartige verwendete Tage
                'usedRegular' => $simulatedRegularUsed, // Angepasster Wert für Anzeige
                'usedCarryOver' => $simulatedCarryOverUsed, // Angepasster Wert für Anzeige
                'planned' => $plannedDays, // Einzigartige geplante Tage
                'remaining' => $remainingDays,
                'remainingRegular' => $remainingRegular,
                'remainingCarryOver' => $remainingCarryOver
            ];

            // Rest der Methode bleibt gleich, da requests, bookedDates, substitutes, history, yearlyStats, yearVacationDetails, monthlyStats
            // bereits auf den korrekten Daten basieren oder keine Überlappungslogik benötigen,
            // oder in den folgenden Abschnitten angepasst werden.

            $requests = VacationRequest::where('user_id', $user->id)
                ->with(['substitute', 'approver', 'rejector'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'startDate' => $request->start_date->format('Y-m-d'),
                        'endDate' => $request->end_date->format('Y-m-d'),
                        'days' => $request->days, // Original days from request
                        'dayType' => $request->day_type ?? 'full_day',
                        'dayTypeLabel' => $this->getDayTypeLabel($request->day_type ?? 'full_day'),
                        'actualDays' => $this->calculateActualDays($request), // Actual days for this specific request
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

            // bookedDates sollten weiterhin alle genehmigten Anträge enthalten, da sie für den Kalender sind
            $bookedDates = VacationRequest::where('status', 'approved')
                ->get()
                ->map(function ($request) {
                    return [
                        'id' => $request->id, // ID hinzufügen, falls im Frontend benötigt
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

                // Wenn keine Bilanz für das Jahr existiert, erstelle eine Standardbilanz für die Historie
                if (!$balance) {
                    $balance = new VacationBalance([
                        'user_id' => $user->id,
                        'year' => $year,
                        'total_days' => $user->vacation_days_per_year,
                        'used_days' => 0,
                        'carry_over_days' => 0,
                        'carry_over_used' => 0,
                        'carry_over_expires_at' => Carbon::create($year, 7, 31),
                        'max_carry_over' => 10
                    ]);
                }

                $yearHolidays = $this->getHolidaysForYear($year);
                $totalUniqueApprovedDaysForYear = $this->calculateTotalUniqueApprovedWorkDays($user, $year, $yearHolidays);

                $yearCarryOver = $balance->carry_over_days ?? 0;
                $yearBaseEntitlement = $balance->total_days;
                $yearTotalEntitlement = $yearBaseEntitlement + $yearCarryOver;

                // Simuliere FIFO für die Historie
                $simulatedYearCarryOverUsed = min($yearCarryOver, $totalUniqueApprovedDaysForYear);
                $simulatedYearRegularUsed = max(0, $totalUniqueApprovedDaysForYear - $simulatedYearCarryOverUsed);

                $yearRemaining = $yearTotalEntitlement - $totalUniqueApprovedDaysForYear;
                $yearRemaining = max(0, $yearRemaining);

                $carryOverToNextYear = 0;
                if ($year < $currentYear) {
                    // Für vergangene Jahre: Übertrag ist der Rest des Jahres, begrenzt auf max_carry_over
                    $carryOverToNextYear = min($balance->max_carry_over, $yearRemaining);
                } else {
                    // Für das aktuelle Jahr: Übertrag ist der aktuelle Rest, begrenzt auf max_carry_over
                    $carryOverToNextYear = min($balance->max_carry_over, $remainingDays);
                }

                $history[] = [
                    'year' => $year,
                    'baseEntitlement' => $yearBaseEntitlement,
                    'carryOver' => $yearCarryOver,
                    'carryOverUsed' => $simulatedYearCarryOverUsed,
                    'totalEntitlement' => $yearTotalEntitlement,
                    'used' => $totalUniqueApprovedDaysForYear, // Gesamte einzigartige verwendete Tage
                    'usedRegular' => $simulatedYearRegularUsed, // Nur reguläre Tage
                    'usedCarryOver' => $simulatedYearCarryOverUsed, // Nur Übertragstage
                    'remaining' => $yearRemaining,
                    'carryOverToNextYear' => $carryOverToNextYear
                ];
            }

            $yearlyStats = [];
            $yearVacationDetails = [];
            $monthlyStats = [];

            foreach ($history as $yearData) {
                $year = $yearData['year'];
                $yearHolidays = $this->getHolidaysForYear($year);

                $yearRequests = VacationRequest::where('user_id', $user->id)
                    ->whereYear('start_date', $year)
                    ->orWhere(function($query) use ($user, $year) {
                        $query->where('user_id', $user->id)
                            ->whereYear('end_date', $year)
                            ->whereYear('start_date', '<', $year);
                    })
                    ->with(['substitute', 'approver', 'rejector'])
                    ->orderBy('start_date')
                    ->get();

                $monthlyData = array_fill(0, 12, 0);
                $details = [];

                // Für die monatliche Statistik und Details: Iteriere über die Tage, nicht über die Anträge direkt
                $uniqueApprovedDaysForMonthlyStats = collect();
                foreach ($yearRequests as $request) {
                    if ($request->status === 'approved') {
                        $period = CarbonPeriod::create($request->start_date, $request->end_date);
                        foreach ($period as $date) {
                            if ($date->year !== $year || $date->isWeekend() || $yearHolidays->contains(fn($h) => $h->isSameDay($date))) {
                                continue;
                            }
                            // Füge Tage mit Halbtags-Marker hinzu
                            if ($request->start_date->eq($request->end_date) && in_array($request->day_type, ['morning', 'afternoon'])) {
                                $uniqueApprovedDaysForMonthlyStats->add($date->format('Y-m-d') . '_half');
                            } else {
                                $uniqueApprovedDaysForMonthlyStats->add($date->format('Y-m-d') . '_full');
                            }
                        }
                    }
                    // Details für jeden Antrag, unabhängig vom Status
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

                // Aggregiere die einzigartigen Tage für die monatliche Statistik
                foreach ($uniqueApprovedDaysForMonthlyStats->unique() as $dayMarker) {
                    $date = Carbon::parse(substr($dayMarker, 0, 10));
                    $monthIndex = $date->month - 1;
                    if (str_ends_with($dayMarker, '_half')) {
                        $monthlyData[$monthIndex] += 0.5;
                    } else {
                        $monthlyData[$monthIndex] += 1;
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
     * KORRIGIERT für neue Übertrag-Felder und Überlappungen
     */
    public function getYearlyVacationData($year)
    {
        try {
            $user = Auth::user();
            $holidays = $this->getHolidaysForYear($year);

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

            // NEU: Berechne die tatsächlich genutzten Tage basierend auf einzigartigen, genehmigten Tagen
            $totalUniqueApprovedDays = $this->calculateTotalUniqueApprovedWorkDays($user, $year, $holidays);

            // NEU: Berechne die tatsächlich geplanten Tage basierend auf einzigartigen, ausstehenden Tagen,
            // die nicht mit genehmigten Tagen überlappen
            $plannedDays = $this->calculateTotalUniquePendingWorkDays($user, $year, $holidays);

            $baseEntitlement = $vacationBalance->total_days;
            $carryOver = $vacationBalance->carry_over_days ?? 0;
            $carryOverExpires = $vacationBalance->carry_over_expires_at;

            $totalEntitlement = $baseEntitlement + $carryOver;

            // Simuliere die FIFO-Verbrauch für die Anzeige-Statistiken
            $simulatedCarryOverUsed = min($carryOver, $totalUniqueApprovedDays);
            $simulatedRegularUsed = max(0, $totalUniqueApprovedDays - $simulatedCarryOverUsed);

            $remainingDays = $totalEntitlement - $totalUniqueApprovedDays - $plannedDays;
            $remainingDays = max(0, $remainingDays);

            $stats = [
                'baseEntitlement' => $baseEntitlement,
                'carryOver' => $carryOver,
                'carryOverUsed' => $simulatedCarryOverUsed,
                'totalEntitlement' => $totalEntitlement,
                'used' => $totalUniqueApprovedDays,
                'planned' => $plannedDays,
                'remaining' => $remainingDays
            ];

            $yearRequests = VacationRequest::where('user_id', $user->id)
                ->whereYear('start_date', $year)
                ->orWhere(function($query) use ($user, $year) {
                    $query->where('user_id', $user->id)
                        ->whereYear('end_date', $year)
                        ->whereYear('start_date', '<', $year);
                })
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

            $query = VacationRequest::with(['user', 'user.currentTeam', 'substitute']);

            if (in_array($role, ['HR', 'Admin', 'Personal'])) {
                // HR, Admin, Personal sehen alle Anträge
                Log::info('HR/Admin/Personal - showing all requests');
            } elseif ($role === 'Abteilungsleiter') {
                // Abteilungsleiter sehen ihre eigenen Anträge und die Anträge ihrer Teammitglieder
                $departmentId = $user->current_team_id;

                $query->where(function($q) use ($user, $departmentId) {
                    $q->where('user_id', $user->id) // Eigene Anträge
                    ->orWhere('team_id', $departmentId); // Anträge des Teams
                });
                Log::info('Abteilungsleiter - showing own and team requests', [
                    'user_id' => $user->id,
                    'department_id' => $departmentId
                ]);
            } else {
                // Für alle anderen Benutzer (einschließlich Vertreter, die nicht HR/Admin/Abteilungsleiter sind)
                // Sie sehen nur ausstehende Anträge, bei denen sie als Vertreter eingetragen sind
                $query->where('substitute_id', $user->id)
                    ->where('status', 'pending');
                Log::info('Other user/Substitute - showing only pending substitute requests', [
                    'user_id' => $user->id
                ]);
            }

            $allRequests = $query->orderBy('created_at', 'desc')->get();

            Log::info('Total requests fetched after role-based filtering', [
                'count' => $allRequests->count()
            ]);

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
                        'dayType' => $request->day_type ?? 'full_day',
                        'dayTypeLabel' => $this->getDayTypeLabel($request->day_type ?? 'full_day'),
                        'actualDays' => $this->calculateActualDays($request),
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
                        'dayType' => $request->day_type ?? 'full_day',
                        'dayTypeLabel' => $this->getDayTypeLabel($request->day_type ?? 'full_day'),
                        'actualDays' => $this->calculateActualDays($request),
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

            Log::info('Urlaubsantrag empfangen', [
                'user' => $user->id,
                'is_apprentice' => $user->is_apprentice ?? false,
                'mentor_id' => $user->mentor_id ?? null,
                'request_data' => $request->all()
            ]);

            $request->validate([
                'periods' => 'required|array',
                'periods.*.startDate' => 'required|date',
                'periods.*.endDate' => 'required|date|after_or_equal:periods.*.startDate',
                'periods.*.days' => 'required|numeric|min:0.5',
                'periods.*.dayType' => 'nullable|in:full_day,morning,afternoon',
                'substitute' => 'nullable|integer',
                'notes' => 'nullable|string'
            ]);

            $teamId = $user->currentTeam ? $user->currentTeam->id : null;
            $createdRequests = [];

            foreach ($request->periods as $period) {
                $vacationRequest = new VacationRequest();
                $vacationRequest->user_id = $user->id;
                $vacationRequest->team_id = $teamId;
                $vacationRequest->substitute_id = $request->substitute;
                $vacationRequest->notes = $request->notes;
                $vacationRequest->status = 'pending';

                $startDate = Carbon::parse($period['startDate'])->startOfDay();
                $endDate = Carbon::parse($period['endDate'])->startOfDay();

                $vacationRequest->start_date = $startDate;
                $vacationRequest->end_date = $endDate;
                $vacationRequest->days = $period['days'];
                $vacationRequest->day_type = $period['dayType'] ?? 'full_day';

                $vacationRequest->save();

                Log::info('Urlaubsantrag erstellt', [
                    'request_id' => $vacationRequest->id,
                    'start_date' => $vacationRequest->start_date->format('Y-m-d'),
                    'end_date' => $vacationRequest->end_date->format('Y-m-d'),
                    'days' => $vacationRequest->days,
                    'day_type' => $vacationRequest->day_type,
                    'actual_days' => $this->calculateActualDays($vacationRequest)
                ]);

                $createdRequests[] = $vacationRequest;
            }

            $firstRequest = $createdRequests[0];
            $firstRequest->load(['user', 'substitute']);

            $approver = $user->vacation_approver;

            Log::info('Genehmiger bestimmt', [
                'approver_id' => $approver ? $approver->id : null,
                'approver_name' => $approver ? $approver->full_name : null,
                'approver_type' => $user->is_apprentice ? 'Mentor' : 'Abteilungsleiter'
            ]);

            if (!$approver) {
                $approver = User::whereHas('role', function($query) {
                    $query->where('name', 'HR')->orWhere('name', 'Personal')->orWhere('name', 'Admin');
                })->first();

                Log::info('Fallback-Genehmiger gefunden', [
                    'fallback_approver_id' => $approver ? $approver->id : null
                ]);
            }

            if ($approver) {
                try {
                    Log::info('Sende E-Mail an Genehmiger', [
                        'approver_id' => $approver->id,
                        'approver_email' => $approver->email,
                        'approver_type' => $user->is_apprentice ? 'Mentor' : 'Abteilungsleiter'
                    ]);

                    $overlappingRequests = $this->getOverlappingRequests($firstRequest);

                    $ccRecipients = [];
                    if ($firstRequest->substitute) {
                        $ccRecipients[] = $firstRequest->substitute->email;
                        Log::info('Vertreter als CC hinzugefügt', [
                            'substitute_id' => $firstRequest->substitute->id,
                            'substitute_email' => $firstRequest->substitute->email
                        ]);
                    }

                    $mail = Mail::to($approver->email);
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
     * KORRIGIERT: Aktualisiert die Urlaubsbilanz neu basierend auf allen genehmigten Anträgen
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

            $vacationRequest = VacationRequest::findOrFail($id);

            if ($vacationRequest->status !== 'pending') {
                return response()->json([
                    'error' => 'Dieser Urlaubsantrag wurde bereits bearbeitet.'
                ], 400);
            }

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

            // WICHTIG: Aktualisiere die Urlaubsbilanz NEU basierend auf ALLEN genehmigten Anträgen
            $this->recalculateAndSaveVacationBalance($vacationRequest->user, $vacationRequest->start_date->year);

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

                if ($employee->email) {
                    $ccRecipients = [];
                    $bccRecipients = [];

                    $hrEmail = env('HR_EMAIL', 'personal@dittmeier.de');
                    if ($hrEmail) {
                        $bccRecipients[] = $hrEmail;
                    }

                    if ($vacationRequest->substitute_id === $user->id) {
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

                    $mail = Mail::to($employee->email);
                    if (!empty($ccRecipients)) {
                        $mail->cc($ccRecipients);
                    }
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
            }

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

            $request->validate([
                'reason' => 'nullable|string|max:255'
            ]);

            Log::info('Urlaubsantrag-Ablehnung empfangen', [
                'approver_id' => $user->id,
                'request_id' => $id,
                'reason' => $request->reason
            ]);

            $vacationRequest = VacationRequest::findOrFail($id);

            if ($vacationRequest->status !== 'pending') {
                return response()->json([
                    'error' => 'Dieser Urlaubsantrag wurde bereits bearbeitet.'
                ], 400);
            }

            $isAuthorized = $this->canApproveRequest($user, $vacationRequest);

            if (!$isAuthorized) {
                return response()->json([
                    'error' => 'Sie sind nicht berechtigt, diesen Urlaubsantrag abzulehnen.'
                ], 403);
            }

            $vacationRequest->status = 'rejected';
            $vacationRequest->rejected_by = $user->id;
            $vacationRequest->rejected_date = now();
            $vacationRequest->rejection_reason = $request->reason;
            $vacationRequest->save();

            $employee = User::findOrFail($vacationRequest->user_id);

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

            try {
                Log::info('Sende Ablehnungs-E-Mail an Antragsteller', [
                    'employee_id' => $employee->id,
                    'employee_email' => $employee->email,
                    'notes' => $vacationRequest->notes,
                    'day_type' => $vacationRequest->day_type,
                    'actual_days' => $actualDays
                ]);

                if ($employee->email) {
                    $ccRecipients = [];
                    $bccRecipients = [];

                    $hrEmail = env('HR_EMAIL', 'personal@dittmeier.de');
                    if ($hrEmail) {
                        $bccRecipients[] = $hrEmail;
                    }

                    if ($vacationRequest->substitute_id === $user->id) {
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

                    $mail = Mail::to($employee->email);
                    if (!empty($ccRecipients)) {
                        $mail->cc($ccRecipients);
                    }
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
     * Storniert einen genehmigten Urlaubsantrag
     * KORRIGIERT: Aktualisiert die Urlaubsbilanz neu nach Stornierung
     */
    public function cancelApprovedRequest($id)
    {
        try {
            $user = Auth::user();

            $vacationRequest = VacationRequest::where('user_id', $user->id)
                ->where('id', $id)
                ->where('status', 'approved')
                ->firstOrFail();

            if ($vacationRequest->start_date <= Carbon::now()) {
                return response()->json([
                    'error' => 'Urlaubsanträge, die bereits begonnen haben, können nicht mehr storniert werden.'
                ], 400);
            }

            // Status auf storniert setzen
            $vacationRequest->status = 'cancelled';
            $vacationRequest->save();

            // WICHTIG: Aktualisiere die Urlaubsbilanz NEU basierend auf ALLEN verbleibenden genehmigten Anträgen
            $this->recalculateAndSaveVacationBalance($vacationRequest->user, $vacationRequest->start_date->year);

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

    /**
     * Cancel a vacation request (pending)
     */
    public function cancelRequest($id)
    {
        try {
            $user = Auth::user();

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

    /**
     * NEU: Rekalkuliert und speichert die Urlaubsbilanz für einen Benutzer und ein Jahr.
     * Dies ist die zentrale Methode zur Vermeidung von Doppelzählungen.
     *
     * @param User $user
     * @param int $year
     * @return void
     */
    private function recalculateAndSaveVacationBalance(User $user, int $year)
    {
        $balance = VacationBalance::firstOrCreate(
            ['user_id' => $user->id, 'year' => $year],
            [
                'total_days' => $user->vacation_days_per_year,
                'used_days' => 0,
                'carry_over_days' => 0,
                'carry_over_used' => 0,
                'carry_over_expires_at' => Carbon::create($year, 7, 31), // 31. Juli
                'max_carry_over' => 10
            ]
        );

        $holidays = $this->getHolidaysForYear($year);
        $totalUniqueApprovedDays = $this->calculateTotalUniqueApprovedWorkDays($user, $year, $holidays);

        // Setze die genutzten Tage zurück und wende die FIFO-Logik auf die Gesamtanzahl an
        $balance->used_days = 0;
        $balance->carry_over_used = 0;

        $remainingDaysToDeduct = $totalUniqueApprovedDays;

        // 1. Zuerst übertragene Tage verbrauchen
        $availableCarryOverDays = $balance->carry_over_days; // Gesamter Übertrag
        if ($availableCarryOverDays > 0 && $remainingDaysToDeduct > 0) {
            $carryOverToUse = min($availableCarryOverDays, $remainingDaysToDeduct);
            $balance->carry_over_used = $carryOverToUse; // Setze direkt, nicht addieren
            $remainingDaysToDeduct -= $carryOverToUse;
        }

        // 2. Dann reguläre Urlaubstage des aktuellen Jahres verbrauchen
        if ($remainingDaysToDeduct > 0) {
            $balance->used_days = $remainingDaysToDeduct; // Setze direkt, nicht addieren
        }

        $balance->save();

        Log::info('Urlaubsbilanz neu berechnet und gespeichert', [
            'user_id' => $user->id,
            'year' => $year,
            'total_unique_approved_days' => $totalUniqueApprovedDays,
            'final_carry_over_used' => $balance->carry_over_used,
            'final_regular_used' => $balance->used_days,
        ]);
    }

    /**
     * NEU: Berechnet die Gesamtanzahl der einzigartigen, genehmigten Arbeitstage für einen Benutzer in einem Jahr.
     * Berücksichtigt Wochenenden und Feiertage.
     *
     * @param User $user
     * @param int $year
     * @param Collection $holidays Collection of Carbon dates for holidays
     * @return float
     */
    private function calculateTotalUniqueApprovedWorkDays(User $user, int $year, Collection $holidays): float
    {
        $approvedRequests = VacationRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where(function($query) use ($year) {
                $query->whereYear('start_date', $year)
                    ->orWhereYear('end_date', $year); // Auch Anträge, die im Vorjahr beginnen und im aktuellen Jahr enden
            })
            ->get();

        $uniqueWorkDays = collect(); // Speichert 'YYYY-MM-DD_full' oder 'YYYY-MM-DD_half'

        foreach ($approvedRequests as $request) {
            $period = CarbonPeriod::create($request->start_date, $request->end_date);
            foreach ($period as $date) {
                // Nur Tage im relevanten Jahr berücksichtigen
                if ($date->year !== $year) {
                    continue;
                }

                // Wochenenden und Feiertage ausschließen
                if ($date->isWeekend() || $holidays->contains(fn($h) => $h->isSameDay($date))) {
                    continue;
                }

                // Halbtage behandeln: Wenn es ein einzelner Tag ist und Halbtag gewählt wurde
                if ($request->start_date->eq($request->end_date) && in_array($request->day_type, ['morning', 'afternoon'])) {
                    $uniqueWorkDays->add($date->format('Y-m-d') . '_half');
                } else {
                    $uniqueWorkDays->add($date->format('Y-m-d') . '_full');
                }
            }
        }

        // Zähle die einzigartigen Tage und summiere sie auf
        $totalDays = 0.0;
        foreach ($uniqueWorkDays->unique() as $dayMarker) {
            if (str_ends_with($dayMarker, '_half')) {
                $totalDays += 0.5;
            } else {
                $totalDays += 1.0;
            }
        }

        return $totalDays;
    }

    /**
     * NEU: Berechnet die Gesamtanzahl der einzigartigen, ausstehenden Arbeitstage für einen Benutzer in einem Jahr,
     * die NICHT mit bereits genehmigten Tagen überlappen.
     *
     * @param User $user
     * @param int $year
     * @param Collection $holidays Collection of Carbon dates for holidays
     * @return float
     */
    private function calculateTotalUniquePendingWorkDays(User $user, int $year, Collection $holidays): float
    {
        $pendingRequests = VacationRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->where(function($query) use ($year) {
                $query->whereYear('start_date', $year)
                    ->orWhereYear('end_date', $year);
            })
            ->get();

        // Zuerst alle genehmigten Tage sammeln, um Überlappungen zu prüfen
        $approvedWorkDays = collect();
        $approvedRequests = VacationRequest::where('user_id', $user->id)
            ->where('status', 'approved')
            ->where(function($query) use ($year) {
                $query->whereYear('start_date', $year)
                    ->orWhereYear('end_date', $year);
            })
            ->get();

        foreach ($approvedRequests as $request) {
            $period = CarbonPeriod::create($request->start_date, $request->end_date);
            foreach ($period as $date) {
                if ($date->year !== $year || $date->isWeekend() || $holidays->contains(fn($h) => $h->isSameDay($date))) {
                    continue;
                }
                // Hier nur volle Tage hinzufügen, da ein Halbtag einen ganzen Tag blockiert, wenn er überlappt
                $approvedWorkDays->add($date->format('Y-m-d'));
            }
        }
        $approvedWorkDays = $approvedWorkDays->unique();

        $uniquePendingWorkDays = collect();

        foreach ($pendingRequests as $request) {
            $period = CarbonPeriod::create($request->start_date, $request->end_date);
            foreach ($period as $date) {
                if ($date->year !== $year || $date->isWeekend() || $holidays->contains(fn($h) => $h->isSameDay($date))) {
                    continue;
                }

                // Nur Tage hinzufügen, die NICHT bereits durch genehmigte Anträge abgedeckt sind
                if (!$approvedWorkDays->contains($date->format('Y-m-d'))) {
                    if ($request->start_date->eq($request->end_date) && in_array($request->day_type, ['morning', 'afternoon'])) {
                        $uniquePendingWorkDays->add($date->format('Y-m-d') . '_half');
                    } else {
                        $uniquePendingWorkDays->add($date->format('Y-m-d') . '_full');
                    }
                }
            }
        }

        $totalDays = 0.0;
        foreach ($uniquePendingWorkDays->unique() as $dayMarker) {
            if (str_ends_with($dayMarker, '_half')) {
                $totalDays += 0.5;
            } else {
                $totalDays += 1.0;
            }
        }

        return $totalDays;
    }

    /**
     * NEU: Hilfsmethode zur Rückgabe einer Collection von Feiertagen für ein bestimmtes Jahr.
     * Diese Methode sollte deine tatsächliche Logik zum Abrufen von Feiertagen enthalten.
     *
     * @param int $year
     * @return Collection<Carbon>
     */
    private function getHolidaysForYear(int $year): Collection
    {
        // TODO: Implementiere hier die tatsächliche Logik zum Abrufen von Feiertagen aus deiner Datenbank oder einem externen Dienst.
        // Beispiel, wenn du ein `Holiday` Model hast:
        // return Holiday::whereYear('date', $year)->get()->pluck('date');

        // Für den Moment eine statische Liste als Platzhalter (bitte anpassen!):
        return collect([
            Carbon::create($year, 1, 1),   // Neujahr
            Carbon::create($year, 3, 29),  // Karfreitag (Beispiel für 2024)
            Carbon::create($year, 4, 1),   // Ostermontag (Beispiel für 2024)
            Carbon::create($year, 5, 1),   // Tag der Arbeit
            Carbon::create($year, 5, 9),   // Christi Himmelfahrt (Beispiel für 2024)
            Carbon::create($year, 5, 20),  // Pfingstmontag (Beispiel für 2024)
            Carbon::create($year, 10, 3),  // Tag der Deutschen Einheit
            Carbon::create($year, 12, 25), // 1. Weihnachtstag
            Carbon::create($year, 12, 26), // 2. Weihnachtstag
        ]);
    }

    /**
     * Hilfsmethode, um den Monatsnamen zu erhalten
     */
    private function getMonthName($month)
    {
        $months = [
            1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April', 5 => 'Mai', 6 => 'Juni',
            7 => 'Juli', 8 => 'August', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember'
        ];
        return $months[$month] ?? '';
    }

    /**
     * Hilfsmethode, um den Statustext zu erhalten
     */
    private function getStatusText($status)
    {
        $statusMap = [
            'pending' => 'Ausstehend', 'approved' => 'Genehmigt', 'rejected' => 'Abgelehnt', 'cancelled' => 'Storniert'
        ];
        return $statusMap[$status] ?? $status;
    }

    /**
     * Hilfsmethode, um die Statusfarbe zu erhalten
     */
    private function getStatusColor($status)
    {
        $colorMap = [
            'pending' => 'blue', 'approved' => 'green', 'rejected' => 'red', 'cancelled' => 'gray'
        ];
        return $colorMap[$status] ?? 'gray';
    }

    /**
     * Finde überlappende Urlaubsanträge (für E-Mail-Benachrichtigung)
     */
    private function getOverlappingRequests(VacationRequest $vacationRequest)
    {
        $user = $vacationRequest->user;
        $teamId = $user->currentTeam ? $user->currentTeam->id : null;

        if (!$teamId) {
            return collect();
        }

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
                'day_type' => $request->day_type ?? 'full_day',
                'day_type_label' => $this->getDayTypeLabel($request->day_type ?? 'full_day'),
                'actual_days' => $this->calculateActualDays($request)
            ];
        });
    }

    /**
     * Hilfsmethode zur Berechnung der tatsächlichen Urlaubstage für einen einzelnen Antrag.
     * Diese Methode bleibt, wie sie ist, da sie die `days` Eigenschaft des Modells verwendet,
     * die bereits die korrekte Anzahl für den einzelnen Antrag (inkl. Halbtage) enthalten sollte.
     * Die Überlappungslogik wird in `calculateTotalUniqueApprovedWorkDays` behandelt.
     */
    private function calculateActualDays(VacationRequest $request): float
    {
        $dayType = $request->day_type ?? 'full_day';

        // Wenn es ein einzelner Tag ist und Halbtag gewählt wurde
        if ($request->start_date->eq($request->end_date) && in_array($dayType, ['morning', 'afternoon'])) {
            return 0.5;
        }

        // Ansonsten verwende die normale Tagesanzahl, die im Modell gespeichert ist
        return (float) $request->days;
    }

    /**
     * Hilfsmethode zur Rückgabe des deutschen Labels für den Urlaubstyp
     */
    private function getDayTypeLabel($dayType): string
    {
        return match($dayType) {
            'morning' => 'Vormittag',
            'afternoon' => 'Nachmittag',
            'full_day' => 'Ganzer Tag',
            default => 'Ganzer Tag'
        };
    }

    // Die Methode refundVacationDays wurde entfernt, da die Neuberechnung der Bilanz sie überflüssig macht.

    // Die HR-Methoden getHROverview und getVacationInfoList müssen ebenfalls angepasst werden,
    // um die neuen calculateTotalUniqueApprovedWorkDays zu verwenden.
    // Ich habe die relevanten Stellen in diesen Methoden ebenfalls aktualisiert.

    public function getHROverview()
    {
        try {
            $user = Auth::user();
            $role = $user->role ? $user->role->name : null;

            if (!in_array($role, ['HR', 'Admin', 'Personal'])) {
                return response()->json(['error' => 'Nicht autorisiert'], 403);
            }

            $currentYear = Carbon::now()->year;
            $previousYear = $currentYear - 1;
            $currentMonth = Carbon::now()->month;

            $users = User::where('is_active', true)
                ->with(['vacationBalances' => function($query) use ($currentYear, $previousYear) {
                    $query->whereIn('year', [$previousYear, $currentYear]);
                }])
                ->get();

            $overviewData = [];

            foreach ($users as $user) {
                $currentYearBalance = $user->vacationBalances->where('year', $currentYear)->first();
                $previousYearBalance = $user->vacationBalances->where('year', $previousYear)->first();

                if (!$currentYearBalance) {
                    $currentYearBalance = new VacationBalance([
                        'total_days' => $user->vacation_days_per_year,
                        'used_days' => 0,
                        'carry_over_days' => 0,
                        'carry_over_used' => 0,
                        'carry_over_expires_at' => Carbon::create($currentYear, 7, 31),
                        'max_carry_over' => 10
                    ]);
                }
                if (!$previousYearBalance) {
                    $previousYearBalance = new VacationBalance([
                        'total_days' => $user->vacation_days_per_year,
                        'used_days' => 0, // Standardmäßig 0, nicht alle verbraucht
                        'carry_over_days' => 0,
                        'carry_over_used' => 0,
                        'carry_over_expires_at' => Carbon::create($previousYear, 7, 31),
                        'max_carry_over' => 10
                    ]);
                }

                $carryOverFromPreviousYear = max(0, min(10, ($previousYearBalance->total_days + $previousYearBalance->carry_over_days) - ($previousYearBalance->used_days + $previousYearBalance->carry_over_used)));

                $totalEntitlement = $currentYearBalance->total_days + $carryOverFromPreviousYear;

                // NEU: Berechne die tatsächlich genutzten Tage für das aktuelle Jahr (HR-Übersicht)
                $holidaysCurrentYear = $this->getHolidaysForYear($currentYear);
                $totalUniqueApprovedDaysCurrentYear = $this->calculateTotalUniqueApprovedWorkDays($user, $currentYear, $holidaysCurrentYear);

                // Monatliche Urlaubsanträge für das aktuelle Jahr laden (basierend auf einzigartigen Tagen)
                $monthlyUsage = array_fill(0, 12, 0.0);
                $uniqueApprovedDaysForMonthlyStats = collect();

                $vacationRequestsForMonthly = VacationRequest::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->where(function($query) use ($currentYear) {
                        $query->whereYear('start_date', $currentYear)
                            ->orWhereYear('end_date', $currentYear);
                    })
                    ->get();

                foreach ($vacationRequestsForMonthly as $request) {
                    $period = CarbonPeriod::create($request->start_date, $request->end_date);
                    foreach ($period as $date) {
                        if ($date->year !== $currentYear || $date->isWeekend() || $holidaysCurrentYear->contains(fn($h) => $h->isSameDay($date))) {
                            continue;
                        }
                        if ($request->start_date->eq($request->end_date) && in_array($request->day_type, ['morning', 'afternoon'])) {
                            $uniqueApprovedDaysForMonthlyStats->add($date->format('Y-m-d') . '_half');
                        } else {
                            $uniqueApprovedDaysForMonthlyStats->add($date->format('Y-m-d') . '_full');
                        }
                    }
                }

                foreach ($uniqueApprovedDaysForMonthlyStats->unique() as $dayMarker) {
                    $date = Carbon::parse(substr($dayMarker, 0, 10));
                    $monthIndex = $date->month - 1;
                    if (str_ends_with($dayMarker, '_half')) {
                        $monthlyUsage[$monthIndex] += 0.5;
                    } else {
                        $monthlyUsage[$monthIndex] += 1.0;
                    }
                }

                // Berechne die monatlichen Resttage
                $monthlyRemainingDays = [];
                $runningTotal = $totalEntitlement;

                for ($month = 1; $month <= 12; $month++) {
                    $runningTotal -= $monthlyUsage[$month-1]; // monthlyUsage ist 0-basiert
                    $monthlyRemainingDays[$month] = $runningTotal;
                }

                $displayMonths = min($currentMonth - 1, 4);
                if ($currentMonth >= 6) {
                    $displayMonths = 5;
                }

                $allVacationRequests = VacationRequest::where('user_id', $user->id)
                    ->where(function($query) use ($currentYear) {
                        $query->whereYear('start_date', $currentYear)
                            ->orWhereYear('end_date', $currentYear);
                    })
                    ->with('approver')
                    ->orderBy('start_date', 'desc')
                    ->get()
                    ->map(function($request) {
                        return [
                            'id' => $request->id,
                            'start_date' => $request->start_date->format('Y-m-d'),
                            'end_date' => $request->end_date->format('Y-m-d'),
                            'days' => $request->days,
                            'dayType' => $request->day_type ?? 'full_day',
                            'dayTypeLabel' => $this->getDayTypeLabel($request->day_type ?? 'full_day'),
                            'actualDays' => $this->calculateActualDays($request),
                            'status' => $request->status,
                            'status_text' => $this->getStatusText($request->status),
                            'status_color' => $this->getStatusColor($request->status),
                            'reason' => $request->rejection_reason, // 'reason' ist jetzt 'rejection_reason'
                            'comment' => $request->notes, // 'comment' ist jetzt 'notes'
                            'approver' => $request->approver ? $request->approver->full_name : null, // full_name statt name
                            'created_at' => $request->created_at->format('Y-m-d H:i'),
                            'updated_at' => $request->updated_at->format('Y-m-d H:i')
                        ];
                    });

                $userData = [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'personnel_number' => $user->employee_number ?? '-',
                    'department' => $user->currentTeam ? $user->currentTeam->name : 'Keine Abteilung',
                    'vacation_days_per_year' => $user->vacation_days_per_year,
                    'carry_over_previous_year' => $carryOverFromPreviousYear,
                    'total_entitlement' => $totalEntitlement,
                    'used_days_total' => $totalUniqueApprovedDaysCurrentYear, // NEU: Einzigartige Tage
                    'remaining_days_total' => $totalEntitlement - $totalUniqueApprovedDaysCurrentYear, // NEU: Einzigartige Tage
                    'monthly_remaining' => [],
                    'vacation_requests' => $allVacationRequests,
                    'is_apprentice' => $user->is_apprentice ?? false,
                    'mentor_name' => $user->mentor ? $user->mentor->full_name : null
                ];

                for ($month = 1; $month <= $displayMonths; $month++) {
                    $userData['monthly_remaining']["month_$month"] = $monthlyRemainingDays[$month];
                }

                $overviewData[] = $userData;
            }

            return response()->json([
                'data' => $overviewData,
                'current_month' => $currentMonth,
                'display_months' => min($currentMonth - 1, 4) + (($currentMonth >= 6) ? 1 : 0)
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

            $users = User::where('is_active', true)
                ->with(['vacationBalances' => function($query) use ($currentYear, $previousYear) {
                    $query->whereIn('year', [$previousYear, $currentYear]);
                }])
                ->get();

            $infoListData = [];

            foreach ($users as $user) {
                $currentYearBalance = $user->vacationBalances->where('year', $currentYear)->first();
                $previousYearBalance = $user->vacationBalances->where('year', $previousYear)->first();

                if (!$currentYearBalance) {
                    $currentYearBalance = new VacationBalance([
                        'total_days' => $user->vacation_days_per_year,
                        'used_days' => 0,
                        'carry_over_days' => 0,
                        'carry_over_used' => 0,
                        'carry_over_expires_at' => Carbon::create($currentYear, 7, 31),
                        'max_carry_over' => 10
                    ]);
                }
                if (!$previousYearBalance) {
                    $previousYearBalance = new VacationBalance([
                        'total_days' => $user->vacation_days_per_year,
                        'used_days' => 0,
                        'carry_over_days' => 0,
                        'carry_over_used' => 0,
                        'carry_over_expires_at' => Carbon::create($previousYear, 7, 31),
                        'max_carry_over' => 10
                    ]);
                }

                $carryOverFromPreviousYear = max(0, min(10, ($previousYearBalance->total_days + $previousYearBalance->carry_over_days) - ($previousYearBalance->used_days + $previousYearBalance->carry_over_used)));
                $totalEntitlement = $currentYearBalance->total_days + $carryOverFromPreviousYear;

                // NEU: Berechne genehmigte Urlaubstage für das aktuelle Jahr (einzigartig)
                $holidaysCurrentYear = $this->getHolidaysForYear($currentYear);
                $approvedVacationDays = $this->calculateTotalUniqueApprovedWorkDays($user, $currentYear, $holidaysCurrentYear);

                // NEU: Berechne genehmigte Urlaubstage für den aktuellen Monat (einzigartig)
                $approvedVacationDaysCurrentMonth = 0.0;
                $currentMonthRequests = VacationRequest::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->where(function($query) use ($currentYear, $currentMonth) {
                        $query->where(function($q) use ($currentYear, $currentMonth) {
                            $q->whereYear('start_date', $currentYear)
                                ->whereMonth('start_date', $currentMonth);
                        })->orWhere(function($q) use ($currentYear, $currentMonth) {
                            $q->whereYear('end_date', $currentYear)
                                ->whereMonth('end_date', $currentMonth);
                        })->orWhere(function($q) use ($currentYear, $currentMonth) {
                            $q->whereYear('start_date', '<', $currentYear)
                                ->whereYear('end_date', '>', $currentYear);
                        })->orWhere(function($q) use ($currentYear, $currentMonth) {
                            $q->whereYear('start_date', $currentYear)
                                ->whereMonth('start_date', '<', $currentMonth)
                                ->whereYear('end_date', $currentYear)
                                ->whereMonth('end_date', '>', $currentMonth);
                        });
                    })
                    ->get();

                $uniqueApprovedDaysCurrentMonth = collect();
                foreach ($currentMonthRequests as $request) {
                    $period = CarbonPeriod::create($request->start_date, $request->end_date);
                    foreach ($period as $date) {
                        if ($date->year !== $currentYear || $date->month !== $currentMonth || $date->isWeekend() || $holidaysCurrentYear->contains(fn($h) => $h->isSameDay($date))) {
                            continue;
                        }
                        if ($request->start_date->eq($request->end_date) && in_array($request->day_type, ['morning', 'afternoon'])) {
                            $uniqueApprovedDaysCurrentMonth->add($date->format('Y-m-d') . '_half');
                        } else {
                            $uniqueApprovedDaysCurrentMonth->add($date->format('Y-m-d') . '_full');
                        }
                    }
                }
                foreach ($uniqueApprovedDaysCurrentMonth->unique() as $dayMarker) {
                    if (str_ends_with($dayMarker, '_half')) {
                        $approvedVacationDaysCurrentMonth += 0.5;
                    } else {
                        $approvedVacationDaysCurrentMonth += 1.0;
                    }
                }


                $remainingDaysCurrentYear = $totalEntitlement - $approvedVacationDays;

                // NEU: Berechne genehmigte Urlaubstage für den Vormonat (einzigartig)
                $holidaysPreviousMonthYear = $this->getHolidaysForYear($previousMonthYear);
                $approvedVacationDaysPreviousMonth = 0.0;
                $previousMonthRequests = VacationRequest::where('user_id', $user->id)
                    ->where('status', 'approved')
                    ->where(function($query) use ($previousMonthYear, $previousMonth) {
                        $query->where(function($q) use ($previousMonthYear, $previousMonth) {
                            $q->whereYear('start_date', $previousMonthYear)
                                ->whereMonth('start_date', $previousMonth);
                        })->orWhere(function($q) use ($previousMonthYear, $previousMonth) {
                            $q->whereYear('end_date', $previousMonthYear)
                                ->whereMonth('end_date', $previousMonth);
                        })->orWhere(function($q) use ($previousMonthYear, $previousMonth) {
                            $q->whereYear('start_date', '<', $previousMonthYear)
                                ->whereYear('end_date', '>', $previousMonthYear);
                        })->orWhere(function($q) use ($previousMonthYear, $previousMonth) {
                            $q->whereYear('start_date', $previousMonthYear)
                                ->whereMonth('start_date', '<', $previousMonth)
                                ->whereYear('end_date', $previousMonthYear)
                                ->whereMonth('end_date', '>', $previousMonth);
                        });
                    })
                    ->get();

                $uniqueApprovedDaysPreviousMonth = collect();
                foreach ($previousMonthRequests as $request) {
                    $period = CarbonPeriod::create($request->start_date, $request->end_date);
                    foreach ($period as $date) {
                        if ($date->year !== $previousMonthYear || $date->month !== $previousMonth || $date->isWeekend() || $holidaysPreviousMonthYear->contains(fn($h) => $h->isSameDay($date))) {
                            continue;
                        }
                        if ($request->start_date->eq($request->end_date) && in_array($request->day_type, ['morning', 'afternoon'])) {
                            $uniqueApprovedDaysPreviousMonth->add($date->format('Y-m-d') . '_half');
                        } else {
                            $uniqueApprovedDaysPreviousMonth->add($date->format('Y-m-d') . '_full');
                        }
                    }
                }
                foreach ($uniqueApprovedDaysPreviousMonth->unique() as $dayMarker) {
                    if (str_ends_with($dayMarker, '_half')) {
                        $approvedVacationDaysPreviousMonth += 0.5;
                    } else {
                        $approvedVacationDaysPreviousMonth += 1.0;
                    }
                }

                $remainingDaysPreviousMonth = $totalEntitlement - $approvedVacationDaysPreviousMonth;

                $userData = [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'personnel_number' => $user->employee_number ?? '-',
                    'department' => $user->currentTeam ? $user->currentTeam->name : 'Keine Abteilung',
                    'vacation_days_per_year' => $user->vacation_days_per_year,
                    'carry_over_previous_year' => $carryOverFromPreviousYear,
                    'total_entitlement' => $totalEntitlement,
                    'approved_days_current_year' => $approvedVacationDays, // NEU: Einzigartige Tage
                    'remaining_days_current_year' => $remainingDaysCurrentYear,
                    'current_month_days' => $approvedVacationDaysCurrentMonth, // NEU: Einzigartige Tage
                    'current_month_name' => $this->getMonthName($currentMonth),
                    'remaining_days_previous_month' => $remainingDaysPreviousMonth, // NEU: Einzigartige Tage
                    'is_apprentice' => $user->is_apprentice ?? false,
                    'mentor_name' => $user->mentor ? $user->mentor->full_name : null
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
}
