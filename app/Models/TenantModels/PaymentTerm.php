<?php

namespace App\Models\TenantModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class PaymentTerm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_term_name',
        'duration_count',
        'duration_unit',
        'active',
        'approved',
        'approved_by',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    // Define relationships
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
} 