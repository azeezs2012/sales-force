<?php

namespace App\Models\TenantModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class ProductType extends Model
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
     * Get the user who created the product type.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who updated the product type.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the product type.
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the user who approved the product type.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the parent product type.
     */
    public function parentType()
    {
        return $this->belongsTo(ProductType::class, 'parent');
    }

    /**
     * Get the child product types.
     */
    public function childTypes()
    {
        return $this->hasMany(ProductType::class, 'parent');
    }
} 