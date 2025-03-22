<?php

namespace App\Validators;

use Illuminate\Http\Request;

class UserValidator
{
    public static function validate(Request $request, $id = null)
    {
        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:8',
        ];

        if ($id === null) {
            $rules['name'] = 'required|string|max:255';
            $rules['email'] = 'required|string|email|max:255|unique:users';
            $rules['password'] = 'required|string|min:8';
        }

        return $request->validate($rules);
    }
} 