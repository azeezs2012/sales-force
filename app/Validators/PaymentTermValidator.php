<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class PaymentTermValidator
{
    /**
     * Validate the payment term data.
     *
     * @param  array  $data
     * @param  int|null  $paymentTermId
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validate(array $data, $paymentTermId = null)
    {
        $rules = [
            'payment_term_name' => 'required|unique:payment_terms' . ($paymentTermId ? ',payment_term_name,' . $paymentTermId : ''),
            'duration_count' => 'required|integer',
            'duration_unit' => 'required|string',
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