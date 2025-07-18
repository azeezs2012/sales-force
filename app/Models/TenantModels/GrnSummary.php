<?php

namespace App\Models\TenantModels;

use App\Models\TenantModels\Account;
use App\Models\TenantModels\GrnDetail;
use App\Models\TenantModels\Location;
use App\Models\TenantModels\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrnSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_date',
        'supplier_id',
        'location_id',
        'grn_billing_address',
        'grn_delivery_address',
        'ap_account_id',
        'grn_status',
        'total_amount',
        'total_payment_received_amount',
        'total_grn_credit_settled_amount',
        'total_settled_amount'
    ];

    protected $casts = [
        'grn_date' => 'date',
        'total_amount' => 'decimal:2',
        'total_payment_received_amount' => 'decimal:2',
        'total_grn_credit_settled_amount' => 'decimal:2',
        'total_settled_amount' => 'decimal:2',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(GrnDetail::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ap_account_id');
    }

    /**
     * Calculate the remaining unsettled amount
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->total_amount - $this->total_settled_amount;
    }

    /**
     * Check if the GRN is fully settled
     */
    public function isFullySettled(): bool
    {
        return $this->total_settled_amount >= $this->total_amount;
    }

    /**
     * Check if the GRN is partially settled
     */
    public function isPartiallySettled(): bool
    {
        return $this->total_settled_amount > 0 && $this->total_settled_amount < $this->total_amount;
    }

    /**
     * Update settlement totals based on settlements
     */
    public function updateSettlementTotals(): void
    {
        // Calculate total payment received from settlements
        $totalPaymentReceived = $this->settlements()
            ->where('settlement_type', 'payment')
            ->sum('settlement_amount');

        // Calculate total GRN credit settled from settlements
        $totalGrnCreditSettled = $this->settlements()
            ->where('settlement_type', 'grn_credit')
            ->sum('settlement_amount');

        // Update the totals
        $this->total_payment_received_amount = $totalPaymentReceived;
        $this->total_grn_credit_settled_amount = $totalGrnCreditSettled;
        $this->total_settled_amount = $totalPaymentReceived + $totalGrnCreditSettled;

        // Update status based on settlement
        if ($this->total_settled_amount >= $this->total_amount) {
            $this->grn_status = 'Closed';
        } elseif ($this->total_settled_amount > 0) {
            $this->grn_status = 'Partial';
        } else {
            $this->grn_status = 'Open';
        }

        $this->save();
    }

    /**
     * Get settlements for this GRN
     */
    public function settlements()
    {
        return $this->hasMany(GrnSettlement::class);
    }
} 