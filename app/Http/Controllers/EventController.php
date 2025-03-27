<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EventController extends Controller
{
    /**
     * Display a listing of the events.
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $query = Event::with('eventType')
                ->where('user_id', $user->id);

            // Filter by date range if provided
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->where(function($q) use ($request) {
                    $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                        ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                        ->orWhere(function($q2) use ($request) {
                            $q2->where('start_date', '<=', $request->start_date)
                                ->where('end_date', '>=', $request->end_date);
                        });
                });
            }

            $events = $query->orderBy('start_date')->get();

            return response()->json($events);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created event in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'event_type_id' => 'required|exists:event_types,id',
                'is_all_day' => 'boolean'
            ]);

            $user = Auth::user();

            $event = new Event();
            $event->user_id = $user->id;
            $event->team_id = $user->currentTeam ? $user->currentTeam->id : null;
            $event->title = $request->title;
            $event->description = $request->description;
            $event->start_date = $request->start_date;
            $event->end_date = $request->end_date;
            $event->event_type_id = $request->event_type_id;
            $event->is_all_day = $request->is_all_day ?? true;
            $event->status = 'approved'; // Auto-approve for now
            $event->created_by = $user->id;
            $event->save();

            return response()->json($event, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified event.
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $event = Event::with('eventType')
                ->where('user_id', $user->id)
                ->findOrFail($id);

            return response()->json($event);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified event in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'event_type_id' => 'required|exists:event_types,id',
                'is_all_day' => 'boolean'
            ]);

            $user = Auth::user();
            $event = Event::where('user_id', $user->id)->findOrFail($id);

            $event->title = $request->title;
            $event->description = $request->description;
            $event->start_date = $request->start_date;
            $event->end_date = $request->end_date;
            $event->event_type_id = $request->event_type_id;
            $event->is_all_day = $request->is_all_day ?? true;
            $event->save();

            return response()->json($event);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified event from storage.
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $event = Event::where('user_id', $user->id)->findOrFail($id);
            $event->delete();

            return response()->json(['message' => 'Event deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

