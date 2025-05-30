<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_ar',
        'code',
        'department_id',
        'description',
        'location',
        'total_beds',
        'available_beds',
        'ward_type',
        'head_nurse_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'total_beds' => 'integer',
        'available_beds' => 'integer',
        'is_active' => 'boolean',
    ];

    // Constants for ward types
    const TYPE_GENERAL = 'general';
    const TYPE_PRIVATE = 'private';
    const TYPE_ICU = 'icu';
    const TYPE_EMERGENCY = 'emergency';
    const TYPE_MATERNITY = 'maternity';
    const TYPE_PEDIATRIC = 'pediatric';
    const TYPE_SURGICAL = 'surgical';

    // Relationships
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function headNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_nurse_id');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' && $this->name_ar ? $this->name_ar : $this->name;
    }

    public function getOccupancyRateAttribute(): float
    {
        if ($this->total_beds <= 0) {
            return 0;
        }
        $occupiedBeds = $this->total_beds - $this->available_beds;
        return ($occupiedBeds / $this->total_beds) * 100;
    }

    public function getOccupancyStatusAttribute(): string
    {
        $rate = $this->occupancy_rate;
        
        if ($rate >= 90) {
            return 'critical';
        } elseif ($rate >= 75) {
            return 'high';
        } elseif ($rate >= 50) {
            return 'moderate';
        } else {
            return 'low';
        }
    }

    public function getOccupancyColorAttribute(): string
    {
        return match($this->occupancy_status) {
            'critical' => 'danger',
            'high' => 'warning',
            'moderate' => 'info',
            'low' => 'success',
            default => 'secondary',
        };
    }

    public function getHasAvailableBedsAttribute(): bool
    {
        return $this->available_beds > 0;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('ward_type', $type);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeWithAvailableBeds($query)
    {
        return $query->where('available_beds', '>', 0);
    }

    public function scopeHighOccupancy($query, $threshold = 80)
    {
        return $query->whereRaw('((total_beds - available_beds) / total_beds * 100) >= ?', [$threshold]);
    }

    public function scopeLowOccupancy($query, $threshold = 30)
    {
        return $query->whereRaw('((total_beds - available_beds) / total_beds * 100) <= ?', [$threshold]);
    }

    // Methods
    public function updateBedCounts(): void
    {
        $totalBeds = $this->rooms()->sum('bed_count');
        $availableBeds = $this->rooms()->sum('available_beds');
        
        $this->update([
            'total_beds' => $totalBeds,
            'available_beds' => $availableBeds,
        ]);
    }

    public function allocateBed(): bool
    {
        if ($this->available_beds > 0) {
            $this->decrement('available_beds');
            return true;
        }
        return false;
    }

    public function releaseBed(): void
    {
        if ($this->available_beds < $this->total_beds) {
            $this->increment('available_beds');
        }
    }

    public function getAvailableRooms()
    {
        return $this->rooms()
                    ->where('status', Room::STATUS_AVAILABLE)
                    ->where('available_beds', '>', 0)
                    ->get();
    }
}
