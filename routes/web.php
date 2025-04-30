<?php
//
//use Illuminate\Foundation\Application;
//use Illuminate\Support\Facades\Route;
//use Inertia\Inertia;
//use App\Http\Controllers\EventController;
//use App\Http\Controllers\VacationController;
//use App\Http\Controllers\TeamController;
//use App\Http\Controllers\UserController;
//
///*
//|--------------------------------------------------------------------------
//| Web Routes
//|--------------------------------------------------------------------------
//|
//| Here is where you can register web routes for your application. These
//| routes are loaded by the RouteServiceProvider within a group which
//| contains the "web" middleware group. Now create something great!
//|
//*/
//
//Route::get('/', function () {
//    return Inertia::render('Welcome', [
//        'canLogin' => Route::has('login'),
//        'canRegister' => Route::has('register'),
//        'laravelVersion' => Application::VERSION,
//        'phpVersion' => PHP_VERSION,
//    ]);
//});
//
//Route::middleware([
//    'auth:sanctum',
//    config('jetstream.auth_session'),
//    'verified',
//])->group(function () {
//    Route::get('/dashboard', function () {
//        return Inertia::render('Dashboard');
//    })->name('dashboard');
//
//    Route::get('/calendar', function () {
//        return Inertia::render('Calendar/Index');
//    })->name('calendar');
//
//    Route::get('/company-calendar', function () {
//        return Inertia::render('Calendar/Company');
//    })->name('company-calendar');
//
//    // Urlaubsverwaltung Routen
//    Route::get('/vacation', function () {
//        return Inertia::render('Vacation/Overview');
//    })->name('vacation');
//
//    Route::get('/vacation/management', function () {
//        return Inertia::render('Vacation/Management');
//    })->name('vacation.management');
//
//    // API-Routen für die Web-Anwendung
//    Route::prefix('api')->group(function () {
//        // Benutzer-Routen
//        Route::get('/user', [UserController::class, 'getCurrentUser']);
//        Route::get('/birthdays', [UserController::class, 'getBirthdays']);
//
//        // Team-Routen
//        Route::get('/teams', [TeamController::class, 'getUserTeams']);
//        Route::get('/teams/{team}/members', [TeamController::class, 'getTeamMembers']);
//        Route::get('/teams/{team}/homeoffice-rules', [TeamController::class, 'getTeamHomeofficeRules']);
//
//        // Ereignis-Routen
//        Route::get('/event-types', [EventController::class, 'getEventTypes']);
//        Route::get('/events', [EventController::class, 'getUserEvents']);
//        Route::get('/teams/{team}/events', [EventController::class, 'getTeamEvents']);
//        Route::post('/events', [EventController::class, 'store']);
//        Route::put('/events/{event}', [EventController::class, 'update']);
//        Route::delete('/events/{event}', [EventController::class, 'destroy']);
//        Route::post('/events/{event}/approve', [EventController::class, 'approve']);
//        Route::post('/events/{event}/reject', [EventController::class, 'reject']);
//
//        // Urlaubs-Routen
//        Route::get('/vacation/balance', [VacationController::class, 'getUserBalance']);
//        Route::get('/vacation/requests', [VacationController::class, 'getUserRequests']);
//        Route::get('/teams/{team}/vacation-requests', [VacationController::class, 'getTeamRequests']);
//        Route::post('/vacation/requests', [VacationController::class, 'store']);
//        Route::post('/vacation/requests/{vacationRequest}/approve', [VacationController::class, 'approve']);
//        Route::post('/vacation/requests/{vacationRequest}/reject', [VacationController::class, 'reject']);
//        Route::delete('/vacation/requests/{vacationRequest}', [VacationController::class, 'cancel']);
//        Route::get('/vacation/substitutes', [VacationController::class, 'getAvailableSubstitutes']);
//    });
//});

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;
use App\Mail\NewVacationRequest;
use App\Models\VacationRequest;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventTypeController;
use App\Http\Controllers\VacationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Auth;
use App\Models\Event;
use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\Role;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// API Routes für die Daten
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->prefix('api')->group(function () {
// Urlaubsverwaltung API
    Route::get('/vacation/user', [VacationController::class, 'getUserData'])->name('api.vacation.user');
    Route::get('/vacation/yearly/{year}', [VacationController::class, 'getYearlyVacationData'])->name('api.vacation.yearly');
    Route::get('/vacation/requests', [VacationController::class, 'getRequests'])->name('api.vacation.requests');
    Route::get('/vacation/all-requests', [VacationController::class, 'getAllRequests'])->name('api.vacation.all-requests');
    Route::get('/vacation/user-requests', [VacationController::class, 'getUserRequests'])->name('api.vacation.user-requests');
    Route::post('/vacation/submit', [VacationController::class, 'submitRequest'])->name('api.vacation.submit');
    Route::post('/vacation/cancel/{id}', [VacationController::class, 'cancelRequest'])->name('api.vacation.cancel');
    Route::post('/vacation/approve/{id}', [VacationController::class, 'approveRequest'])->name('api.vacation.approve');
    Route::post('/vacation/reject/{id}', [VacationController::class, 'rejectRequest'])->name('api.vacation.reject');

// Kalender API
    Route::get('/calendar/company', [CalendarController::class, 'getCompanyData'])->name('api.calendar.company');

// Events API
    Route::get('/events', [EventController::class, 'index'])->name('api.events.index');
    Route::post('/events', [EventController::class, 'store'])->name('api.events.store');
    Route::get('/events/{id}', [EventController::class, 'show'])->name('api.events.show');
    Route::match(['put', 'post'], '/events/{id}', [EventController::class, 'update'])->name('api.events.update');
    Route::delete('/events/{id}', [EventController::class, 'destroy'])->name('api.events.destroy');
    Route::post('/events/{id}', [EventController::class, 'destroy'])->name('api.events.destroy.post');
// Neue Route für die Wochenplanung
    Route::post('/events/week-plan', [EventController::class, 'storeWeekPlan'])->name('api.events.week-plan');
// Neue Routen für die Genehmigung/Ablehnung von Ereignissen
    Route::post('/events/approve/{id}', [EventController::class, 'approveEvent'])->name('api.events.approve');
    Route::post('/events/reject/{id}', [EventController::class, 'rejectEvent'])->name('api.events.reject');
    Route::get('/events/pending', [EventController::class, 'getPendingEvents'])->name('api.events.pending');

// Ereignistypen API - KORRIGIERT: Entfernen Sie das doppelte /api/ im Pfad
    Route::get('/event-types', [EventTypeController::class, 'index'])->name('api.event-types.index');

// Benachrichtigungen API
    Route::get('/notifications/birthdays', [NotificationController::class, 'getBirthdayNotifications'])
        ->name('api.notifications.birthdays');

    Route::get('/events/{id}/delete', function($id) {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Nicht authentifiziert'], 401);
            }

            $event = Event::where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('created_by', $user->id);
            })->find($id);

            if (!$event) {
                return response()->json(['error' => 'Ereignis nicht gefunden oder Sie haben keine Berechtigung, es zu löschen.'], 404);
            }

            $event->delete();

            return response()->json(['message' => 'Ereignis erfolgreich gelöscht']);
        } catch (\Exception $e) {
            \Log::error('Fehler beim Löschen des Ereignisses: ' . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    })->middleware(['auth:sanctum']);

    // Simplified route for creating events
    Route::get('/events/create', function(Request $request) {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Nicht authentifiziert'], 401);
            }

            // Find event type
            $eventType = \App\Models\EventType::where('name', $request->query('event_type'))->first();

            if (!$eventType) {
                // Try case-insensitive search
                $eventType = \App\Models\EventType::whereRaw('LOWER(name) = ?', [strtolower($request->query('event_type'))])->first();

                if (!$eventType) {
                    // Default to "Sonstiges" if not found
                    $eventType = \App\Models\EventType::where('name', 'Sonstiges')->first();

                    if (!$eventType) {
                        // Create a default event type if none exists
                        $eventType = new \App\Models\EventType();
                        $eventType->name = 'Sonstiges';
                        $eventType->color = '#607D8B';
                        $eventType->requires_approval = false;
                        $eventType->save();
                    }
                }
            }

            // Create new event
            $event = new \App\Models\Event();
            $event->user_id = $user->id;
            $event->created_by = $user->id;
            $event->event_type_id = $eventType->id;
            $event->title = $request->query('title');
            $event->description = $request->query('description');
            $event->start_date = $request->query('start_date');
            $event->end_date = $request->query('end_date');
            $event->is_all_day = $request->query('is_all_day') === '1';
            $event->status = $eventType->requires_approval ? 'pending' : 'approved';

            // Set team_id if available
            if ($user->current_team_id) {
                $event->team_id = $user->current_team_id;
            }

            $event->save();

            return response()->json([
                'message' => 'Ereignis erfolgreich erstellt',
                'event' => $event
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Fehler beim Erstellen des Ereignisses: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    })->middleware(['auth:sanctum']);

    // Simplified route for updating events
    Route::get('/events/{id}/update', function(Request $request, $id) {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Nicht authentifiziert'], 401);
            }

            // Find the event
            $event = \App\Models\Event::find($id);

            if (!$event) {
                return response()->json(['error' => 'Ereignis nicht gefunden'], 404);
            }

            // Find event type
            $eventType = \App\Models\EventType::where('name', $request->query('event_type'))->first();

            if (!$eventType) {
                // Try case-insensitive search
                $eventType = \App\Models\EventType::whereRaw('LOWER(name) = ?', [strtolower($request->query('event_type'))])->first();

                if (!$eventType) {
                    // Default to "Sonstiges" if not found
                    $eventType = \App\Models\EventType::where('name', 'Sonstiges')->first();

                    if (!$eventType) {
                        // Create a default event type if none exists
                        $eventType = new \App\Models\EventType();
                        $eventType->name = 'Sonstiges';
                        $eventType->color = '#607D8B';
                        $eventType->requires_approval = false;
                        $eventType->save();
                    }
                }
            }

            // Update event
            $event->event_type_id = $eventType->id;
            $event->title = $request->query('title');
            $event->description = $request->query('description');
            $event->start_date = $request->query('start_date');
            $event->end_date = $request->query('end_date');
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
            \Log::error('Fehler beim Aktualisieren des Ereignisses: ' . $e->getMessage(), [
                'id' => $id,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    })->middleware(['auth:sanctum']);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/calendar', function () {
        return Inertia::render('Calendar/Index');
    })->name('calendar');

    Route::get('/company-calendar', function () {
        return Inertia::render('Calendar/Company');
    })->name('company-calendar');

// Urlaubsverwaltung Routen
    Route::get('/vacation', function () {
        return Inertia::render('Vacation/Overview');
    })->name('vacation');

    Route::get('/vacation/management', function () {
        return Inertia::render('Vacation/Management');
    })->name('vacation.management');


    // Userverwaltung Routen
    Route::get('/users', function (){
        return Inertia::render('Users/Index');
    })->name('users');
//    Route::get('/users', [UserController::class, 'index']);
//    Route::get('/users/{id}', [UserController::class, 'show']);
//    Route::post('/users', [UserController::class, 'store']);
//    Route::put('/users/{id}', [UserController::class, 'update']);
//    Route::delete('/users/{id}', [UserController::class, 'destroy']);
//
//// Add these routes for departments and roles
//    Route::get('/departments', function() {
//        return response()->json(Team::all());
//    });
//
//    Route::get('/roles', function() {
//        return response()->json(Role::all());
//    });
});

