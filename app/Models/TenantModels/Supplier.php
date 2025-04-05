<?php

namespace App\Models\TenantModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\TenantModels\PaymentMethod;
use App\Models\TenantModels\PaymentTerm;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'supplier_code',
        'phone_no',
        'default_payment_method',
        'default_payment_term',
        'active',
        'approved',
        'approved_by',
        'created_by',
        'updated_by',
        'deleted_by',
        'user_id',
    ];

    protected $casts = [
        'active' => 'boolean',
        'approved' => 'boolean',
    ];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function defaultPaymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'default_payment_method');
    }

    public function defaultPaymentTerm()
    {
        return $this->belongsTo(PaymentTerm::class, 'default_payment_term');
    }
} 