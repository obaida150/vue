<?php

use App\Models\Event;
use App\Models\EventType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EventController;
use App\Http\Controllers\VacationController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\RoomStatusController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {
    // Benutzer-Routen
    Route::get('/user', [UserController::class, 'getCurrentUser']);
    Route::get('/birthdays', [UserController::class, 'getBirthdays']);

    // Simple delete route without CSRF protection
    Route::delete('/events/{id}', function($id) {
        try {
            // Find the event
            $event = Event::find($id);

            if (!$event) {
                return response()->json(['error' => 'Ereignis nicht gefunden'], 404);
            }

            // Delete the event
            $event->delete();

            return response()->json(['message' => 'Ereignis erfolgreich gelöscht']);
        } catch (\Exception $e) {
            Log::error('API Delete Error: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });

    // Debug route to test database connection
    Route::get('/debug/event-types', function() {
        try {
            $types = EventType::all();
            return response()->json([
                'success' => true,
                'count' => count($types),
                'data' => $types
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    });

    // Super simple create route with minimal code
    Route::get('/events/simple-create', function(Request $request) {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Not authenticated'], 401);
            }

            // Create a very basic event with minimal fields
            $event = new Event();
            $event->user_id = $user->id;
            $event->created_by = $user->id;
            $event->title = $request->query('title', 'Test Event');
            $event->start_date = date('Y-m-d');
            $event->end_date = date('Y-m-d');
            $event->is_all_day = true;
            $event->status = 'approved';

            // Find any event type
            $eventType = EventType::first();
            if ($eventType) {
                $event->event_type_id = $eventType->id;
            }

            // Log what we're about to save
            Log::info('About to save simple event', [
                'event_data' => $event->toArray()
            ]);

            $event->save();

            return response()->json([
                'success' => true,
                'message' => 'Simple event created',
                'event' => $event
            ]);
        } catch (\Exception $e) {
            Log::error('Simple create error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    });

    // Simple create route without CSRF protection - with detailed error handling
// php
// php
// php
    Route::post('/events', function(Request $request) {
        try {
            $user = auth('sanctum')->user() ?: Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Nicht authentifiziert'], 401);
            }

            Log::info('Creating event with data:', [
                'request_data' => $request->all(),
                'user_id' => $user->id
            ]);

            // Sicherstellen, dass alle erforderlichen Parameter vorhanden sind
            if (!$request->filled('title') || !$request->filled('start_date') || !$request->filled('end_date')) {
                return response()->json(['error' => 'Fehlende erforderliche Felder'], 422);
            }

            $eventTypeName = $request->input('event_type', 'Sonstiges');
            Log::info('Looking for event type:', ['name' => $eventTypeName]);

            $eventType = EventType::where('name', $eventTypeName)->first();
            if (!$eventType) {
                $eventType = EventType::whereRaw('LOWER(name) = ?', [strtolower($eventTypeName)])->first();
            }
            if (!$eventType) {
                $eventType = EventType::firstOrCreate(
                    ['name' => 'Sonstiges'],
                    ['color' => '#607D8B', 'requires_approval' => false]
                );
            }

            // Neues Ereignis erstellen
            $event = new Event();
            $event->user_id = $user->id;
            $event->created_by = $user->id;
            $event->event_type_id = $eventType->id;
            $event->title = $request->input('title');
            $event->description = $request->input('description', '');
            $event->start_date = $request->input('start_date');
            $event->end_date = $request->input('end_date');
            $event->is_all_day = $request->boolean('is_all_day', false);
            $event->status = $eventType->requires_approval ? 'pending' : 'approved';

            if ($user->current_team_id) {
                $event->team_id = $user->current_team_id;
            }

            Log::info('About to save event', ['event_data' => $event->toArray()]);
            $event->save();
            Log::info('Event saved successfully', ['id' => $event->id]);

            return response()->json([
                'message' => 'Ereignis erfolgreich erstellt',
                'event' => $event
            ], 201);
        } catch (\Exception $e) {
            Log::error('API Create Error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });

    // Simple update route without CSRF protection
    Route::put('/events/{id}', function(Request $request, $id) {
        try {
            // Find the event
            $event = Event::find($id);

            if (!$event) {
                return response()->json(['error' => 'Ereignis nicht gefunden'], 404);
            }

            // Find event type
            $eventTypeName = $request->input('event_type', 'Sonstiges');
            $eventType = EventType::where('name', $eventTypeName)->first();

            if (!$eventType) {
                // Try case-insensitive search
                $eventType = EventType::whereRaw('LOWER(name) = ?', [strtolower($eventTypeName)])->first();

                if (!$eventType) {
                    // Default to "Sonstiges" if not found
                    $eventType = EventType::where('name', 'Sonstiges')->first();

                    if (!$eventType) {
                        // Create a default event type if none exists
                        $eventType = new EventType();
                        $eventType->name = 'Sonstiges';
                        $eventType->color = '#607D8B';
                        $eventType->requires_approval = false;
                        $eventType->save();
                    }
                }
            }

            // Update event
            $event->event_type_id = $eventType->id;
            $event->title = $request->input('title');
            $event->description = $request->input('description');
            $event->start_date = $request->input('start_date');
            $event->end_date = $request->input('end_date');
            $event->is_all_day = $request->input('is_all_day', $event->is_all_day);

            // Update status if event type changed
            if ($event->isDirty('event_type_id')) {
                $event->status = $eventType->requires_approval ? 'pending' : 'approved';
            }

            $event->save();

            return response()->json([
                'message' => 'Ereignis erfolgreich aktualisiert',
                'event' => $event
            ]);
        } catch (\Exception $e) {
            Log::error('API Update Error: ' . $e->getMessage(), [
                'id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });

    // Also add a POST route for updates to handle _method=PUT
    Route::post('/events/{id}', function(Request $request, $id) {
        try {
            // Find the event
            $event = Event::find($id);

            if (!$event) {
                return response()->json(['error' => 'Ereignis nicht gefunden'], 404);
            }

            // Find event type
            $eventTypeName = $request->input('event_type', 'Sonstiges');
            $eventType = EventType::where('name', $eventTypeName)->first();

            if (!$eventType) {
                // Try case-insensitive search
                $eventType = EventType::whereRaw('LOWER(name) = ?', [strtolower($eventTypeName)])->first();

                if (!$eventType) {
                    // Default to "Sonstiges" if not found
                    $eventType = EventType::where('name', 'Sonstiges')->first();

                    if (!$eventType) {
                        // Create a default event type if none exists
                        $eventType = new EventType();
                        $eventType->name = 'Sonstiges';
                        $eventType->color = '#607D8B';
                        $eventType->requires_approval = false;
                        $eventType->save();
                    }
                }
            }

            // Update event
            $event->event_type_id = $eventType->id;
            $event->title = $request->input('title');
            $event->description = $request->input('description');
            $event->start_date = $request->input('start_date');
            $event->end_date = $request->input('end_date');
            $event->is_all_day = $request->input('is_all_day', $event->is_all_day);

            // Update status if event type changed
            if ($event->isDirty('event_type_id')) {
                $event->status = $eventType->requires_approval ? 'pending' : 'approved';
            }

            $event->save();

            return response()->json([
                'message' => 'Ereignis erfolgreich aktualisiert',
                'event' => $event
            ]);
        } catch (\Exception $e) {
            Log::error('API Update Error (POST): ' . $e->getMessage(), [
                'id' => $id,
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });

    // Add GET routes for create and update with detailed error handling
    Route::get('/events/create', function(Request $request) {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Nicht authentifiziert'], 401);
            }

            // Log the request data
            Log::info('Creating event with GET data:', [
                'request_data' => $request->query(),
                'user_id' => $user->id
            ]);

            // Find event type - with detailed logging
            $eventTypeName = $request->query('event_type', 'Sonstiges');
            Log::info('Looking for event type:', ['name' => $eventTypeName]);

            $eventType = null;

            // Try to find by exact name
            $eventType = EventType::where('name', $eventTypeName)->first();
            if ($eventType) {
                Log::info('Found event type by exact name', ['id' => $eventType->id]);
            } else {
                // Try case-insensitive search
                $eventType = EventType::whereRaw('LOWER(name) = ?', [strtolower($eventTypeName)])->first();
                if ($eventType) {
                    Log::info('Found event type by case-insensitive name', ['id' => $eventType->id]);
                } else {
                    // Default to "Sonstiges" if not found
                    $eventType = EventType::where('name', 'Sonstiges')->first();
                    if ($eventType) {
                        Log::info('Using default "Sonstiges" event type', ['id' => $eventType->id]);
                    } else {
                        // Create a default event type if none exists
                        Log::info('Creating new default event type "Sonstiges"');
                        $eventType = new EventType();
                        $eventType->name = 'Sonstiges';
                        $eventType->color = '#607D8B';
                        $eventType->requires_approval = false;
                        $eventType->save();
                        Log::info('Created new default event type', ['id' => $eventType->id]);
                    }
                }
            }

            // Create new event
            $event = new Event();
            $event->user_id = $user->id;
            $event->created_by = $user->id;
            $event->event_type_id = $eventType->id;
            $event->title = $request->query('title', 'Neues Ereignis');
            $event->description = $request->query('description', '');
            $event->start_date = $request->query('start_date', date('Y-m-d'));
            $event->end_date = $request->query('end_date', date('Y-m-d'));
            $event->is_all_day = $request->query('is_all_day') === '1';
            $event->status = $eventType->requires_approval ? 'pending' : 'approved';

            // Set team_id if available
            if ($user->current_team_id) {
                $event->team_id = $user->current_team_id;
            }

            // Log what we're about to save
            Log::info('About to save event via GET', [
                'event_data' => $event->toArray()
            ]);

            $event->save();

            Log::info('Event saved successfully via GET', ['id' => $event->id]);

            return response()->json([
                'message' => 'Ereignis erfolgreich erstellt',
                'event' => $event
            ], 201);
        } catch (\Exception $e) {
            Log::error('GET Create Error: ' . $e->getMessage(), [
                'request_data' => $request->query(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });

    Route::get('/events/{id}/update', function(Request $request, $id) {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Nicht authentifiziert'], 401);
            }

            // Find the event
            $event = Event::find($id);

            if (!$event) {
                return response()->json(['error' => 'Ereignis nicht gefunden'], 404);
            }

            // Find event type
            $eventTypeName = $request->query('event_type', 'Sonstiges');
            $eventType = EventType::where('name', $eventTypeName)->first();

            if (!$eventType) {
                // Try case-insensitive search
                $eventType = EventType::whereRaw('LOWER(name) = ?', [strtolower($eventTypeName)])->first();

                if (!$eventType) {
                    // Default to "Sonstiges" if not found
                    $eventType = EventType::where('name', 'Sonstiges')->first();

                    if (!$eventType) {
                        // Create a default event type if none exists
                        $eventType = new EventType();
                        $eventType->name = 'Sonstiges';
                        $eventType->color = '#607D8B';
                        $eventType->requires_approval = false;
                        $eventType->save();
                    }
                }
            }

            // Update event
            $event->event_type_id = $eventType->id;
            $event->title = $request->query('title', $event->title);
            $event->description = $request->query('description', $event->description);
            $event->start_date = $request->query('start_date', $event->start_date);
            $event->end_date = $request->query('end_date', $event->end_date);
            $event->is_all_day = $request->query('is_all_day') === '1';

            // Update status if event type changed
            if ($event->isDirty('event_type_id')) {
                $event->status = $eventType->requires_approval ? 'pending' : 'approved';
            }

            $event->save();

            return response()->json([
                'message' => 'Ereignis erfolgreich aktualisiert',
                'event' => $event
            ]);
        } catch (\Exception $e) {
            Log::error('GET Update Error: ' . $e->getMessage(), [
                'id' => $id,
                'request_data' => $request->query(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });

    // Team-Routen
    Route::get('/teams', [TeamController::class, 'getUserTeams']);
    Route::get('/teams/{team}/members', [TeamController::class, 'getTeamMembers']);
    Route::get('/teams/{team}/homeoffice-rules', [TeamController::class, 'getTeamHomeofficeRules']);

    // Ereignis-Routen
    Route::get('/event-types', [EventController::class, 'getEventTypes']);
    Route::get('/events', [EventController::class, 'getUserEvents']);
    Route::get('/teams/{team}/events', [EventController::class, 'getTeamEvents']);
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{event}', [EventController::class, 'update']);
    Route::delete('/events/{event}', [EventController::class, 'destroy']);
    Route::post('/events/{event}/approve', [EventController::class, 'approve']);
    Route::post('/events/{event}/reject', [EventController::class, 'reject']);
    Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');
    Route::post('/events/week-plan', [EventController::class, 'storeWeekPlan'])->name('api.events.week-plan');

    // Urlaubs-Routen
    Route::middleware('auth:sanctum')->post('/vacation/cancel/{id}', [VacationController::class, 'cancelRequest'])->name('api.vacation.cancel');
    Route::get('/vacation/balance', [VacationController::class, 'getUserBalance']);
    Route::get('/vacation/requests', [VacationController::class, 'getUserRequests']);
    Route::get('/teams/{team}/vacation-requests', [VacationController::class, 'getTeamRequests']);
    Route::post('/vacation/requests', [VacationController::class, 'store']);
    Route::post('/vacation/requests/{vacationRequest}/approve', [VacationController::class, 'approve']);
    Route::post('/vacation/requests/{vacationRequest}/reject', [VacationController::class, 'reject']);
    Route::delete('/vacation/requests/{vacationRequest}', [VacationController::class, 'cancel']);
    Route::get('/vacation/substitutes', [VacationController::class, 'getAvailableSubstitutes']);
    // HR: Alle Mitarbeiter abrufen
    Route::get('/vacation/hr-entry', [VacationController::class, 'showHrEntry'])->name('vacation.hr.entry');
    Route::post('/vacation/hr/store-for-employee', [VacationController::class, 'storeForEmployee'])->name('vacation.hr.store');
});

// Room Status API - without authentication
Route::get('/room-status', [RoomStatusController::class, 'api']);
