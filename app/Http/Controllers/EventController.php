<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventType;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    /**
     * Alle Ereignistypen abrufen
     */
    public function getEventTypes()
    {
        $eventTypes = EventType::all();
        return response()->json($eventTypes);
    }

    /**
     * Ereignisse für den aktuellen Benutzer abrufen
     */
    public function getUserEvents(Request $request)
    {
        $user = Auth::user();
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Event::where('user_id', $user->id);

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

        $events = $query->with(['eventType'])->get();

        return response()->json($events);
    }

    /**
     * Ereignisse für ein Team abrufen
     */
    public function getTeamEvents(Request $request, Team $team)
    {
        $user = Auth::user();

        // Prüfen, ob der Benutzer Mitglied des Teams ist
        if (!$user->belongsToTeam($team)) {
            return response()->json(['message' => 'Nicht autorisiert'], 403);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Event::where('team_id', $team->id);

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

        $events = $query->with(['user', 'eventType'])->get();

        return response()->json($events);
    }

    /**
     * Ein neues Ereignis erstellen
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'team_id' => 'required|exists:teams,id',
            'event_type_id' => 'required|exists:event_types,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_all_day' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Prüfen, ob der Benutzer Mitglied des Teams ist
        $team = Team::find($request->team_id);
        if (!$user->belongsToTeam($team)) {
            return response()->json(['message' => 'Nicht autorisiert'], 403);
        }

        // Prüfen, ob der Ereignistyp Genehmigung erfordert
        $eventType = EventType::find($request->event_type_id);
        $status = $eventType->requires_approval ? 'pending' : 'approved';

        $event = Event::create([
            'user_id' => $user->id,
            'team_id' => $request->team_id,
            'event_type_id' => $request->event_type_id,
            'title' => $request->title,
            'description' => $request->description,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'is_all_day' => $request->is_all_day ?? true,
            'status' => $status,
            'created_by' => $user->id,
        ]);

        return response()->json($event->load('eventType'), 201);
    }

    /**
     * Ein Ereignis aktualisieren
     */
    public function update(Request $request, Event $event)
    {
        $user = Auth::user();

        // Prüfen, ob der Benutzer das Ereignis bearbeiten darf
        if ($event->user_id !== $user->id && !$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Nicht autorisiert'], 403);
        }

        $validator = Validator::make($request->all(), [
            'event_type_id' => 'exists:event_types,id',
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'is_all_day' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Wenn der Ereignistyp geändert wird, prüfen, ob der neue Typ Genehmigung erfordert
        if ($request->has('event_type_id') && $request->event_type_id != $event->event_type_id) {
            $eventType = EventType::find($request->event_type_id);
            if ($eventType->requires_approval) {
                $event->status = 'pending';
                $event->approved_by = null;
                $event->approved_date = null;
            }
        }

        $event->update($request->only([
            'event_type_id',
            'title',
            'description',
            'start_date',
            'end_date',
            'is_all_day',
        ]));

        return response()->json($event->load('eventType'));
    }

    /**
     * Ein Ereignis löschen
     */
    public function destroy(Event $event)
    {
        $user = Auth::user();

        // Prüfen, ob der Benutzer das Ereignis löschen darf
        if ($event->user_id !== $user->id && !$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Nicht autorisiert'], 403);
        }

        $event->delete();

        return response()->json(['message' => 'Ereignis gelöscht']);
    }

    /**
     * Ein Ereignis genehmigen
     */
    public function approve(Event $event)
    {
        $user = Auth::user();

        // Prüfen, ob der Benutzer das Ereignis genehmigen darf
        if (!$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Nicht autorisiert'], 403);
        }

        $event->status = 'approved';
        $event->approved_by = $user->id;
        $event->approved_date = now();
        $event->save();

        return response()->json($event->load('eventType'));
    }

    /**
     * Ein Ereignis ablehnen
     */
    public function reject(Request $request, Event $event)
    {
        $user = Auth::user();

        // Prüfen, ob der Benutzer das Ereignis ablehnen darf
        if (!$user->isAdmin() && !$user->isManager()) {
            return response()->json(['message' => 'Nicht autorisiert'], 403);
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $event->status = 'rejected';
        $event->rejected_by = $user->id;
        $event->rejected_date = now();
        $event->rejection_reason = $request->rejection_reason;
        $event->save();

        return response()->json($event->load('eventType'));
    }
}

