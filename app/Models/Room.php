<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_number',
        'ward_id',
        'department_id',
        'room_type',
        'bed_count',
        'available_beds',
        'daily_rate',
        'has_ac',
        'has_tv',
        'has_wifi',
        'has_bathroom',
        'equipment',
        'status',
        'description',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'bed_count' => 'integer',
        'available_beds' => 'integer',
        'daily_rate' => 'decimal:2',
        'has_ac' => 'boolean',
        'has_tv' => 'boolean',
        'has_wifi' => 'boolean',
        'has_bathroom' => 'boolean',
        'equipment' => 'array',
        'is_active' => 'boolean',
    ];

    // Constants for room types
    const TYPE_SINGLE = 'single';
    const TYPE_DOUBLE = 'double';
    const TYPE_TRIPLE = 'triple';
    const TYPE_WARD = 'ward';
    const TYPE_ICU = 'icu';
    const TYPE_OPERATION_THEATER = 'operation_theater';
    const TYPE_CONSULTATION = 'consultation';

    // Constants for status
    const STATUS_AVAILABLE = 'available';
    const STATUS_OCCUPIED = 'occupied';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_CLEANING = 'cleaning';
    const STATUS_RESERVED = 'reserved';

    // Relationships
    public function ward(): BelongsTo
    {
        return $this->belongsTo(Ward::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function roomAssignments(): HasMany
    {
        return $this->hasMany(RoomAssignment::class);
    }

    public function currentAssignments(): HasMany
    {
        return $this->hasMany(RoomAssignment::class)
                    ->whereNull('discharge_date')
                    ->where('status', 'active');
    }

    // Accessors
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_AVAILABLE => 'success',
            self::STATUS_OCCUPIED => 'danger',
            self::STATUS_MAINTENANCE => 'warning',
            self::STATUS_CLEANING => 'info',
            self::STATUS_RESERVED => 'primary',
            default => 'secondary',
        };
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->status === self::STATUS_AVAILABLE && $this->available_beds > 0;
    }

    public function getIsOccupiedAttribute(): bool
    {
        return $this->status === self::STATUS_OCCUPIED;
    }

    public function getOccupancyRateAttribute(): float
    {
        if ($this->bed_count <= 0) {
            return 0;
        }
        $occupiedBeds = $this->bed_count - $this->available_beds;
        return ($occupiedBeds / $this->bed_count) * 100;
    }

    public function getAmenitiesListAttribute(): array
    {
        $amenities = [];
        
        if ($this->has_ac) $amenities[] = 'Air Conditioning';
        if ($this->has_tv) $amenities[] = 'Television';
        if ($this->has_wifi) $amenities[] = 'WiFi';
        if ($this->has_bathroom) $amenities[] = 'Private Bathroom';
        
        return $amenities;
    }

    public function getEquipmentListAttribute(): array
    {
        return $this->equipment ?? [];
    }

    public function getCurrentPatientsAttribute()
    {
        return $this->currentAssignments()
                    ->with('patient')
                    ->get()
                    ->pluck('patient');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE)
                    ->where('available_beds', '>', 0);
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', self::STATUS_OCCUPIED);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('room_type', $type);
    }

    public function scopeByWard($query, $wardId)
    {
        return $query->where('ward_id', $wardId);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    public function scopeWithAmenity($query, $amenity)
    {
        return $query->where($amenity, true);
    }

    public function scopeInPriceRange($query, $minPrice, $maxPrice)
    {
        return $query->whereBetween('daily_rate', [$minPrice, $maxPrice]);
    }

    // Methods
    public function assignPatient(Patient $patient, User $assignedBy = null): ?RoomAssignment
    {
        if (!$this->is_available) {
            return null;
        }

        $assignment = RoomAssignment::create([
            'room_id' => $this->id,
            'patient_id' => $patient->id,
            'admission_date' => now(),
            'status' => 'active',
            'assigned_by' => $assignedBy?->id,
        ]);

        $this->decrement('available_beds');
        
        if ($this->available_beds <= 0) {
            $this->update(['status' => self::STATUS_OCCUPIED]);
        }

        // Update ward bed count
        $this->ward->updateBedCounts();

        return $assignment;
    }

    public function dischargePatient(Patient $patient, User $dischargedBy = null): bool
    {
        $assignment = $this->currentAssignments()
                          ->where('patient_id', $patient->id)
                          ->first();

        if (!$assignment) {
            return false;
        }

        $assignment->update([
            'discharge_date' => now(),
            'status' => 'discharged',
            'discharged_by' => $dischargedBy?->id,
        ]);

        $this->increment('available_beds');
        
        if ($this->available_beds > 0 && $this->status === self::STATUS_OCCUPIED) {
            $this->update(['status' => self::STATUS_AVAILABLE]);
        }

        // Update ward bed count
        $this->ward->updateBedCounts();

        return true;
    }

    public function setMaintenance(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_MAINTENANCE,
            'notes' => $reason,
        ]);
    }

    public function setCleaning(): void
    {
        $this->update(['status' => self::STATUS_CLEANING]);
    }

    public function makeAvailable(): void
    {
        if ($this->available_beds > 0) {
            $this->update(['status' => self::STATUS_AVAILABLE]);
        }
    }

    public function calculateDailyRevenue(): float
    {
        $occupiedBeds = $this->bed_count - $this->available_beds;
        return $occupiedBeds * $this->daily_rate;
    }
}
