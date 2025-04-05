<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

/**
 * Validator for supplier data.
 */
class SupplierValidator
{
    /**
     * Validate the supplier data.
     *
     * @param  array  $data
     * @param  int|null  $supplierId
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validate(array $data, $supplierId = null)
    {
        $rules = [
            'supplier_code' => 'required|unique:suppliers' . ($supplierId ? ',supplier_code,' . $supplierId : ''),
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