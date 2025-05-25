<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'name', // Wird für Jetstream benötigt
        'email',
        'password',
        'birth_date',
        'role_id',
        'vacation_days_per_year',
        'initials',
        'entry_date',
        'employee_number',
        'is_active',
        'mentor_id',        // NEU für Mentor-System
        'is_apprentice',    // NEU für Mentor-System
        'is_ausbilder',     // NEU für Ausbilder-System
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date' => 'date',
        'entry_date' => 'date',
        'is_active' => 'boolean',
        'is_apprentice' => 'boolean',  // NEU für Mentor-System
        'is_ausbilder' => 'boolean',   // NEU für Ausbilder-System
        'password' => 'hashed',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'full_name',
    ];

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the role that the user has.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the vacation requests for the user.
     */
    public function vacationRequests()
    {
        return $this->hasMany(VacationRequest::class);
    }

    /**
     * Get the events for the user.
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the vacation balances for the user.
     */
    public function vacationBalances()
    {
        return $this->hasMany(VacationBalance::class);
    }

    /**
     * Get the notifications for the user.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Der Mentor/Betreuer des Azubis
     * NEU für Mentor-System
     */
    public function mentor()
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    /**
     * Azubis, die dieser User betreut
     * NEU für Mentor-System
     */
    public function apprentices()
    {
        return $this->hasMany(User::class, 'mentor_id');
    }

    /**
     * Wer ist für die Urlaubsgenehmigung zuständig?
     * - Normale Mitarbeiter: Team-Leader/Abteilungsleiter
     * - Azubis: Ihr Mentor
     * NEU für Mentor-System
     */
    public function getVacationApproverAttribute()
    {
        if ($this->is_apprentice && $this->mentor) {
            return $this->mentor;
        }

        // Für normale Mitarbeiter: Abteilungsleiter finden
        if ($this->currentTeam) {
            return $this->currentTeam->users()
                ->whereHas('role', function($query) {
                    $query->where('name', 'Abteilungsleiter');
                })
                ->first();
        }

        return null;
    }

    /**
     * Für welche Urlaubsanträge ist dieser User zuständig?
     * NEU für Mentor-System
     */
    public function getVacationRequestsToApproveAttribute()
    {
        $requests = collect();

        // Als Abteilungsleiter: Anträge der Team-Mitglieder (aber nicht Azubis)
        if ($this->role && $this->role->name === 'Abteilungsleiter' && $this->currentTeam) {
            $teamRequests = VacationRequest::whereHas('user', function($query) {
                $query->where('current_team_id', $this->current_team_id)
                    ->where('is_apprentice', false);
            })->where('status', 'pending')->get();

            $requests = $requests->merge($teamRequests);
        }

        // Als Mentor: Anträge der Azubis
        $apprenticeRequests = VacationRequest::whereHas('user', function($query) {
            $query->where('mentor_id', $this->id);
        })->where('status', 'pending')->get();

        $requests = $requests->merge($apprenticeRequests);

        return $requests;
    }

    /**
     * Determine if the user is an admin.
     */
    public function getIsAdminAttribute()
    {
        return $this->role && $this->role->name === 'Admin';
    }

    /**
     * Determine if the user is HR.
     */
    public function getIsHRAttribute()
    {
        return $this->role && $this->role->name === 'Personal';
    }

    /**
     * Determine if the user is a manager.
     */
    public function getIsManagerAttribute()
    {
        return $this->role && $this->role->name === 'Abteilungsleiter';
    }

    public function getIsInstructorAttribute()
    {
        return $this->is_ausbilder;
    }
}
