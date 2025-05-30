<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'doctor_id',
        'specialty',
        'license_number',
        'years_of_experience',
        'education',
        'consultation_fee',
        'available_days',
        'available_hours_start',
        'available_hours_end',
        'room_number',
        'phone_extension',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'available_days' => 'array',
        'available_hours_start' => 'datetime:H:i',
        'available_hours_end' => 'datetime:H:i',
        'consultation_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function medicalRecords(): HasMany
    {
        return $this->hasMany(MedicalRecord::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function emergencyVisits(): HasMany
    {
        return $this->hasMany(EmergencyVisit::class);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return $this->user->name ?? '';
    }

    public function getAvailabilityStatusAttribute(): string
    {
        $today = now()->format('l'); // Get day name (Monday, Tuesday, etc.)
        $currentTime = now()->format('H:i');
        
        if (!in_array($today, $this->available_days ?? [])) {
            return 'Not Available Today';
        }
        
        $startTime = $this->available_hours_start?->format('H:i');
        $endTime = $this->available_hours_end?->format('H:i');
        
        if ($currentTime >= $startTime && $currentTime <= $endTime) {
            return 'Available';
        }
        
        return 'Not Available Now';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySpecialty($query, $specialty)
    {
        return $query->where('specialty', $specialty);
    }

    public function scopeAvailableToday($query)
    {
        $today = now()->format('l');
        return $query->whereJsonContains('available_days', $today);
    }

    public function scopeAvailableNow($query)
    {
        $currentTime = now()->format('H:i:s');
        return $query->where('available_hours_start', '<=', $currentTime)
                    ->where('available_hours_end', '>=', $currentTime);
    }
}
