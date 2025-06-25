<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\VacationRequestMail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventTypeController;
use App\Http\Controllers\VacationController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VacationWishController;
use App\Http\Controllers\ParkingController;
use App\Models\VacationRequest;
use App\Models\User;
use App\Models\Team;
use App\Models\Role;
use Carbon\Carbon;

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

// CSRF-Token-Routen für bessere Token-Verwaltung
Route::middleware('web')->group(function () {
    // CSRF-Cookie setzen
    Route::get('/sanctum/csrf-cookie', function () {
        return response()->json([
            'message' => 'CSRF cookie set',
            'timestamp' => now()->toISOString()
        ]);
    });

    // Frisches CSRF-Token holen
    Route::get('/refresh-csrf', function () {
        // Regeneriere Session-Token
        request()->session()->regenerateToken();

        return response()->json([
            'token' => csrf_token(),
            'timestamp' => now()->toISOString()
        ]);
    });

    // Debug-Route für Token-Status
    Route::get('/csrf-status', function () {
        return response()->json([
            'has_session' => request()->hasSession(),
            'session_id' => request()->session()->getId(),
            'csrf_token' => csrf_token(),
            'timestamp' => now()->toISOString()
        ]);
    });
});

Route::prefix('test')->group(function () {
    // Test für Parkplatz-E-Mail
    Route::get('/parking-reservation-mail', function () {
        try {
            $testEmail = env('MAIL_TEST_RECIPIENT', 'test@example.com');

            // Testdaten erzeugen
            $user = new User();
            $user->id = 1;
            $user->name = 'Max Mustermann';
            $user->email = $testEmail;

            $parkingSpot = new \App\Models\ParkingSpot();
            $parkingSpot->id = 1;
            $parkingSpot->name = 'Hebebühne 1 - Oben';
            $parkingSpot->identifier = 'HB1-O';

            $parkingLocation = new \App\Models\ParkingLocation();
            $parkingLocation->id = 1;
            $parkingLocation->name = 'Firmenhof - Hebebühnen';

            $parkingSpot->setRelation('parkingLocation', $parkingLocation);

            $reservation = new \App\Models\ParkingReservation();
            $reservation->id = 999;
            $reservation->user_id = 1;
            $reservation->parking_spot_id = 1;
            $reservation->reservation_date = Carbon\Carbon::now()->addDays(1);
            $reservation->start_time = '08:00';
            $reservation->end_time = '17:00';
            $reservation->vehicle_info = 'BMW X3, ABC-123';
            $reservation->notes = 'Testnotiz für die Parkplatz-Reservierung';
            $reservation->status = 'confirmed';

            $reservation->setRelation('parkingSpot', $parkingSpot);
            $reservation->setRelation('user', $user);

            // E-Mail senden
            Mail::to($testEmail)->send(new App\Mail\ParkingReservationMail(
                $reservation,
                $user,
                'created'
            ));

            return 'Parkplatz-Reservierung-E-Mail gesendet an ' . $testEmail;
        } catch (\Exception $e) {
            return 'Fehler: ' . $e->getMessage();
        }
    });

    // Test für Stornierung-E-Mail
    Route::get('/parking-cancellation-mail', function () {
        try {
            $testEmail = env('MAIL_TEST_RECIPIENT', 'test@example.com');

            // Testdaten erzeugen
            $user = new User();
            $user->id = 1;
            $user->name = 'Max Mustermann';
            $user->email = $testEmail;

            $parkingSpot = new \App\Models\ParkingSpot();
            $parkingSpot->id = 1;
            $parkingSpot->name = 'Hebebühne 1 - Oben';
            $parkingSpot->identifier = 'HB1-O';

            $parkingLocation = new \App\Models\ParkingLocation();
            $parkingLocation->id = 1;
            $parkingLocation->name = 'Firmenhof - Hebebühnen';

            $parkingSpot->setRelation('parkingLocation', $parkingLocation);

            $reservation = new \App\Models\ParkingReservation();
            $reservation->id = 999;
            $reservation->user_id = 1;
            $reservation->parking_spot_id = 1;
            $reservation->reservation_date = Carbon\Carbon::now()->addDays(1);
            $reservation->start_time = '08:00';
            $reservation->end_time = '17:00';
            $reservation->vehicle_info = 'BMW X3, ABC-123';
            $reservation->notes = 'Testnotiz für die Parkplatz-Reservierung';
            $reservation->status = 'cancelled';

            $reservation->setRelation('parkingSpot', $parkingSpot);
            $reservation->setRelation('user', $user);

            // E-Mail senden
            Mail::to($testEmail)->send(new App\Mail\ParkingReservationMail(
                $reservation,
                $user,
                'cancelled'
            ));

            return 'Parkplatz-Stornierung-E-Mail gesendet an ' . $testEmail;
        } catch (\Exception $e) {
            return 'Fehler: ' . $e->getMessage();
        }
    });
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
    Route::get('/vacation/hr-overview', [VacationController::class, 'getHROverview'])->name('api.vacation.hr-overview');
    Route::get('/vacation/info-list', [VacationController::class, 'getVacationInfoList'])->name('api.vacation.info-list');

    // Urlaubswünsche API
    Route::get('/vacation-wishes/my/{year?}', [VacationWishController::class, 'getMyWishes'])->name('api.vacation-wishes.my');
    Route::get('/vacation-wishes/team/{year?}', [VacationWishController::class, 'getTeamWishes'])->name('api.vacation-wishes.team');
    Route::post('/vacation-wishes', [VacationWishController::class, 'store'])->name('api.vacation-wishes.store');
    Route::delete('/vacation-wishes/{id}', [VacationWishController::class, 'destroy'])->name('api.vacation-wishes.destroy');
    Route::post('/vacation/cancel-approved/{id}', [VacationController::class, 'cancelApprovedRequest'])->name('api.vacation.cancel-approved');

    // Debug route für alle Urlaubswünsche - nur für Entwicklung
    Route::get('/vacation-wishes/all', [VacationWishController::class, 'getAllWishes'])->name('api.vacation-wishes.all');

    // Urlaubskontingent API
    Route::get('/vacation/balance/{year?}', [VacationWishController::class, 'getVacationBalance'])->name('api.vacation.balance');

    // Kalender API
    Route::get('/calendar/company', [CalendarController::class, 'getCompanyData'])->name('api.calendar.company');

    // Events API
    Route::get('/events', [EventController::class, 'index'])->name('api.events.index');
    Route::post('/events', [EventController::class, 'store'])->name('api.events.store');
    Route::get('/events/{id}', [EventController::class, 'show'])->name('api.events.show');
    Route::match(['put', 'post'], '/events/{id}', [EventController::class, 'update'])->name('api.events.update');
    Route::delete('/events/{id}', [EventController::class, 'destroy'])->name('api.events.destroy');
    Route::post('/events/{id}', [EventController::class, 'destroy'])->name('api.events.destroy.post');
    Route::post('/events/week-plan', [EventController::class, 'storeWeekPlan'])->name('api.events.week-plan');
    Route::post('/events/approve/{id}', [EventController::class, 'approveEvent'])->name('api.events.approve');
    Route::post('/events/reject/{id}', [EventController::class, 'rejectEvent'])->name('api.events.reject');
    Route::get('/events/pending', [EventController::class, 'getPendingEvents'])->name('api.events.pending');

    // Ereignistypen API
    Route::get('/event-types', [EventTypeController::class, 'index'])->name('api.event-types.index');

    // Benutzerrolle API
    Route::get('/user/role', [CalendarController::class, 'getUserRole'])->name('api.user.role');
    Route::get('/user/info', [VacationWishController::class, 'getUserInfo'])->name('api.user.info');

    // Mitarbeiterliste API
    Route::get('/employees', [CalendarController::class, 'getEmployees'])->name('api.employees');

    // Benachrichtigungen API
    Route::get('/notifications/birthdays', [NotificationController::class, 'getBirthdayNotifications'])
        ->name('api.notifications.birthdays');

    // Benutzer API
    Route::get('/users', [UserController::class, 'index'])->name('api.users.index');
    Route::get('/users/{id}', [UserController::class, 'show'])->name('api.users.show');
    Route::post('/users', [UserController::class, 'store'])->name('api.users.store');
    Route::put('/users/{id}', [UserController::class, 'update'])->name('api.users.update');

    // Abteilungen API
    Route::get('/departments', function () {
        $teams = Team::where('personal_team', false)->get()->map(function ($team) {
            return [
                'id' => $team->id,
                'name' => $team->name
            ];
        });
        return response()->json($teams);
    })->name('api.departments.index');

    // Rollen API
    Route::get('/roles', function () {
        $roles = Role::all()->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description
            ];
        });
        return response()->json($roles);
    })->name('api.roles.index');

    // Feiertage API
    Route::get('/holidays/{year?}', [VacationWishController::class, 'getHolidays'])->name('api.holidays');

    // Berichtsheft API
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index'])->name('api.reports.index');
    Route::post('/reports', [App\Http\Controllers\ReportController::class, 'store'])->name('api.reports.store');
    Route::get('/reports/{id}', [App\Http\Controllers\ReportController::class, 'show'])->name('api.reports.show');
    Route::put('/reports/{id}', [App\Http\Controllers\ReportController::class, 'update'])->name('api.reports.update');
    Route::delete('/reports/{id}', [App\Http\Controllers\ReportController::class, 'destroy'])->name('api.reports.destroy');
    Route::get('/reports/{id}/pdf', [App\Http\Controllers\ReportController::class, 'downloadPdf'])->name('api.reports.pdf');
    Route::get('/instructors', [App\Http\Controllers\ReportController::class, 'getInstructors'])->name('api.instructors');

    // Fächer API
    Route::get('/subjects', [App\Http\Controllers\SubjectController::class, 'index'])->name('api.subjects.index');
    Route::post('/subjects', [App\Http\Controllers\SubjectController::class, 'store'])->name('api.subjects.store');
    Route::put('/subjects/{id}', [App\Http\Controllers\SubjectController::class, 'update'])->name('api.subjects.update');
    Route::delete('/subjects/{id}', [App\Http\Controllers\SubjectController::class, 'destroy'])->name('api.subjects.destroy');
    Route::get('/subjects/year/{year}', [App\Http\Controllers\SubjectController::class, 'getByYear'])->name('api.subjects.by-year');

    // Parkplatz API - VEREINFACHT (ohne Admin-Genehmigungen)
    Route::prefix('parking')->group(function () {
        // Benutzer-API
        Route::get('/locations', [ParkingController::class, 'getLocations'])->name('api.parking.locations');
        Route::get('/availability', [ParkingController::class, 'getAvailability'])->name('api.parking.availability');
        Route::get('/my-reservations', [ParkingController::class, 'getMyReservations'])->name('api.parking.my-reservations');
        Route::post('/reserve', [ParkingController::class, 'createReservation'])->name('api.parking.reserve');
        Route::delete('/reservations/{reservation}', [ParkingController::class, 'cancelReservation'])->name('api.parking.cancel-reservation');
        Route::get('/spots', [ParkingController::class, 'getAllSpots'])->name('api.parking.spots');
        Route::get('/reservations/current', [ParkingController::class, 'getCurrentReservations'])->name('api.parking.current-reservations');
        Route::post('/spots/{spot}/toggle', [ParkingController::class, 'toggleSpotStatus'])->name('api.parking.toggle-spot');

        // Admin-API für CRUD-Operationen
        Route::get('/spaces', [ParkingController::class, 'getSpaces'])->name('api.parking.spaces');
        Route::post('/locations', [ParkingController::class, 'storeLocation'])->name('api.parking.locations.store');
        Route::put('/locations/{location}', [ParkingController::class, 'updateLocation'])->name('api.parking.locations.update');
        Route::delete('/locations/{location}', [ParkingController::class, 'destroyLocation'])->name('api.parking.locations.destroy');
        Route::get('/locations/{location}/spaces', [ParkingController::class, 'getSpacesByLocation'])->name('api.parking.locations.spaces');
        Route::post('/spaces', [ParkingController::class, 'storeSpace'])->name('api.parking.spaces.store');
        Route::put('/spaces/{space}', [ParkingController::class, 'updateSpace'])->name('api.parking.spaces.update');
        Route::delete('/spaces/{space}', [ParkingController::class, 'destroySpace'])->name('api.parking.spaces.destroy');
    });
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

    // FEHLENDE PROFILE ROUTE - HIER WAR DAS PROBLEM!
    Route::get('/profile', function () {
        return Inertia::render('Profile/Show', [
            'sessions' => collect(), // Leere Sessions als Fallback
        ]);
    })->name('profile.show');

    // Urlaubsverwaltung Routen
    Route::get('/vacation', function () {
        return Inertia::render('Vacation/Overview');
    })->name('vacation');

    Route::get('/vacation/management', function () {
        return Inertia::render('Vacation/Management');
    })->name('vacation.management');

    // Benutzerverwaltung Route
    Route::get('/users', function () {
        return Inertia::render('Users/Index');
    })->name('users.index');

    Route::get('/vacation/hr-overview', function () {
        return Inertia::render('Vacation/HROverview');
    })->name('vacation.hr-overview');

    Route::get('/vacation/info-list', function () {
        return Inertia::render('Vacation/VacationInfoList');
    })->name('vacation.info-list');

    // Urlaubswünsche Route
    Route::get('/vacation/wishes', function () {
        return Inertia::render('Calendar/VacationWishes');
    })->name('vacation.wishes');

    // Berichtsheft Seiten
    Route::get('/reports', function () {
        return Inertia::render('Reports/Index');
    })->name('reports.index');

    Route::get('/reports/create', function () {
        return Inertia::render('Reports/Create');
    })->name('reports.create');

    Route::get('/subjects', function () {
        return Inertia::render('Subjects/Index');
    })->name('subjects.index');

    // Parkplatzverwaltung Routen
    Route::get('/parking', [ParkingController::class, 'index'])->name('parking.index');
    Route::get('/admin/parking', [ParkingController::class, 'admin'])->name('admin.parking');
});
