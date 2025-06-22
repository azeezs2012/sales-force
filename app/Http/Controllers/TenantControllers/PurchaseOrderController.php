<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\TenantModels\PurchaseOrder;
use App\Models\TenantModels\PurchaseOrderDetail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Validators\PurchaseOrderValidator;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $purchaseOrders = PurchaseOrder::with('supplier', 'location', 'creator')->latest()->get();
        return response()->json($purchaseOrders);
    }

    public function store(Request $request)
    {
        $validator = PurchaseOrderValidator::validate($request->all());
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $validatedData = $validator->validated();

        $purchaseOrder = DB::transaction(function () use ($validatedData, $request) {
            $totalAmount = collect($validatedData['details'])->sum(function ($detail) {
                return ($detail['quantity'] * $detail['cost']);
            });

            $po = PurchaseOrder::create([
                'po_date' => $validatedData['po_date'],
                'supplier_id' => $validatedData['supplier_id'],
                'location_id' => $validatedData['location_id'],
                'po_billing_address' => $validatedData['po_billing_address'],
                'po_delivery_address' => $validatedData['po_delivery_address'],
                'po_status' => $validatedData['po_status'],
                'total_amount' => $totalAmount,
                'created_by' => auth()->id(),
            ]);

            foreach ($validatedData['details'] as $detail) {
                $po->details()->create([
                    'product_id' => $detail['product_id'],
                    'quantity' => $detail['quantity'],
                    'cost' => $detail['cost'],
                    'total' => $detail['quantity'] * $detail['cost'],
                    'location_id' => $detail['location_id'] ?? $po->location_id,
                    'description' => $detail['description'] ?? null,
                ]);
            }

            return $po;
        });

        return response()->json($purchaseOrder->load('details.product', 'supplier', 'location'), 201);
    }

    public function show($id)
    {
        $purchaseOrder = PurchaseOrder::with('details.product', 'details.location', 'supplier', 'location', 'creator', 'updater')->findOrFail($id);
        return response()->json($purchaseOrder);
    }

    public function update(Request $request, $id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        $validator = PurchaseOrderValidator::validate($request->all());
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        
        $validatedData = $validator->validated();

        $updatedPurchaseOrder = DB::transaction(function () use ($purchaseOrder, $validatedData, $request) {
            $totalAmount = collect($validatedData['details'])->sum(function ($detail) {
                return ($detail['quantity'] * $detail['cost']);
            });

            $purchaseOrder->update([
                'po_date' => $validatedData['po_date'],
                'supplier_id' => $validatedData['supplier_id'],
                'location_id' => $validatedData['location_id'],
                'po_billing_address' => $validatedData['po_billing_address'],
                'po_delivery_address' => $validatedData['po_delivery_address'],
                'po_status' => $validatedData['po_status'],
                'total_amount' => $totalAmount,
                'updated_by' => auth()->id(),
            ]);

            // Simple sync: delete old details and create new ones
            $purchaseOrder->details()->delete();

            foreach ($validatedData['details'] as $detail) {
                $purchaseOrder->details()->create([
                    'product_id' => $detail['product_id'],
                    'quantity' => $detail['quantity'],
                    'cost' => $detail['cost'],
                    'total' => $detail['quantity'] * $detail['cost'],
                    'location_id' => $detail['location_id'] ?? $purchaseOrder->location_id,
                    'description' => $detail['description'] ?? null,
                ]);
            }

            return $purchaseOrder;
        });

        return response()->json($updatedPurchaseOrder->load('details.product', 'supplier', 'location'));
    }

    public function destroy($id)
    {
        $purchaseOrder = PurchaseOrder::findOrFail($id);

        DB::transaction(function () use ($purchaseOrder) {
            // Details are deleted automatically due to cascade on delete constraint
            $purchaseOrder->delete();
        });

        return response()->json(null, 204);
    }
} 