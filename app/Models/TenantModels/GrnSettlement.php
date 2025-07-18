<?php

namespace App\Models\TenantModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GrnSettlement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'grn_summary_id',
        'settlement_type',
        'settlement_reference_id',
        'settlement_reference_type',
        'settlement_amount',
        'settlement_date',
        'settlement_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'settlement_amount' => 'decimal:2',
    ];

    /**
     * Get the GRN summary that this settlement belongs to
     */
    public function grnSummary(): BelongsTo
    {
        return $this->belongsTo(GrnSummary::class);
    }

    /**
     * Get the settlement reference (payment or GRN credit)
     */
    public function settlementReference(): MorphTo
    {
        return $this->morphTo('settlement_reference');
    }

    /**
     * Get the user who created this settlement
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this settlement
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get settlements by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('settlement_type', $type);
    }

    /**
     * Scope to get payment settlements
     */
    public function scopePayments($query)
    {
        return $query->byType('payment');
    }

    /**
     * Scope to get GRN credit settlements
     */
    public function scopeGrnCredits($query)
    {
        return $query->byType('grn_credit');
    }
} 