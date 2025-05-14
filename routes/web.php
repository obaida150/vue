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
use App\Models\VacationRequest;
use App\Models\User;
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

Route::prefix('test')->group(function () {
    // Test für Urlaubsantrag-E-Mail
    Route::get('/vacation-request-mail', function () {
        try {
            $testEmail = env('MAIL_TEST_RECIPIENT', 'test@example.com');

            // Testdaten erzeugen
            $user = new User();
            $user->id = 1;
            $user->full_name = 'Max Mustermann';

            $vacationRequest = new VacationRequest();
            $vacationRequest->id = 999;
            $vacationRequest->user_id = 1;
            $vacationRequest->start_date = Carbon\Carbon::now()->addDays(5);
            $vacationRequest->end_date = Carbon\Carbon::now()->addDays(10);
            $vacationRequest->days = 5;
            $vacationRequest->notes = 'Testnotiz für den Urlaubsantrag';

            // E-Mail senden
            Mail::to($testEmail)->send(new App\Mail\VacationRequestMail(
                $vacationRequest,
                $user,
                collect([]),
                null
            ));

            return 'Urlaubsantrag-E-Mail gesendet an ' . $testEmail;
        } catch (\Exception $e) {
            return 'Fehler: ' . $e->getMessage();
        }
    });

    // Test für Genehmigungs-E-Mail
    Route::get('/vacation-approved-mail', function () {
        try {
            $testEmail = env('MAIL_TEST_RECIPIENT', 'test@example.com');

            // Testdaten erzeugen
            $employee = new User();
            $employee->id = 1;
            $employee->full_name = 'Max Mustermann';

            $approver = new User();
            $approver->id = 2;
            $approver->full_name = 'Chef Mustermann';

            $vacationRequest = new VacationRequest();
            $vacationRequest->id = 999;
            $vacationRequest->user_id = 1;
            $vacationRequest->start_date = Carbon\Carbon::now()->addDays(5);
            $vacationRequest->end_date = Carbon\Carbon::now()->addDays(10);
            $vacationRequest->days = 5;
            $vacationRequest->notes = 'Testnotiz für den Urlaubsantrag';

            // E-Mail senden
            Mail::to($testEmail)->send(new App\Mail\VacationApprovedMail(
                $vacationRequest,
                $employee,
                $approver
            ));

            return 'Genehmigungs-E-Mail gesendet an ' . $testEmail;
        } catch (\Exception $e) {
            return 'Fehler: ' . $e->getMessage();
        }
    });

    // Test für Ablehnungs-E-Mail
    Route::get('/vacation-rejected-mail', function () {
        try {
            $testEmail = env('MAIL_TEST_RECIPIENT', 'test@example.com');

            // Testdaten erzeugen
            $employee = new User();
            $employee->id = 1;
            $employee->full_name = 'Max Mustermann';

            $approver = new User();
            $approver->id = 2;
            $approver->full_name = 'Chef Mustermann';

            $vacationRequest = new VacationRequest();
            $vacationRequest->id = 999;
            $vacationRequest->user_id = 1;
            $vacationRequest->start_date = Carbon\Carbon::now()->addDays(5);
            $vacationRequest->end_date = Carbon\Carbon::now()->addDays(10);
            $vacationRequest->days = 5;
            $vacationRequest->notes = 'Testnotiz für den Urlaubsantrag';

            $rejectionReason = 'Zu viele Mitarbeiter sind bereits im Urlaub in diesem Zeitraum.';

            // E-Mail senden
            Mail::to($testEmail)->send(new App\Mail\VacationRejectedMail(
                $vacationRequest,
                $employee,
                $rejectionReason,
                $approver
            ));

            return 'Ablehnungs-E-Mail gesendet an ' . $testEmail;
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
    // Füge die Route für das Zurückziehen VOR den anderen Routen mit Parametern ein
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

// Benutzerrolle API
    Route::get('/user/role', [CalendarController::class, 'getUserRole'])->name('api.user.role');

// Mitarbeiterliste API
    Route::get('/employees', [CalendarController::class, 'getEmployees'])->name('api.employees');

// Benachrichtigungen API
    Route::get('/notifications/birthdays', [NotificationController::class, 'getBirthdayNotifications'])
        ->name('api.notifications.birthdays');
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
});
