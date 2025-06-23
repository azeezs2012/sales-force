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
                'po_status' => $validatedData['po_status'],
                'total_amount' => $totalAmount,
                'updated_by' => auth()->id(),
            ]);

            $incomingDetailIds = collect($validatedData['details'])->pluck('id')->filter()->all();
            $detailsToDeleteIds = collect($purchaseOrder->details)->pluck('id')->diff($incomingDetailIds);

            if ($detailsToDeleteIds->isNotEmpty()) {
                // Future-proofing: Here you could add a check to prevent deletion
                // if a line is associated with a GRN.
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
                    $purchaseOrder->details()->where('id', $detailData['id'])->update($payload);
                } else {
                    $purchaseOrder->details()->create($payload);
                }
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
} 