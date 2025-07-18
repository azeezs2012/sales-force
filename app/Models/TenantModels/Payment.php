<?php

namespace App\Models\TenantModels;

use App\Models\TenantModels\Account;
use App\Models\TenantModels\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_id',
        'account_id',
        'payment_date',
        'payment_amount',
        'payment_reference',
        'payment_notes',
        'payment_status',
        'payment_method',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'payment_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    /**
     * Get settlements created from this payment
     */
    public function settlements()
    {
        return $this->morphMany(GrnSettlement::class, 'settlement_reference', 'settlement_reference_type', 'settlement_reference_id');
    }
}
