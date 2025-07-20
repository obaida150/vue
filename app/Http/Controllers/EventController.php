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
// Entfernen Sie die Microsoft Graph API Imports, da sie hier nicht mehr direkt verwendet werden
// use Microsoft\Graph\Graph;
// use Microsoft\Graph\Model\Event as OutlookGraphEvent;
// use Microsoft\Graph\Model\ItemBody;
// use Microsoft\Graph\Model\DateTimeTimeZone;
// use Microsoft\Graph\Model\BodyType;
use App\Http\Controllers\OutlookController; // Importieren Sie den neuen Controller

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

            // Überprüfen Sie, ob der Benutzer die Berechtigung hat, Ereignisse zu erstellen
            if (!$user->hasPermissionTo('create events')) {
                return response()->json(['error' => 'Nicht autorisiert, Ereignisse zu erstellen.'], 403);
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'is_all_day' => 'boolean',
                'event_type_id' => 'required|exists:event_types,id',
                'user_id' => 'nullable|exists:users,id',
                'sync_with_outlook' => 'boolean', // NEU: Für Outlook-Synchronisierung
            ]);

            // Wenn eine user_id angegeben ist, überprüfen Sie, ob der aktuelle Benutzer die Berechtigung hat, Ereignisse für andere Benutzer zu erstellen
            $userId = $validated['user_id'] ?? $user->id;
            if ($userId !== $user->id && !$user->hasPermissionTo('create events for others')) {
                return response()->json(['error' => 'Nicht autorisiert, Ereignisse für andere Benutzer zu erstellen.'], 403);
            }

            // Überprüfen Sie, ob der Benutzer, für den das Ereignis erstellt wird, ein aktives Krankheitsereignis hat
            $hasActiveSickness = Event::where('user_id', $userId)
                ->where('event_type_id', 3) // Annahme: 3 ist die ID für "Krankheit"
                ->where('start_date', '<=', $validated['end_date'])
                ->where('end_date', '>=', $validated['start_date'])
                ->exists();

            if ($hasActiveSickness) {
                return response()->json(['error' => 'Benutzer hat bereits ein aktives Krankheitsereignis für diesen Zeitraum.'], 400);
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
            $userTeamId = User::find($userId)->current_team_id;
            $event->team_id = $userTeamId;
            $event->created_by = $user->id;
            $event->status = 'approved';

            $event->save();

            // NEU: Synchronisierung mit Outlook, wenn gewünscht
            // outlook_access_token ist für EWS nicht relevant, die EWS-Client-Prüfung erfolgt im OutlookController
            if (($validated['sync_with_outlook'] ?? false)) {
                $outlookController = new OutlookController();
                $ewsEventId = $outlookController->createEwsEvent($event, $user);
                if ($ewsEventId) {
                    $event->outlook_event_id = $ewsEventId;
                    $event->save(); // Speichern der Outlook Event ID
                } else {
                    Log::warning('Failed to sync new event ' . $event->id . ' to Outlook via EWS.');
                }
            }

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

            // Überprüfen Sie, ob der Benutzer die Berechtigung hat, das Ereignis zu bearbeiten
            if ($event->created_by !== $user->id && !$user->hasPermissionTo('edit events for others')) {
                return response()->json(['error' => 'Nicht autorisiert, dieses Ereignis zu bearbeiten.'], 403);
            }

            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'is_all_day' => 'boolean',
                'event_type_id' => 'required|exists:event_types,id',
                'user_id' => 'nullable|exists:users,id',
                'sync_with_outlook' => 'boolean', // NEU: Für Outlook-Synchronisierung
            ]);

            // Wenn eine user_id angegeben ist, überprüfen Sie, ob der aktuelle Benutzer die Berechtigung hat, Ereignisse für andere Benutzer zu bearbeiten
            $userId = $validated['user_id'] ?? $user->id;
            if ($userId !== $user->id && !$user->hasPermissionTo('edit events for others')) {
                return response()->json(['error' => 'Nicht autorisiert, Ereignisse für andere Benutzer zu bearbeiten.'], 403);
            }

            // Überprüfen Sie, ob der Benutzer, für den das Ereignis bearbeitet wird, ein aktives Krankheitsereignis hat
            $hasActiveSickness = Event::where('user_id', $userId)
                ->where('event_type_id', 3) // Annahme: 3 ist die ID für "Krankheit"
                ->where('id', '!=', $id) // Ausnahme für das aktuelle Ereignis
                ->where('start_date', '<=', $validated['end_date'])
                ->where('end_date', '>=', $validated['start_date'])
                ->exists();

            if ($hasActiveSickness) {
                return response()->json(['error' => 'Benutzer hat bereits ein aktives Krankheitsereignis für diesen Zeitraum.'], 400);
            }

            // Ereignis aktualisieren
            $event->title = $validated['title'];
            $event->description = $validated['description'] ?? null;
            $event->start_date = $validated['start_date'];
            $event->end_date = $validated['end_date'];
            $event->is_all_day = $validated['is_all_day'] ?? true;
            $event->event_type_id = $validated['event_type_id'];
            $event->user_id = $userId;

            $userTeamId = User::find($userId)->current_team_id;
            $event->team_id = $userTeamId;

            if (Schema::hasColumn('events', 'updated_by')) {
                $event->updated_by = $user->id;
            }

            $event->save();

            // NEU: Synchronisierung mit Outlook, wenn gewünscht
            $outlookController = new OutlookController();
            if (($validated['sync_with_outlook'] ?? false)) {
                if ($event->outlook_event_id) {
                    $outlookController->updateEwsEvent($event, $user);
                } else {
                    // Wenn noch keine Outlook ID vorhanden ist, aber jetzt synchronisiert werden soll
                    $ewsEventId = $outlookController->createEwsEvent($event, $user);
                    if ($ewsEventId) {
                        $event->outlook_event_id = $ewsEventId;
                        $event->save();
                    } else {
                        Log::warning('Failed to sync existing event ' . $event->id . ' to Outlook via EWS during update.');
                    }
                }
            } else if (!($validated['sync_with_outlook'] ?? false) && $event->outlook_event_id) {
                // Wenn Synchronisierung deaktiviert wurde und ein Outlook Event existiert, löschen
                $outlookController->deleteEwsEvent($event, $user);
                $event->outlook_event_id = null;
                $event->save();
            }

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

            // Überprüfen Sie, ob der Benutzer die Berechtigung hat, das Ereignis zu löschen
            if ($event->created_by !== $user->id && !$user->hasPermissionTo('delete events for others')) {
                return response()->json(['error' => 'Nicht autorisiert, dieses Ereignis zu löschen.'], 403);
            }

            // NEU: Outlook-Ereignis löschen, bevor das lokale Ereignis gelöscht wird
            if ($event->outlook_event_id) { // outlook_access_token ist für EWS nicht relevant
                $outlookController = new OutlookController();
                $outlookController->deleteEwsEvent($event, $user);
            }

            $event->delete();

            return response()->json(['message' => 'Ereignis erfolgreich gelöscht.']);
        } catch (\Exception $e) {
            Log::error('Fehler beim Löschen des Ereignisses: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Die folgenden Methoden sind nicht mehr Teil dieses Controllers, da sie in den OutlookController verschoben wurden
    // private function syncEventToOutlook(Event $event, User $user): ?string { ... }
    // private function updateOutlookEvent(Event $event, User $user): bool { ... }
    // private function deleteOutlookEvent(Event $event, User $user): bool { ... }

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
