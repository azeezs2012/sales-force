<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class CustomerValidator
{
    /**
     * Validate the customer data.
     *
     * @param  array  $data
     * @param  int|null  $customerId
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validate(array $data, $customerId = null)
    {
        $rules = [
            'customer_code' => 'required|unique:customers' . ($customerId ? ',customer_code,' . $customerId : ''),
            'credit_limit' => 'nullable|numeric|min:0',
            'phone_no' => 'nullable|string|max:15',
            'default_payment_method' => 'nullable|exists:payment_methods,id',
            'default_payment_term' => 'nullable|exists:payment_terms,id',
            'active' => 'required|boolean',
            'approved' => 'required|boolean',
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