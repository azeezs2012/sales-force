<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class PaymentMethodValidator
{
    /**
     * Validate the payment method data.
     *
     * @param  array  $data
     * @param  int|null  $paymentMethodId
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validate(array $data, $paymentMethodId = null)
    {
        $rules = [
            'method_name' => 'required|unique:payment_methods' . ($paymentMethodId ? ',method_name,' . $paymentMethodId : ''),
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