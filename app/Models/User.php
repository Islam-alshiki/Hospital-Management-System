<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'employee_id',
        'national_id',
        'phone',
        'date_of_birth',
        'gender',
        'address',
        'department_id',
        'role',
        'shift',
        'hire_date',
        'salary',
        'is_active',
        'profile_photo',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'hire_date' => 'date',
            'salary' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // Constants for roles
    const ROLE_ADMIN = 'admin';
    const ROLE_DOCTOR = 'doctor';
    const ROLE_NURSE = 'nurse';
    const ROLE_PHARMACIST = 'pharmacist';
    const ROLE_LAB_STAFF = 'lab_staff';
    const ROLE_RECEPTIONIST = 'receptionist';
    const ROLE_PATIENT = 'patient';

    // Constants for shifts
    const SHIFT_MORNING = 'morning';
    const SHIFT_EVENING = 'evening';
    const SHIFT_NIGHT = 'night';

    // Relationships
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    public function createdAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'created_by');
    }

    public function dispensedPrescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class, 'dispensed_by');
    }

    // Accessors
    public function getIsAdminAttribute(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function getIsDoctorAttribute(): bool
    {
        return $this->role === self::ROLE_DOCTOR;
    }

    public function getIsNurseAttribute(): bool
    {
        return $this->role === self::ROLE_NURSE;
    }

    public function getIsPharmacistAttribute(): bool
    {
        return $this->role === self::ROLE_PHARMACIST;
    }

    public function getIsLabStaffAttribute(): bool
    {
        return $this->role === self::ROLE_LAB_STAFF;
    }

    public function getIsReceptionistAttribute(): bool
    {
        return $this->role === self::ROLE_RECEPTIONIST;
    }

    public function getIsPatientAttribute(): bool
    {
        return $this->role === self::ROLE_PATIENT;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeByShift($query, $shift)
    {
        return $query->where('shift', $shift);
    }
}
