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
            'name' => 'required|string|max:255',
            'code' => 'required|unique:sales_reps' . ($salesRepId ? ',code,' . $salesRepId : ''),
            'email' => 'required|email|unique:users' . ($salesRepId ? ',email,' . $salesRepId : ''),
            'active' => 'required|boolean',
            'approved' => 'required|boolean',
            'parent' => 'nullable|exists:sales_reps,id',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $errorMessage = implode(' ', $errors);
            throw new \Exception($errorMessage);
        }

        return $validator;
    }
} 