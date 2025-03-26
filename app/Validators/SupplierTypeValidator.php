<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class SupplierTypeValidator
{
    /**
     * Validate the supplier type data.
     *
     * @param  array  $data
     * @param  int|null  $supplierTypeId
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validate(array $data, $supplierTypeId = null)
    {
        $rules = [
            'type_name' => 'required|unique:supplier_types' . ($supplierTypeId ? ',type_name,' . $supplierTypeId : ''),
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