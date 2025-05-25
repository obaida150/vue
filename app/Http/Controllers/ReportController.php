<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::with(['user', 'instructor'])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'berichtsnummer' => $report->berichtsnummer,
                    'type' => $report->type,
                    'year' => $report->year,
                    'date_from' => $report->date_from ? $report->date_from->format('Y-m-d') : null,
                    'date_to' => $report->date_to ? $report->date_to->format('Y-m-d') : null,
                    'instructor_name' => $report->instructor ? $report->instructor->full_name : 'Unbekannt',
                    'created_at' => $report->created_at->format('d.m.Y'),
                ];
            });

        return response()->json($reports);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:Betrieb,Berufsschule',
            'year' => 'required|integer|between:1,3',
            'berichtsnummer' => 'required|integer',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'instructor_id' => 'required|exists:users,id',
            'subjects_data' => 'nullable|array',
            'activities' => 'nullable|string',
            'unterweisungen' => 'nullable|string',
            'unterricht' => 'nullable|string',
            'abteilung' => 'nullable|string',
        ]);

        // Debug: Log the subjects_data
        \Log::info('Subjects data received:', ['subjects_data' => $validated['subjects_data'] ?? null]);

        // Wenn Berufsschule und subjects_data vorhanden, formatiere die Daten
        $formattedSubjectsData = null;
        if ($validated['type'] === 'Berufsschule' && isset($validated['subjects_data'])) {
            $formattedSubjectsData = [];
            $subjects = Subject::where('user_id', Auth::id())
                ->where('year', $validated['year'])
                ->get();

            foreach ($subjects as $subject) {
                $content = $validated['subjects_data'][$subject->id] ?? '';
                if (!empty($content)) {
                    $formattedSubjectsData[] = [
                        'id' => $subject->id,
                        'name' => $subject->name,
                        'hours' => $subject->hours,
                        'content' => $content
                    ];
                }
            }
        }

        $report = Report::create([
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'year' => $validated['year'],
            'berichtsnummer' => $validated['berichtsnummer'],
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
            'instructor_id' => $validated['instructor_id'],
            'subjects_data' => $formattedSubjectsData,
            'activities' => $validated['activities'] ?? null,
            'unterweisungen' => $validated['unterweisungen'] ?? null,
            'unterricht' => $validated['unterricht'] ?? null,
            'abteilung' => $validated['abteilung'] ?? null,
            'erstellungsdatum' => now()->format('Y-m-d'),
        ]);

        \Log::info('Report created with subjects_data:', ['report_id' => $report->id, 'subjects_data' => $report->subjects_data]);

        return response()->json([
            'message' => 'Bericht erfolgreich erstellt',
            'report' => $report
        ], 201);
    }

    public function show($id)
    {
        $report = Report::with(['user', 'instructor'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json([
            'id' => $report->id,
            'type' => $report->type,
            'year' => $report->year,
            'berichtsnummer' => $report->berichtsnummer,
            'date_from' => $report->date_from ? $report->date_from->format('Y-m-d') : null,
            'date_to' => $report->date_to ? $report->date_to->format('Y-m-d') : null,
            'instructor_id' => $report->instructor_id,
            'subjects_data' => $report->subjects_data,
            'activities' => $report->activities,
            'unterweisungen' => $report->unterweisungen,
            'unterricht' => $report->unterricht,
            'abteilung' => $report->abteilung,
            'instructor' => $report->instructor ? [
                'id' => $report->instructor->id,
                'name' => $report->instructor->full_name
            ] : null
        ]);
    }

    public function update(Request $request, $id)
    {
        $report = Report::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|in:Betrieb,Berufsschule',
            'year' => 'required|integer|between:1,3',
            'berichtsnummer' => 'required|integer',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'instructor_id' => 'required|exists:users,id',
            'subjects_data' => 'nullable|array',
            'activities' => 'nullable|string',
            'unterweisungen' => 'nullable|string',
            'unterricht' => 'nullable|string',
            'abteilung' => 'nullable|string',
        ]);

        $report->update($validated);

        return response()->json([
            'message' => 'Bericht erfolgreich aktualisiert',
            'report' => $report
        ]);
    }

    public function destroy($id)
    {
        $report = Report::where('user_id', Auth::id())->findOrFail($id);
        $report->delete();

        return response()->json(['message' => 'Bericht erfolgreich gelöscht']);
    }

    public function downloadPdf($id)
    {
        $report = Report::with(['user', 'instructor'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        \Log::info('PDF generation for report:', ['report_id' => $report->id, 'subjects_data' => $report->subjects_data]);

        $pdf = Pdf::loadView('reports.pdf', compact('report'));

        return $pdf->download('bericht_' . $report->berichtsnummer . '.pdf');
    }

    public function getInstructors()
    {
        $instructors = User::where('is_ausbilder', true)
            ->get()
            ->map(function ($instructor) {
                return [
                    'id' => $instructor->id,
                    'name' => $instructor->full_name
                ];
            });

        return response()->json($instructors);
    }
}
