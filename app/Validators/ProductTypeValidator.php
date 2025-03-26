<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class ProductTypeValidator
{
    /**
     * Validate the product type data.
     *
     * @param  array  $data
     * @param  int|null  $productTypeId
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validate(array $data, $productTypeId = null)
    {
        $rules = [
            'type_name' => 'required|unique:product_types' . ($productTypeId ? ',type_name,' . $productTypeId : ''),
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