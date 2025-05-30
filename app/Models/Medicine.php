<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Medicine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_ar',
        'generic_name',
        'brand_name',
        'code',
        'barcode',
        'category',
        'description',
        'description_ar',
        'dosage_form',
        'strength',
        'unit',
        'manufacturer',
        'supplier',
        'purchase_price',
        'selling_price',
        'stock_quantity',
        'minimum_stock_level',
        'maximum_stock_level',
        'expiry_date',
        'batch_number',
        'storage_conditions',
        'side_effects',
        'contraindications',
        'interactions',
        'pregnancy_category',
        'controlled_substance',
        'requires_prescription',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'minimum_stock_level' => 'integer',
        'maximum_stock_level' => 'integer',
        'side_effects' => 'array',
        'contraindications' => 'array',
        'interactions' => 'array',
        'controlled_substance' => 'boolean',
        'requires_prescription' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Constants for categories
    const CATEGORY_ANTIBIOTIC = 'antibiotic';
    const CATEGORY_ANALGESIC = 'analgesic';
    const CATEGORY_ANTIHYPERTENSIVE = 'antihypertensive';
    const CATEGORY_ANTIDIABETIC = 'antidiabetic';
    const CATEGORY_VITAMIN = 'vitamin';
    const CATEGORY_SUPPLEMENT = 'supplement';

    // Constants for dosage forms
    const FORM_TABLET = 'tablet';
    const FORM_CAPSULE = 'capsule';
    const FORM_SYRUP = 'syrup';
    const FORM_INJECTION = 'injection';
    const FORM_CREAM = 'cream';
    const FORM_DROPS = 'drops';

    // Relationships
    public function prescriptions(): BelongsToMany
    {
        return $this->belongsToMany(Prescription::class, 'prescription_medicines')
                    ->withPivot(['quantity', 'dosage', 'frequency', 'duration', 'instructions'])
                    ->withTimestamps();
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
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

    public function getFullNameAttribute(): string
    {
        $name = $this->display_name;
        if ($this->strength) {
            $name .= ' ' . $this->strength;
        }
        if ($this->dosage_form) {
            $name .= ' (' . ucfirst($this->dosage_form) . ')';
        }
        return $name;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock_quantity <= 0) {
            return 'out_of_stock';
        } elseif ($this->stock_quantity <= $this->minimum_stock_level) {
            return 'low_stock';
        } elseif ($this->stock_quantity >= $this->maximum_stock_level) {
            return 'overstock';
        }
        return 'normal';
    }

    public function getStockStatusColorAttribute(): string
    {
        return match($this->stock_status) {
            'out_of_stock' => 'danger',
            'low_stock' => 'warning',
            'overstock' => 'info',
            default => 'success',
        };
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsNearExpiryAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->diffInDays(now()) <= 30;
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->purchase_price > 0) {
            return (($this->selling_price - $this->purchase_price) / $this->purchase_price) * 100;
        }
        return 0;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock_quantity', '<=', 'minimum_stock_level');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock_quantity', '<=', 0);
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    public function scopeNearExpiry($query, $days = 30)
    {
        return $query->where('expiry_date', '<=', now()->addDays($days))
                    ->where('expiry_date', '>', now());
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeRequiresPrescription($query)
    {
        return $query->where('requires_prescription', true);
    }

    public function scopeControlledSubstance($query)
    {
        return $query->where('controlled_substance', true);
    }
}
