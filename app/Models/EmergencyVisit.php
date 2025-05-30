<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_number',
        'patient_id',
        'patient_name',
        'patient_phone',
        'arrival_time',
        'chief_complaint',
        'incident_details',
        'triage_level',
        'arrival_mode',
        'triage_nurse_id',
        'attending_doctor_id',
        'room_id',
        'status',
        'seen_at',
        'completed_at',
        'treatment_given',
        'disposition',
        'notes',
    ];

    protected $casts = [
        'arrival_time' => 'datetime',
        'seen_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Constants for triage levels
    const TRIAGE_CRITICAL = 'critical';
    const TRIAGE_URGENT = 'urgent';
    const TRIAGE_LESS_URGENT = 'less_urgent';
    const TRIAGE_NON_URGENT = 'non_urgent';

    // Constants for arrival modes
    const ARRIVAL_WALK_IN = 'walk_in';
    const ARRIVAL_AMBULANCE = 'ambulance';
    const ARRIVAL_POLICE = 'police';
    const ARRIVAL_REFERRAL = 'referral';

    // Constants for status
    const STATUS_WAITING = 'waiting';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ADMITTED = 'admitted';
    const STATUS_DISCHARGED = 'discharged';
    const STATUS_TRANSFERRED = 'transferred';

    // Relationships
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function triageNurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triage_nurse_id');
    }

    public function attendingDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'attending_doctor_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    // Accessors
    public function getTriageLevelColorAttribute(): string
    {
        return match($this->triage_level) {
            self::TRIAGE_CRITICAL => 'danger',
            self::TRIAGE_URGENT => 'warning',
            self::TRIAGE_LESS_URGENT => 'info',
            self::TRIAGE_NON_URGENT => 'success',
            default => 'secondary',
        };
    }

    public function getTriageLevelPriorityAttribute(): int
    {
        return match($this->triage_level) {
            self::TRIAGE_CRITICAL => 1,
            self::TRIAGE_URGENT => 2,
            self::TRIAGE_LESS_URGENT => 3,
            self::TRIAGE_NON_URGENT => 4,
            default => 5,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_WAITING => 'warning',
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_ADMITTED => 'info',
            self::STATUS_DISCHARGED => 'secondary',
            self::STATUS_TRANSFERRED => 'dark',
            default => 'light',
        };
    }

    public function getPatientDisplayNameAttribute(): string
    {
        return $this->patient?->full_name ?? $this->patient_name ?? 'Unknown Patient';
    }

    public function getWaitingTimeAttribute(): ?int
    {
        if ($this->seen_at) {
            return $this->arrival_time->diffInMinutes($this->seen_at);
        }
        
        if ($this->status === self::STATUS_WAITING) {
            return $this->arrival_time->diffInMinutes(now());
        }
        
        return null;
    }

    public function getTreatmentTimeAttribute(): ?int
    {
        if ($this->seen_at && $this->completed_at) {
            return $this->seen_at->diffInMinutes($this->completed_at);
        }
        
        if ($this->seen_at && in_array($this->status, [self::STATUS_IN_PROGRESS])) {
            return $this->seen_at->diffInMinutes(now());
        }
        
        return null;
    }

    public function getTotalTimeAttribute(): ?int
    {
        if ($this->completed_at) {
            return $this->arrival_time->diffInMinutes($this->completed_at);
        }
        
        if (in_array($this->status, [self::STATUS_WAITING, self::STATUS_IN_PROGRESS])) {
            return $this->arrival_time->diffInMinutes(now());
        }
        
        return null;
    }

    // Scopes
    public function scopeByTriageLevel($query, $level)
    {
        return $query->where('triage_level', $level);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', self::STATUS_WAITING);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCritical($query)
    {
        return $query->where('triage_level', self::TRIAGE_CRITICAL);
    }

    public function scopeUrgent($query)
    {
        return $query->whereIn('triage_level', [self::TRIAGE_CRITICAL, self::TRIAGE_URGENT]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('arrival_time', today());
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_WAITING, self::STATUS_IN_PROGRESS]);
    }

    public function scopeOrderByPriority($query)
    {
        return $query->orderByRaw("
            CASE triage_level 
                WHEN 'critical' THEN 1 
                WHEN 'urgent' THEN 2 
                WHEN 'less_urgent' THEN 3 
                WHEN 'non_urgent' THEN 4 
                ELSE 5 
            END
        ")->orderBy('arrival_time');
    }

    // Methods
    public function assignDoctor(Doctor $doctor, User $assignedBy = null): void
    {
        $this->update([
            'attending_doctor_id' => $doctor->id,
            'status' => self::STATUS_IN_PROGRESS,
            'seen_at' => now(),
        ]);
    }

    public function assignRoom(Room $room): void
    {
        if ($room->is_available) {
            $this->update(['room_id' => $room->id]);
        }
    }

    public function complete(string $treatment = null, string $disposition = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'treatment_given' => $treatment,
            'disposition' => $disposition,
        ]);
    }

    public function admit(Room $room = null): void
    {
        $updateData = [
            'status' => self::STATUS_ADMITTED,
            'completed_at' => now(),
        ];

        if ($room) {
            $updateData['room_id'] = $room->id;
        }

        $this->update($updateData);
    }

    public function discharge(string $disposition = null): void
    {
        $this->update([
            'status' => self::STATUS_DISCHARGED,
            'completed_at' => now(),
            'disposition' => $disposition,
        ]);
    }

    // Boot method to auto-generate visit number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($visit) {
            if (empty($visit->visit_number)) {
                $visit->visit_number = 'ER-' . now()->format('Ymd') . '-' . str_pad(
                    EmergencyVisit::whereDate('created_at', today())->count() + 1,
                    4,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }
}
