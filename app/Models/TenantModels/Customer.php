<?php

namespace App\Models\TenantModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_code',
        'credit_limit',
        'phone_no',
        'default_payment_method',
        'default_payment_term',
        'active',
        'approved',
        'created_by',
        'updated_by',
        'deleted_by',
        'approved_by',
        'user_id',
        'parent',
    ];

    // Define relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function parent()
    {
        return $this->belongsTo(Customer::class, 'parent');
    }

    public function childCustomers()
    {
        return $this->hasMany(Customer::class, 'parent');
    }
} 