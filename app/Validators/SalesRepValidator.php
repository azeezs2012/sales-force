<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class SalesRepValidator
{
    /**
     * Validate the sales representative data.
     *
     * @param  array  $data
     * @param  int|null  $salesRepId
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validate(array $data, $salesRepId = null)
    {
        $rules = [
            'sales_rep_name' => 'required|unique:sales_reps' . ($salesRepId ? ',sales_rep_name,' . $salesRepId : ''),
            'active' => 'required|boolean',
            'approved' => 'required|boolean',
            'parent' => 'nullable|exists:sales_reps,id',
        ];

        return Validator::make($data, $rules);
    }
} 