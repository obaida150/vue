<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\EventType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            // Prüfen, ob der Benutzer ein Abteilungsleiter ist
            $isTeamManager = false;
            $teamId = null;

            if ($user->currentTeam) {
                $teamId = $user->currentTeam->id;

                // Prüfen, ob der Benutzer die Rolle "Abteilungsleiter" in der team_user Tabelle hat
                $teamUserRole = DB::table('team_user')
                    ->where('team_id', $teamId)
                    ->where('user_id', $user->id)
                    ->value('role');

                $isTeamManager = ($teamUserRole === 'Abteilungsleiter');
            }

            $query = Event::with('eventType', 'user')
                ->where(function($query) use ($user, $isTeamManager, $teamId) {
                    // Eigene Ereignisse
                    $query->where('user_id', $user->id);

                    // Wenn HR-Benutzer, dann alle Ereignisse
                    if ($user->role_id === 2) {
                        $query->orWhereNotNull('id'); // Immer wahr
                    }
                    // Wenn Abteilungsleiter, dann Ereignisse der Teammitglieder
                    else if ($isTeamManager && $teamId) {
                        $query->orWhere(function($q) use ($teamId) {
                            $q->where('team_id', $teamId);
                        });
                    }
                });

            if ($startDate && $endDate) {
                $query->where(function($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function($query) use ($startDate, $endDate) {
                            $query->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                });
            }

            $events = $query->get();

            return $events->map(function($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'start_date' => $event->start_date->format('Y-m-d'),
                    'end_date' => $event->end_date->format('Y-m-d'),
                    'is_all_day' => $event->is_all_day,
                    'status' => $event->status,
                    'event_type_id' => $event->event_type_id,
                    'event_type' => $event->eventType ? $event->eventType->name : null,
                    'user_id' => $event->user_id,
                    'team_id' => $event->team_id,
                    'employee_name' => $event->user ? $event->user->first_name . ' ' . $event->user->last_name : null
                ];
            });
        } catch (\Exception $e) {
            Log::error('Fehler beim Abrufen der Ereignisse: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            // Prüfen, ob der Benutzer ein Abteilungsleiter ist
            $isTeamManager = false;
            $teamId = null;

            if ($user->currentTeam) {
                $teamId = $user->currentTeam->id;

                // Prüfen, ob der Benutzer die Rolle "Abteilungsleiter" in der team_user Tabelle hat
                $teamUserRole = DB::table('team_user')
                    ->where('team_id', $teamId)
                    ->where('user_id', $user->id)
                    ->value('role');

                $isTeamManager = ($teamUserRole === 'Abteilungsleiter');
            }

            // Validierung
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'is_all_day' => 'boolean',
                'event_type_id' => 'required|exists:event_types,id',
                'user_id' => 'nullable|exists:users,id'
            ]);

            // Prüfen, ob der Benutzer berechtigt ist, für andere Benutzer Ereignisse zu erstellen
            $userId = $validated['user_id'] ?? $user->id;

            // Wenn ein anderer Benutzer als der aktuelle ausgewählt wurde
            if ($userId != $user->id) {
                // HR-Benutzer dürfen für alle Benutzer Ereignisse erstellen
                if ($user->role_id === 2) {
                    // Erlaubt
                }
                // Abteilungsleiter dürfen nur für Mitglieder ihres Teams Ereignisse erstellen
                else if ($isTeamManager) {
                    // Prüfen, ob der ausgewählte Benutzer im Team des Abteilungsleiters ist
                    $isTeamMember = DB::table('team_user')
                        ->where('team_id', $teamId)
                        ->where('user_id', $userId)
                        ->exists();

                    if (!$isTeamMember) {
                        return response()->json(['error' => 'Sie sind nicht berechtigt, Ereignisse für Benutzer außerhalb Ihres Teams zu erstellen.'], 403);
                    }
                }
                else {
                    return response()->json(['error' => 'Sie sind nicht berechtigt, Ereignisse für andere Benutzer zu erstellen.'], 403);
                }

                // Prüfen, ob es sich um einen Krankheitseintrag handelt
                $eventType = EventType::find($validated['event_type_id']);
                if ($eventType && $eventType->name === 'Krankheit' && $user->role_id !== 2) {
                    return response()->json(['error' => 'Nur HR-Mitarbeiter können Krankheitseinträge erstellen.'], 403);
                }
            }

            // Ereignis erstellen
            $event = new Event();
            $event->title = $validated['title'];
            $event->description = $validated['description'] ?? null;
            $event->start_date = $validated['start_date'];
            $event->end_date = $validated['end_date'];
            $event->is_all_day = $validated['is_all_day'] ?? true;
            $event->event_type_id = $validated['event_type_id'];
            $event->user_id = $userId;
            // Team-ID des Benutzers ermitteln und setzen
            $userTeamId = User::find($userId)->current_team_id;
            $event->team_id = $userTeamId;
            $event->created_by = $user->id;
            $event->status = 'approved'; // Standardmäßig genehmigt

            $event->save();

            return response()->json($event, 201);
        } catch (\Exception $e) {
            Log::error('Fehler beim Erstellen des Ereignisses: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $user = Auth::user();
            $event = Event::with('eventType', 'user')->findOrFail($id);

            // Prüfen, ob der Benutzer ein Abteilungsleiter ist
            $isTeamManager = false;
            $teamId = null;

            if ($user->currentTeam) {
                $teamId = $user->currentTeam->id;

                // Prüfen, ob der Benutzer die Rolle "Abteilungsleiter" in der team_user Tabelle hat
                $teamUserRole = DB::table('team_user')
                    ->where('team_id', $teamId)
                    ->where('user_id', $user->id)
                    ->value('role');

                $isTeamManager = ($teamUserRole === 'Abteilungsleiter');
            }

            // Prüfen, ob der Benutzer berechtigt ist, das Ereignis zu sehen
            $canView = false;

            // Eigene Ereignisse darf jeder sehen
            if ($event->user_id === $user->id) {
                $canView = true;
            }
            // HR-Benutzer dürfen alle Ereignisse sehen
            else if ($user->role_id === 2) {
                $canView = true;
            }
            // Abteilungsleiter dürfen Ereignisse ihrer Teammitglieder sehen
            else if ($isTeamManager && $event->team_id === $teamId) {
                $canView = true;
            }

            if (!$canView) {
                return response()->json(['error' => 'Sie sind nicht berechtigt, dieses Ereignis zu sehen.'], 403);
            }

            return response()->json([
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start_date' => $event->start_date->format('Y-m-d'),
                'end_date' => $event->end_date->format('Y-m-d'),
                'is_all_day' => $event->is_all_day,
                'status' => $event->status,
                'event_type_id' => $event->event_type_id,
                'event_type' => $event->eventType ? $event->eventType->name : null,
                'user_id' => $event->user_id,
                'team_id' => $event->team_id,
                'employee_name' => $event->user ? $event->user->first_name . ' ' . $event->user->last_name : null
            ]);
        } catch (\Exception $e) {
            Log::error('Fehler beim Abrufen des Ereignisses: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $user = Auth::user();
            $event = Event::findOrFail($id);

            // Prüfen, ob der Benutzer ein Abteilungsleiter ist
            $isTeamManager = false;
            $teamId = null;

            if ($user->currentTeam) {
                $teamId = $user->currentTeam->id;

                // Prüfen, ob der Benutzer die Rolle "Abteilungsleiter" in der team_user Tabelle hat
                $teamUserRole = DB::table('team_user')
                    ->where('team_id', $teamId)
                    ->where('user_id', $user->id)
                    ->value('role');

                $isTeamManager = ($teamUserRole === 'Abteilungsleiter');
            }

            // Prüfen, ob der Benutzer berechtigt ist, das Ereignis zu aktualisieren
            $canEdit = false;

            // Eigene Ereignisse darf jeder bearbeiten
            if ($event->user_id === $user->id) {
                $canEdit = true;
            }
            // HR-Benutzer dürfen alle Ereignisse bearbeiten
            else if ($user->role_id === 2) {
                $canEdit = true;
            }
            // Abteilungsleiter dürfen Ereignisse ihrer Teammitglieder bearbeiten
            else if ($isTeamManager && $event->team_id === $teamId) {
                $canEdit = true;
            }

            if (!$canEdit) {
                return response()->json(['error' => 'Sie sind nicht berechtigt, dieses Ereignis zu aktualisieren.'], 403);
            }

            // Validierung
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'is_all_day' => 'boolean',
                'event_type_id' => 'required|exists:event_types,id',
                'user_id' => 'nullable|exists:users,id'
            ]);

            // Prüfen, ob der Benutzer berechtigt ist, für andere Benutzer Ereignisse zu aktualisieren
            $userId = $validated['user_id'] ?? $event->user_id;

            // Wenn ein anderer Benutzer als der aktuelle ausgewählt wurde
            if ($userId != $user->id) {
                // HR-Benutzer dürfen für alle Benutzer Ereignisse aktualisieren
                if ($user->role_id === 2) {
                    // Erlaubt
                }
                // Abteilungsleiter dürfen nur für Mitglieder ihres Teams Ereignisse aktualisieren
                else if ($isTeamManager) {
                    // Prüfen, ob der ausgewählte Benutzer im Team des Abteilungsleiters ist
                    $isTeamMember = DB::table('team_user')
                        ->where('team_id', $teamId)
                        ->where('user_id', $userId)
                        ->exists();

                    if (!$isTeamMember) {
                        return response()->json(['error' => 'Sie sind nicht berechtigt, Ereignisse für Benutzer außerhalb Ihres Teams zu aktualisieren.'], 403);
                    }
                }
                else {
                    return response()->json(['error' => 'Sie sind nicht berechtigt, Ereignisse für andere Benutzer zu aktualisieren.'], 403);
                }

                // Prüfen, ob es sich um einen Krankheitseintrag handelt
                $eventType = EventType::find($validated['event_type_id']);
                if ($eventType && $eventType->name === 'Krankheit' && $user->role_id !== 2) {
                    return response()->json(['error' => 'Nur HR-Mitarbeiter können Krankheitseinträge aktualisieren.'], 403);
                }
            }

            // Ereignis aktualisieren
            $event->title = $validated['title'];
            $event->description = $validated['description'] ?? null;
            $event->start_date = $validated['start_date'];
            $event->end_date = $validated['end_date'];
            $event->is_all_day = $validated['is_all_day'] ?? true;
            $event->event_type_id = $validated['event_type_id'];
            $event->user_id = $userId;

            // Team-ID des Benutzers ermitteln und setzen
            $userTeamId = User::find($userId)->current_team_id;
            $event->team_id = $userTeamId;

            // Prüfen, ob die Spalte updated_by existiert, bevor wir versuchen, sie zu aktualisieren
            if (Schema::hasColumn('events', 'updated_by')) {
                $event->updated_by = $user->id;
            }

            $event->save();

            return response()->json($event);
        } catch (\Exception $e) {
            Log::error('Fehler beim Aktualisieren des Ereignisses: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $user = Auth::user();
            $event = Event::findOrFail($id);

            // Prüfen, ob der Benutzer ein Abteilungsleiter ist
            $isTeamManager = false;
            $teamId = null;

            if ($user->currentTeam) {
                $teamId = $user->currentTeam->id;

                // Prüfen, ob der Benutzer die Rolle "Abteilungsleiter" in der team_user Tabelle hat
                $teamUserRole = DB::table('team_user')
                    ->where('team_id', $teamId)
                    ->where('user_id', $user->id)
                    ->value('role');

                $isTeamManager = ($teamUserRole === 'Abteilungsleiter');
            }

            // Prüfen, ob der Benutzer berechtigt ist, das Ereignis zu löschen
            $canDelete = false;

            // Eigene Ereignisse darf jeder löschen
            if ($event->user_id === $user->id) {
                $canDelete = true;
            }
            // HR-Benutzer dürfen alle Ereignisse löschen
            else if ($user->role_id === 2) {
                $canDelete = true;
            }
            // Abteilungsleiter dürfen Ereignisse ihrer Teammitglieder löschen
            else if ($isTeamManager && $event->team_id === $teamId) {
                $canDelete = true;
            }

            if (!$canDelete) {
                return response()->json(['error' => 'Sie sind nicht berechtigt, dieses Ereignis zu löschen.'], 403);
            }

            $event->delete();

            return response()->json(['message' => 'Ereignis erfolgreich gelöscht.']);
        } catch (\Exception $e) {
            Log::error('Fehler beim Löschen des Ereignisses: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store multiple events for a week plan.
     */
    public function storeWeekPlan(Request $request)
    {
        try {
            $user = Auth::user();

            // Prüfen, ob der Benutzer ein Abteilungsleiter ist
            $isTeamManager = false;
            $teamId = null;

            if ($user->currentTeam) {
                $teamId = $user->currentTeam->id;

                // Prüfen, ob der Benutzer die Rolle "Abteilungsleiter" in der team_user Tabelle hat
                $teamUserRole = DB::table('team_user')
                    ->where('team_id', $teamId)
                    ->where('user_id', $user->id)
                    ->value('role');

                $isTeamManager = ($teamUserRole === 'Abteilungsleiter');
            }

            // Validierung
            $validated = $request->validate([
                'events' => 'required|array',
                'events.*.title' => 'required|string|max:255',
                'events.*.description' => 'nullable|string',
                'events.*.start_date' => 'required|date',
                'events.*.end_date' => 'required|date|after_or_equal:events.*.start_date',
                'events.*.is_all_day' => 'boolean',
                'events.*.event_type_id' => 'required|exists:event_types,id',
                'events.*.user_id' => 'nullable|exists:users,id'
            ]);

            $createdEvents = [];

            foreach ($validated['events'] as $eventData) {
                // Prüfen, ob der Benutzer berechtigt ist, für andere Benutzer Ereignisse zu erstellen
                $userId = $eventData['user_id'] ?? $user->id;

                // Wenn ein anderer Benutzer als der aktuelle ausgewählt wurde
                if ($userId != $user->id) {
                    // HR-Benutzer dürfen für alle Benutzer Ereignisse erstellen
                    if ($user->role_id === 2) {
                        // Erlaubt
                    }
                    // Abteilungsleiter dürfen nur für Mitglieder ihres Teams Ereignisse erstellen
                    else if ($isTeamManager) {
                        // Prüfen, ob der ausgewählte Benutzer im Team des Abteilungsleiters ist
                        $isTeamMember = DB::table('team_user')
                            ->where('team_id', $teamId)
                            ->where('user_id', $userId)
                            ->exists();

                        if (!$isTeamMember) {
                            continue; // Überspringe dieses Ereignis
                        }
                    }
                    else {
                        continue; // Überspringe dieses Ereignis
                    }

                    // Prüfen, ob es sich um einen Krankheitseintrag handelt
                    $eventType = EventType::find($eventData['event_type_id']);
                    if ($eventType && $eventType->name === 'Krankheit' && $user->role_id !== 2) {
                        continue; // Überspringe dieses Ereignis
                    }
                }

                // Ereignis erstellen
                $event = new Event();
                $event->title = $eventData['title'];
                $event->description = $eventData['description'] ?? null;
                $event->start_date = $eventData['start_date'];
                $event->end_date = $eventData['end_date'];
                $event->is_all_day = $eventData['is_all_day'] ?? true;
                $event->event_type_id = $eventData['event_type_id'];
                $event->user_id = $userId;
                // Team-ID des Benutzers ermitteln und setzen
                $userTeamId = User::find($userId)->current_team_id;
                $event->team_id = $userTeamId;
                $event->created_by = $user->id;
                $event->status = 'approved'; // Standardmäßig genehmigt

                $event->save();

                $createdEvents[] = $event;
            }

            return response()->json($createdEvents, 201);
        } catch (\Exception $e) {
            Log::error('Fehler beim Erstellen der Wochenplanung: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
