<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class LocationValidator
{
    /**
     * Validate the location data.
     *
     * @param  array  $data
     * @param  int|null  $locationId
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validate(array $data, $locationId = null)
    {
        $rules = [
            'location_name' => 'required|unique:locations' . ($locationId ? ',location_name,' . $locationId : ''),
            'active' => 'required|boolean',
            'approved' => 'required|boolean',
            'parent' => 'nullable|exists:locations,id',
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