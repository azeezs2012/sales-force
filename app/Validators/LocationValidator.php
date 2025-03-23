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

        return Validator::make($data, $rules);
    }
} 