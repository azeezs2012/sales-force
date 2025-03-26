<?php

namespace App\Models\TenantModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class ProductCategory extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'category_name',
        'active',
        'approved',
        'created_by',
        'updated_by',
        'deleted_by',
        'approved_by',
        'parent',
    ];

    /**
     * Get the user who created the product category.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who updated the product category.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the product category.
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the user who approved the product category.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the parent product category.
     */
    public function parentCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'parent');
    }

    /**
     * Get the child product categories.
     */
    public function childCategories()
    {
        return $this->hasMany(ProductCategory::class, 'parent');
    }
} 