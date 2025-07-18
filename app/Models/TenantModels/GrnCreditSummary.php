<?php

namespace App\Models\TenantModels;

use App\Models\TenantModels\Account;
use App\Models\TenantModels\GrnCreditDetail;
use App\Models\TenantModels\GrnCreditSettlement;
use App\Models\TenantModels\Location;
use App\Models\TenantModels\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrnCreditSummary extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'grn_credit_date',
        'supplier_id',
        'location_id',
        'grn_credit_billing_address',
        'grn_credit_delivery_address',
        'ap_account_id',
        'grn_credit_status',
        'total_amount',
        'total_payment_applied_amount',
        'total_settled_amount',
        'credit_reason'
    ];

    protected $casts = [
        'grn_credit_date' => 'date',
        'total_amount' => 'decimal:2',
        'total_payment_applied_amount' => 'decimal:2',
        'total_settled_amount' => 'decimal:2',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(GrnCreditDetail::class);
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
     * Get settlements for this GRN Credit
     */
    public function settlements(): HasMany
    {
        return $this->hasMany(GrnCreditSettlement::class);
    }

    /**
     * Calculate the remaining unsettled amount
     */
    public function getRemainingAmountAttribute(): float
    {
        return $this->total_amount - $this->total_settled_amount;
    }

    /**
     * Check if the GRN Credit is fully settled
     */
    public function isFullySettled(): bool
    {
        return $this->total_settled_amount >= $this->total_amount;
    }

    /**
     * Check if the GRN Credit is partially settled
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
        // Calculate total payment applied from settlements
        $totalPaymentApplied = $this->settlements()
            ->where('settlement_type', 'payment_applied')
            ->sum('settlement_amount');

        // Update the totals
        $this->total_payment_applied_amount = $totalPaymentApplied;
        $this->total_settled_amount = $totalPaymentApplied;

        // Update status based on settlement
        if ($this->total_settled_amount >= $this->total_amount) {
            $this->grn_credit_status = 'Closed';
        } elseif ($this->total_settled_amount > 0) {
            $this->grn_credit_status = 'Partial';
        } else {
            $this->grn_credit_status = 'Open';
        }

        $this->save();
    }
}
