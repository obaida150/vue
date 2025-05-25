<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = Subject::where('user_id', Auth::id())
            ->orderBy('year', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'year' => $subject->year,
                    'hours' => $subject->hours,
                    'description' => $subject->description,
                ];
            });

        return response()->json($subjects);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'year' => 'required|integer|between:1,3',
            'hours' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $subject = Subject::create([
            'user_id' => Auth::id(),
            'team_id' => Auth::user()->current_team_id,
            'name' => $validated['name'],
            'year' => $validated['year'],
            'hours' => $validated['hours'] ?? 0,
            'description' => $validated['description'],
        ]);

        return response()->json([
            'message' => 'Fach erfolgreich erstellt',
            'subject' => $subject
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $subject = Subject::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'year' => 'required|integer|between:1,3',
            'hours' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ]);

        $subject->update($validated);

        return response()->json([
            'message' => 'Fach erfolgreich aktualisiert',
            'subject' => $subject
        ]);
    }

    public function destroy($id)
    {
        $subject = Subject::where('user_id', Auth::id())->findOrFail($id);
        $subject->delete();

        return response()->json(['message' => 'Fach erfolgreich gelöscht']);
    }

    public function getByYear($year)
    {
        $subjects = Subject::where('user_id', Auth::id())
            ->where('year', $year)
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'year' => $subject->year,
                    'hours' => $subject->hours,
                    'description' => $subject->description,
                ];
            });

        return response()->json($subjects);
    }
}
