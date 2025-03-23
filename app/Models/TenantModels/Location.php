<?php

namespace App\Models\TenantModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'location_name',
        'active',
        'approved',
        'parent',
        'created_by',
        'updated_by',
        'deleted_by',
        'approved_by',
    ];

    /**
     * Get the parent location.
     */
    public function parentLocation()
    {
        return $this->belongsTo(Location::class, 'parent');
    }

    /**
     * Get the user who created the location.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who updated the location.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the location.
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    /**
     * Get the user who approved the location.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the child locations.
     */
    public function childLocations()
    {
        return $this->hasMany(Location::class, 'parent');
    }
} 