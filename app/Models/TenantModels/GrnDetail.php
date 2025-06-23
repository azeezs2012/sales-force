<?php

namespace App\Models\TenantModels;

use App\Models\TenantModels\Location;
use App\Models\TenantModels\Product;
use App\Models\TenantModels\PurchaseOrderDetail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrnDetail extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'grn_summary_id',
        'product_id',
        'location_id',
        'quantity',
        'cost',
        'total',
        'purchase_order_detail_id'
    ];

    public function grnSummary(): BelongsTo
    {
        return $this->belongsTo(GrnSummary::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function purchaseOrderDetail(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderDetail::class);
    }
}
