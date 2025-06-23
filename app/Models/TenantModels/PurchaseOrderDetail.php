<?php

namespace App\Models\TenantModels;

use App\Models\GrnDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrderDetail extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'purchase_order_id',
        'product_id',
        'location_id',
        'quantity',
        'cost',
        'total',
        'description',
        'received_quantity'
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function grnDetails(): HasMany
    {
        return $this->hasMany(GrnDetail::class);
    }
} 