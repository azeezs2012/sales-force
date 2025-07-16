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
        $purchaseOrders = PurchaseOrder::with('supplier.user', 'location', 'creator')->latest()->get();
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
                'po_status' => 'Open', // Always start as Open
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
        
        // Add received_quantity to each detail for display
        $purchaseOrder->details->each(function ($detail) {
            $detail->received_quantity = $detail->received_quantity ?? 0;
            $detail->remaining_quantity = $detail->quantity - $detail->received_quantity;
        });
        
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

        // Validate that received lines cannot be modified
        $validationResult = $this->validateReceivedLines($purchaseOrder, $validatedData);
        if (!$validationResult['valid']) {
            return response()->json(['message' => $validationResult['message']], 422);
        }

        $updatedPurchaseOrder = DB::transaction(function () use ($purchaseOrder, $validatedData) {
            $totalAmount = collect($validatedData['details'])->sum(function ($detail) {
                return ($detail['quantity'] * $detail['cost']);
            });

            $purchaseOrder->update([
                'po_date' => $validatedData['po_date'],
                'supplier_id' => $validatedData['supplier_id'],
                'location_id' => $validatedData['location_id'],
                'po_billing_address' => $validatedData['po_billing_address'],
                'po_delivery_address' => $validatedData['po_delivery_address'],
                // po_status is managed by controller, not user-editable
                'total_amount' => $totalAmount,
                'updated_by' => auth()->id(),
            ]);

            $incomingDetailIds = collect($validatedData['details'])->pluck('id')->filter()->all();
            $detailsToDeleteIds = collect($purchaseOrder->details)->pluck('id')->diff($incomingDetailIds);

            if ($detailsToDeleteIds->isNotEmpty()) {
                // Check if any lines to be deleted have received quantities
                $linesWithReceipts = $purchaseOrder->details()
                    ->whereIn('id', $detailsToDeleteIds)
                    ->where('received_quantity', '>', 0)
                    ->get();

                if ($linesWithReceipts->isNotEmpty()) {
                    $lineNumbers = $linesWithReceipts->pluck('id')->join(', ');
                    throw new \Exception("Cannot delete lines with received quantities. Lines: {$lineNumbers}");
                }

                $purchaseOrder->details()->whereIn('id', $detailsToDeleteIds)->delete();
            }

            foreach ($validatedData['details'] as $detailData) {
                $payload = [
                    'product_id' => $detailData['product_id'],
                    'quantity' => $detailData['quantity'],
                    'cost' => $detailData['cost'],
                    'total' => $detailData['quantity'] * $detailData['cost'],
                    'location_id' => $detailData['location_id'] ?? $purchaseOrder->location_id,
                    'description' => $detailData['description'] ?? null,
                ];

                if (isset($detailData['id'])) {
                    // Check if quantity is being reduced below received amount
                    $existingDetail = $purchaseOrder->details()->find($detailData['id']);
                    if ($existingDetail && $existingDetail->received_quantity > 0) {
                        if ($detailData['quantity'] < $existingDetail->received_quantity) {
                            throw new \Exception("Cannot reduce quantity below received amount ({$existingDetail->received_quantity}) for line {$detailData['id']}");
                        }
                    }
                    
                    $purchaseOrder->details()->where('id', $detailData['id'])->update($payload);
                } else {
                    $purchaseOrder->details()->create($payload);
                }
            }

            return $purchaseOrder;
        });

        $updatedPurchaseOrder = $updatedPurchaseOrder->load('details.product', 'supplier', 'location');
        
        // Add received_quantity to each detail for display
        $updatedPurchaseOrder->details->each(function ($detail) {
            $detail->received_quantity = $detail->received_quantity ?? 0;
            $detail->remaining_quantity = $detail->quantity - $detail->received_quantity;
        });
        
        return response()->json($updatedPurchaseOrder);
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

    public function getPoDetailsForGrn(Request $request)
    {
        $poIds = explode(',', $request->query('po_ids'));

        if (empty($poIds) || empty($poIds[0])) {
            return response()->json(['error' => 'No Purchase Order IDs provided.'], 400);
        }

        $suppliers = PurchaseOrder::whereIn('id', $poIds)->distinct('supplier_id')->pluck('supplier_id');
        if ($suppliers->count() > 1) {
            return response()->json(['error' => 'All selected Purchase Orders must belong to the same supplier.'], 400);
        }

        $supplierId = $suppliers->first();

        $details = PurchaseOrderDetail::with('product')
            ->whereIn('purchase_order_id', $poIds)
            ->whereRaw('quantity > received_quantity')
            ->get()
            ->map(function ($detail) {
                $arr = $detail->toArray();
                $arr['ordered_quantity'] = $detail->quantity;
                return $arr;
            });

        return response()->json([
            'supplier_id' => $supplierId,
            'details' => $details
        ]);
    }

    /**
     * Validate that received lines cannot be modified inappropriately
     */
    private function validateReceivedLines($purchaseOrder, $validatedData)
    {
        $incomingDetailIds = collect($validatedData['details'])->pluck('id')->filter()->all();
        $existingDetails = $purchaseOrder->details()->whereIn('id', $incomingDetailIds)->get()->keyBy('id');

        foreach ($validatedData['details'] as $detailData) {
            if (isset($detailData['id']) && isset($existingDetails[$detailData['id']])) {
                $existingDetail = $existingDetails[$detailData['id']];
                
                // Check if quantity is being reduced below received amount
                if ($existingDetail->received_quantity > 0 && $detailData['quantity'] < $existingDetail->received_quantity) {
                    return [
                        'valid' => false,
                        'message' => "Cannot reduce quantity below received amount ({$existingDetail->received_quantity}) for line {$detailData['id']}"
                    ];
                }

                // Check if product is being changed on a received line
                if ($existingDetail->received_quantity > 0 && $detailData['product_id'] != $existingDetail->product_id) {
                    return [
                        'valid' => false,
                        'message' => "Cannot change product on line {$detailData['id']} as it has received quantities"
                    ];
                }

                // Check if location is being changed on a received line
                if ($existingDetail->received_quantity > 0 && $detailData['location_id'] != $existingDetail->location_id) {
                    return [
                        'valid' => false,
                        'message' => "Cannot change location on line {$detailData['id']} as it has received quantities"
                    ];
                }
            }
        }

        return ['valid' => true];
    }
} 