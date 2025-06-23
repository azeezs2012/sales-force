<?php

namespace App\Models;

use App\Models\TenantModels\Account;
use App\Models\TenantModels\GrnDetail;
use App\Models\TenantModels\Location;
use App\Models\TenantModels\Supplier;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrnSummary extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'grn_date',
        'supplier_id',
        'location_id',
        'grn_billing_address',
        'grn_delivery_address',
        'ap_account_id',
        'grn_status',
        'total_amount'
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
}
