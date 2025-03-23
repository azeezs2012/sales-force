<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class BranchValidator
{
    /**
     * Validate the branch data.
     *
     * @param  array  $data
     * @param  int|null  $branchId
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validate(array $data, $branchId = null)
    {
        $rules = [
            'branch_name' => 'required|unique:branches' . ($branchId ? ',branch_name,' . $branchId : ''),
            'active' => 'required|boolean',
            'approved' => 'required|boolean',
            'parent' => 'nullable|exists:branches,id',
        ];

        return Validator::make($data, $rules);
    }
} 