<?php

namespace App\Models\TenantModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class SalesRep extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sales_rep_name',
        'active',
        'approved',
        'parent',
        'created_by',
        'updated_by',
        'deleted_by',
        'approved_by',
    ];

    /**
     * Get the parent sales representative.
     */
    public function parentSalesRep()
    {
        return $this->belongsTo(SalesRep::class, 'parent');
    }

    /**
     * Get the user who created the sales representative.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who updated the sales representative.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the sales representative.
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the user who approved the sales representative.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the child sales representatives.
     */
    public function childSalesReps()
    {
        return $this->hasMany(SalesRep::class, 'parent');
    }
} 