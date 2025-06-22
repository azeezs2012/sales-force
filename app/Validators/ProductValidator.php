<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductValidator
{
    /**
     * Validate the product data.
     *
     * @param  array  $data
     * @param  int|null  $productId
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validate(array $data, $productId = null)
    {
        $rules = [
            'product_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')->where(function ($query) use ($data) {
                    return $query->where('inventory_type', $data['inventory_type'] ?? null);
                })->ignore($productId),
            ],
            'product_description' => 'nullable|string',
            'inventory_type' => 'required|in:Service,Inventory',
            'parent_id' => 'nullable|exists:products,id',
            'product_type_id' => 'nullable|exists:product_types,id',
            'product_category_id' => 'nullable|exists:product_categories,id',
            'cost' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'sales_account_id' => 'required|exists:accounts,id',
            'expense_account_id' => 'required|exists:accounts,id',
            'inventory_account_id' => [
                'nullable',
                'exists:accounts,id',
                Rule::requiredIf(($data['inventory_type'] ?? null) === 'Inventory'),
            ],
        ];

        $validator = Validator::make($data, $rules, [
            'inventory_account_id.required' => 'The inventory account is required when the inventory type is "Inventory".',
        ]);

        return $validator;
    }
} 