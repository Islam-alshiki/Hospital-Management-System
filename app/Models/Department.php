<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_ar',
        'code',
        'description',
        'description_ar',
        'head_doctor_id',
        'location',
        'phone_extension',
        'email',
        'is_active',
        'operating_hours_start',
        'operating_hours_end',
        'emergency_department',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'emergency_department' => 'boolean',
        'operating_hours_start' => 'datetime:H:i',
        'operating_hours_end' => 'datetime:H:i',
    ];

    // Relationships
    public function headDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'head_doctor_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function wards(): HasMany
    {
        return $this->hasMany(Ward::class);
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' && $this->name_ar ? $this->name_ar : $this->name;
    }

    public function getDisplayDescriptionAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' && $this->description_ar ? $this->description_ar : $this->description;
    }

    public function getOperatingHoursAttribute(): string
    {
        if ($this->operating_hours_start && $this->operating_hours_end) {
            return $this->operating_hours_start->format('H:i') . ' - ' . $this->operating_hours_end->format('H:i');
        }
        return '24/7';
    }

    public function getIsOpenNowAttribute(): bool
    {
        if (!$this->operating_hours_start || !$this->operating_hours_end) {
            return true; // 24/7 department
        }

        $currentTime = now()->format('H:i:s');
        return $currentTime >= $this->operating_hours_start->format('H:i:s') && 
               $currentTime <= $this->operating_hours_end->format('H:i:s');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEmergency($query)
    {
        return $query->where('emergency_department', true);
    }

    public function scopeOpenNow($query)
    {
        $currentTime = now()->format('H:i:s');
        return $query->where(function($q) use ($currentTime) {
            $q->whereNull('operating_hours_start')
              ->orWhere(function($subQ) use ($currentTime) {
                  $subQ->where('operating_hours_start', '<=', $currentTime)
                       ->where('operating_hours_end', '>=', $currentTime);
              });
        });
    }
}
