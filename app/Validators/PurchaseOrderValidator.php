<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PurchaseOrderValidator
{
    /**
     * Validate the purchase order data.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public static function validate(array $data)
    {
        $rules = [
            'po_date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'location_id' => 'required|exists:locations,id',
            'po_billing_address' => 'required|string',
            'po_delivery_address' => 'required|string',
            'po_status' => 'required|string|in:Draft,Submitted,Approved,Completed',
            
            'details' => 'required|array|min:1',
            'details.*.id' => 'nullable|exists:purchase_order_details,id',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.quantity' => 'required|numeric|min:1',
            'details.*.cost' => 'required|numeric|min:0',
            'details.*.location_id' => 'nullable|exists:locations,id',
        ];

        $messages = [
            'details.required' => 'At least one product must be added to the purchase order.',
            'details.array' => 'The details must be an array.',
            'details.min' => 'At least one product must be added to the purchase order.',
            'details.*.id.exists' => 'An invalid line item was provided.',
            'details.*.product_id.required' => 'A product must be selected for each line item.',
            'details.*.product_id.exists' => 'The selected product is invalid.',
            'details.*.quantity.required' => 'Quantity is required for each line item.',
            'details.*.quantity.min' => 'Quantity must be at least 1 for each line item.',
            'details.*.cost.required' => 'Cost is required for each line item.',
        ];

        return Validator::make($data, $rules, $messages);
    }
} 