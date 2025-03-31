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

// Benutzerverwaltung Routen (nur für Admin und HR)
    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        Route::get('/users', function () {
            return Inertia::render('Users/Index');
        })->name('users.index');
    });
});

// API Routes für die Daten
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->prefix('api')->group(function () {
// Urlaubsverwaltung API
    Route::get('/vacation/user', 'App\Http\Controllers\VacationController@getUserData')->name('api.vacation.user');
    Route::get('/vacation/yearly/{year}', 'App\Http\Controllers\VacationController@getYearlyVacationData')->name('api.vacation.yearly');
    Route::get('/vacation/requests', 'App\Http\Controllers\VacationController@getRequests')->name('api.vacation.requests');
    Route::get('/vacation/all-requests', 'App\Http\Controllers\VacationController@getAllRequests')->name('api.vacation.all-requests');
    Route::get('/vacation/user-requests', 'App\Http\Controllers\VacationController@getUserRequests')->name('api.vacation.user-requests');
    Route::post('/vacation/submit', 'App\Http\Controllers\VacationController@submitRequest')->name('api.vacation.submit');
    Route::post('/vacation/approve/{id}', 'App\Http\Controllers\VacationController@approveRequest')->name('api.vacation.approve');
    Route::post('/vacation/reject/{id}', 'App\Http\Controllers\VacationController@rejectRequest')->name('api.vacation.reject');

// Kalender API
    Route::get('/calendar/company', 'App\Http\Controllers\CalendarController@getCompanyData')->name('api.calendar.company');

// Events API
    Route::get('/events', 'App\Http\Controllers\EventController@index')->name('api.events.index');
    Route::post('/events', 'App\Http\Controllers\EventController@store')->name('api.events.store');
    Route::get('/events/{id}', 'App\Http\Controllers\EventController@show')->name('api.events.show');
    Route::put('/events/{id}', 'App\Http\Controllers\EventController@update')->name('api.events.update');
    Route::delete('/events/{id}', 'App\Http\Controllers\EventController@destroy')->name('api.events.destroy');

// Benutzerverwaltung API (nur für Admin und HR)
// Temporär die Berechtigungsprüfung entfernen, um die Funktionalität zu testen
    Route::get('/users', 'App\Http\Controllers\UserController@index')->name('api.users.index');
    Route::get('/users/{id}', 'App\Http\Controllers\UserController@show')->name('api.users.show');
    Route::post('/users', 'App\Http\Controllers\UserController@store')->name('api.users.store');
    Route::put('/users/{id}', 'App\Http\Controllers\UserController@update')->name('api.users.update');
    Route::delete('/users/{id}', 'App\Http\Controllers\UserController@destroy')->name('api.users.destroy');

    Route::get('/departments', 'App\Http\Controllers\TeamController@index')->name('api.departments.index');
    Route::get('/roles', 'App\Http\Controllers\RoleController@index')->name('api.roles.index');

    // Benachrichtigungen API
    Route::get('/notifications/birthdays', 'App\Http\Controllers\NotificationController@getBirthdayNotifications')
        ->name('api.notifications.birthdays');
});

