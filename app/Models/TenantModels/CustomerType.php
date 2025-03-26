<?php

namespace App\Models\TenantModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class CustomerType extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type_name',
        'active',
        'approved',
        'created_by',
        'updated_by',
        'deleted_by',
        'approved_by',
        'parent',
    ];

    /**
     * Get the user who created the customer type.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who updated the customer type.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the customer type.
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the user who approved the customer type.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the parent customer type.
     */
    public function parentType()
    {
        return $this->belongsTo(CustomerType::class, 'parent');
    }

    /**
     * Get the child customer types.
     */
    public function childTypes()
    {
        return $this->hasMany(CustomerType::class, 'parent');
    }
} 