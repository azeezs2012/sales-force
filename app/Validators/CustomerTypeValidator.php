<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class CustomerTypeValidator
{
    /**
     * Validate the customer type data.
     *
     * @param  array  $data
     * @param  int|null  $customerTypeId
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validate(array $data, $customerTypeId = null)
    {
        $rules = [
            'type_name' => 'required|unique:customer_types' . ($customerTypeId ? ',type_name,' . $customerTypeId : ''),
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