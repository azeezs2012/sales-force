<?php

namespace App\Models\TenantModels;

use App\Models\TenantModels\GrnDetail;
use App\Models\TenantModels\Location;
use App\Models\TenantModels\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnCreditDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_credit_summary_id',
        'product_id',
        'location_id',
        'quantity',
        'cost',
        'total',
        'grn_detail_id'
    ];

    public function grnCreditSummary(): BelongsTo
    {
        return $this->belongsTo(GrnCreditSummary::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function grnDetail(): BelongsTo
    {
        return $this->belongsTo(GrnDetail::class);
    }
}
