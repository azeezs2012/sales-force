<?php

namespace App\Models\TenantModels;

use App\Models\TenantModels\Account;
use App\Models\TenantModels\GrnCreditDetail;
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
        'credit_reason'
    ];

    protected $casts = [
        'grn_credit_date' => 'date',
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
}
