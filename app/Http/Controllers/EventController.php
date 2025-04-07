<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();

        $events = Event::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhere('created_by', $user->id);
        })->get();

        return response()->json($events);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
// PHP
    public function store(Request $request)
    {
        try {
            // Validierung
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'event_type_id' => 'required|exists:event_types,id',
                'is_all_day' => 'boolean'
            ]);

            $event = new Event();
            $event->user_id = Auth::id();
            $event->title = $request->input('title');
            $event->description = $request->input('description');
            $event->start_date = $request->input('start_date');
            $event->end_date = $request->input('end_date');
            $event->event_type_id = $request->input('event_type_id');
            $event->is_all_day = $request->input('is_all_day', false);
            $event->status = 'approved';
            $event->created_at = now();
            $event->updated_at = now();

            // Hier sicherstellen, dass team_id gesetzt ist
            if (Auth::user()->current_team_id) {
                $event->team_id = Auth::user()->current_team_id;  // Setze team_id
            } else {
                $event->team_id = null; // Setze als null oder eine Standardwert wenn kein Team existiert
            }

            $event->save();

            return response()->json($event, 201);
        } catch (\Exception $e) {
            Log::error('Fehler beim Speichern des Ereignisses: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function storeWeekPlan(Request $request)
    {
        try {
            // Validierung der Eingabedaten
            // Beispielvalidierung, passe sie nach Bedarf an
            $request->validate([
                'events' => 'required|array',
                // Füge weitere Validierungsregeln für die einzelnen Events hinzu
            ]);

            // Logik zum Speichern der Wochenplanung
            foreach ($request->input('events') as $eventData) {
                // Speichern jedes einzelnen Events, hier kannst du die Logik anpassen
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Fehler beim Speichern der Wochenplanung: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // php
    public function show($id)
    {
        $user = Auth::user();

        // Lade die Relation "eventType", damit der Typ im Frontend bereitsteht
        $event = Event::with('eventType')->where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere('created_by', $user->id);
        })->findOrFail($id);

        return response()->json($event);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            // Finde das Event anhand der übergebenen ID
            $event = Event::find($id);
            if (!$event) {
                return response()->json(['error' => 'Ereignis nicht gefunden'], 404);
            }

            // Suche zuerst nach Event-Typ anhand der event_type_id, falls vorhanden
            $eventType = null;
            if ($request->has('event_type_id')) {
                $eventType = EventType::find($request->input('event_type_id'));
            }

            // Falls kein Event-Typ über die ID gefunden wird, versuche den Namen zu verwenden
            if (!$eventType) {
                $eventTypeName = $request->input('event_type');
                if ($eventTypeName) {
                    $eventType = EventType::where('name', $eventTypeName)->first();
                    if (!$eventType) {
                        // Fall-back: Case-insensitive Suche
                        $eventType = EventType::whereRaw('LOWER(name) = ?', [strtolower($eventTypeName)])->first();
                    }
                }
            }

            // Falls weiterhin kein Event-Typ gefunden wurde, verwende oder erstelle "Sonstiges"
            if (!$eventType) {
                $eventType = EventType::where('name', 'Sonstiges')->first();
                if (!$eventType) {
                    $eventType = new EventType();
                    $eventType->name = 'Sonstiges';
                    $eventType->color = '#607D8B';
                    $eventType->requires_approval = false;
                    $eventType->save();
                }
            }

            // Aktualisiere das Event
            $event->event_type_id = $eventType->id;
            $event->title = $request->input('title');
            $event->description = $request->input('description');
            $event->start_date = $request->input('start_date');
            $event->end_date = $request->input('end_date');
            $event->is_all_day = $request->input('is_all_day', $event->is_all_day);

            // Aktualisiere den Status, falls der Event-Typ geändert wurde
            if ($event->isDirty('event_type_id')) {
                $event->status = $eventType->requires_approval ? 'pending' : 'approved';
            }

            $event->save();

            return response()->json([
                'message' => 'Ereignis erfolgreich aktualisiert',
                'event' => $event
            ]);
        } catch (\Exception $e) {
            Log::error('Fehler beim Aktualisieren des Ereignisses: ' . $e->getMessage(), [
                'id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            // Simplified approach - just try to find and delete the event
            $event = Event::find($id);

            if (!$event) {
                return response()->json(['error' => 'Ereignis nicht gefunden'], 404);
            }

            // Delete the event without additional checks
            $event->delete();

            return response()->json(['message' => 'Ereignis erfolgreich gelöscht']);
        } catch (\Exception $e) {
            Log::error('Fehler beim Löschen des Ereignisses: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

