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
}

