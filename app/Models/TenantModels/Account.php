<?php

namespace App\Models\TenantModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Account extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account_name',
        'account_description',
        'account_type_id',
        'parent_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Get the account type.
     */
    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }

    /**
     * Get the parent account.
     */
    public function parentAccount()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Get the child accounts.
     */
    public function childAccounts()
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * Get the user who created the account.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who updated the account.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the user who deleted the account.
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
} 