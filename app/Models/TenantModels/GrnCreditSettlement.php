<?php

namespace App\Models\TenantModels;

use App\Models\TenantModels\GrnCreditSummary;
use App\Models\TenantModels\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GrnCreditSettlement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'grn_credit_summary_id',
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

    public function grnCreditSummary(): BelongsTo
    {
        return $this->belongsTo(GrnCreditSummary::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'settlement_reference_id')
            ->where('settlement_reference_type', 'payments');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
