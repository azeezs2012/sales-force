<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class AccountValidator
{
    /**
     * Validate the account data.
     *
     * @param  array  $data
     * @param  int|null  $accountId
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validate(array $data, $accountId = null)
    {
        $rules = [
            'account_name' => 'required|string|max:255',
            'account_description' => 'nullable|string',
            'account_type_id' => 'required|exists:account_types,id',
            'parent_id' => 'nullable|exists:accounts,id',
        ];

        // Add unique validation for account_name per account_type_id
        if (isset($data['account_type_id'])) {
            $uniqueRule = 'unique:accounts,account_name,' . ($accountId ?: 'NULL') . ',id,account_type_id,' . $data['account_type_id'];
            $rules['account_name'] .= '|' . $uniqueRule;
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            $errorMessage = implode(' ', $errors);
            throw new \Exception($errorMessage);
        }

        return $validator;
    }
} 