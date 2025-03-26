<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class ProductCategoryValidator
{
    /**
     * Validate the product category data.
     *
     * @param  array  $data
     * @param  int|null  $productCategoryId
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validate(array $data, $productCategoryId = null)
    {
        $rules = [
            'category_name' => 'required|unique:product_categories' . ($productCategoryId ? ',category_name,' . $productCategoryId : ''),
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