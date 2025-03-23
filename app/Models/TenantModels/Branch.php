<?php

namespace App\Models\TenantModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Branch extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'branch_name',
        'active',
        'approved',
        'parent',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Get the parent branch.
     */
    public function parentBranch()
    {
        return $this->belongsTo(Branch::class, 'parent');
    }

    /**
     * Get the user who created the branch.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who updated the branch.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the branch.
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the child branches.
     */
    public function childBranches()
    {
        return $this->hasMany(Branch::class, 'parent');
    }
} 