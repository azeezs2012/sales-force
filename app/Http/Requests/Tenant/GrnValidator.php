<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class GrnValidator extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'grn_date' => 'required|date',
            'supplier_id' => 'required|exists:suppliers,id',
            'location_id' => 'required|exists:locations,id',
            'ap_account_id' => 'required|exists:accounts,id',
            'grn_billing_address' => 'nullable|string',
            'grn_delivery_address' => 'nullable|string',
            'grn_status' => 'required|string|in:draft,posted',
            'details' => 'required|array|min:1',
            'details.*.id' => 'nullable|integer',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.location_id' => 'required|exists:locations,id',
            'details.*.quantity' => 'required|numeric|min:0.01',
            'details.*.cost' => 'required|numeric|min:0',
            'details.*.purchase_order_detail_id' => 'nullable|exists:purchase_order_details,id',
        ];
    }
}
