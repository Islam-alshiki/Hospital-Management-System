<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'patient_id',
        'admission_date',
        'discharge_date',
        'status',
        'assigned_by',
        'discharged_by',
        'admission_notes',
        'discharge_notes',
    ];

    protected $casts = [
        'admission_date' => 'datetime',
        'discharge_date' => 'datetime',
    ];

    // Constants for status
    const STATUS_ACTIVE = 'active';
    const STATUS_DISCHARGED = 'discharged';
    const STATUS_TRANSFERRED = 'transferred';

    // Relationships
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function dischargedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discharged_by');
    }

    // Accessors
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_DISCHARGED => 'info',
            self::STATUS_TRANSFERRED => 'warning',
            default => 'secondary',
        };
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getLengthOfStayAttribute(): ?int
    {
        $endDate = $this->discharge_date ?? now();
        return $this->admission_date->diffInDays($endDate);
    }

    public function getLengthOfStayHoursAttribute(): ?int
    {
        $endDate = $this->discharge_date ?? now();
        return $this->admission_date->diffInHours($endDate);
    }

    public function getTotalCostAttribute(): float
    {
        $days = max(1, $this->length_of_stay);
        return $days * $this->room->daily_rate;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByRoom($query, $roomId)
    {
        return $query->where('room_id', $roomId);
    }

    public function scopeByPatient($query, $patientId)
    {
        return $query->where('patient_id', $patientId);
    }

    public function scopeCurrentAdmissions($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->whereNull('discharge_date');
    }

    public function scopeAdmittedToday($query)
    {
        return $query->whereDate('admission_date', today());
    }

    public function scopeDischargedToday($query)
    {
        return $query->whereDate('discharge_date', today());
    }

    public function scopeLongStay($query, $days = 7)
    {
        return $query->where('admission_date', '<=', now()->subDays($days))
                    ->where('status', self::STATUS_ACTIVE);
    }

    // Methods
    public function discharge(User $user = null, string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_DISCHARGED,
            'discharge_date' => now(),
            'discharged_by' => $user?->id,
            'discharge_notes' => $notes,
        ]);

        // Update room availability
        $this->room->increment('available_beds');
        if ($this->room->available_beds > 0 && $this->room->status === Room::STATUS_OCCUPIED) {
            $this->room->update(['status' => Room::STATUS_AVAILABLE]);
        }

        // Update ward bed counts
        $this->room->ward->updateBedCounts();
    }

    public function transfer(Room $newRoom, User $user = null, string $notes = null): ?RoomAssignment
    {
        if (!$newRoom->is_available) {
            return null;
        }

        // Mark current assignment as transferred
        $this->update([
            'status' => self::STATUS_TRANSFERRED,
            'discharge_date' => now(),
            'discharged_by' => $user?->id,
            'discharge_notes' => $notes,
        ]);

        // Create new assignment
        $newAssignment = RoomAssignment::create([
            'room_id' => $newRoom->id,
            'patient_id' => $this->patient_id,
            'admission_date' => now(),
            'status' => self::STATUS_ACTIVE,
            'assigned_by' => $user?->id,
            'admission_notes' => "Transferred from Room {$this->room->room_number}",
        ]);

        // Update room availabilities
        $this->room->increment('available_beds');
        if ($this->room->available_beds > 0 && $this->room->status === Room::STATUS_OCCUPIED) {
            $this->room->update(['status' => Room::STATUS_AVAILABLE]);
        }

        $newRoom->decrement('available_beds');
        if ($newRoom->available_beds <= 0) {
            $newRoom->update(['status' => Room::STATUS_OCCUPIED]);
        }

        // Update ward bed counts
        $this->room->ward->updateBedCounts();
        $newRoom->ward->updateBedCounts();

        return $newAssignment;
    }
}
