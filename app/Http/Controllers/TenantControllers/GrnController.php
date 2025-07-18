<?php

namespace App\Http\Controllers\TenantControllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\GrnValidator;
use App\Models\TenantModels\GrnSummary;
use App\Models\TenantModels\PurchaseOrder;
use App\Models\TenantModels\PurchaseOrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrnController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $grns = GrnSummary::with('supplier.user', 'location', 'account')->latest()->get();
        return response()->json($grns);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GrnValidator $request)
    {
        $validated = $request->validated();

        // Validate that GRN quantities don't exceed PO line quantities
        $validationResult = $this->validateGrnQuantities($validated['details']);
        if (!$validationResult['valid']) {
            return response()->json(['message' => $validationResult['message']], 422);
        }

        DB::beginTransaction();
        try {
            $totalAmount = collect($validated['details'])->sum(function ($detail) {
                return $detail['quantity'] * $detail['cost'];
            });

            $grnSummary = GrnSummary::create([
                'grn_date' => $validated['grn_date'],
                'supplier_id' => $validated['supplier_id'],
                'location_id' => $validated['location_id'],
                'ap_account_id' => $validated['ap_account_id'],
                'grn_billing_address' => $validated['grn_billing_address'] ?? null,
                'grn_delivery_address' => $validated['grn_delivery_address'] ?? null,
                'grn_status' => $validated['grn_status'] ?? 'draft',
                'total_amount' => $totalAmount,
            ]);

            foreach ($validated['details'] as $detail) {
                $grnDetail = $grnSummary->details()->create([
                    'product_id' => $detail['product_id'],
                    'location_id' => $detail['location_id'],
                    'quantity' => $detail['quantity'],
                    'cost' => $detail['cost'],
                    'total' => $detail['quantity'] * $detail['cost'],
                    'purchase_order_detail_id' => $detail['purchase_order_detail_id'] ?? null,
                ]);

                if ($grnDetail->purchase_order_detail_id) {
                    $this->updatePoDetailReceivedQuantity($grnDetail->purchase_order_detail_id);
                }
            }

            // After all details are processed, check and update PO statuses
            $poIds = collect($validated['details'])
                ->whereNotNull('purchase_order_detail_id')
                ->map(function ($detail) {
                    return PurchaseOrderDetail::find($detail['purchase_order_detail_id'])->purchase_order_id;
                })->unique()->toArray();

            foreach($poIds as $poId) {
                $this->checkAndUpdatePoStatus($poId);
            }

            DB::commit();

            return response()->json($grnSummary->load('details'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create GRN.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(GrnSummary $grn)
    {
        $grn = $grn->load('details.product', 'details.location', 'supplier.user', 'location', 'account');
        
        // Add PO line information to each detail for display
        $grn->details->each(function ($detail) {
            if ($detail->purchase_order_detail_id) {
                $poDetail = \App\Models\TenantModels\PurchaseOrderDetail::find($detail->purchase_order_detail_id);
                if ($poDetail) {
                    $detail->ordered_quantity = $poDetail->quantity;
                }
            }
        });
        
        return $grn;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(GrnValidator $request, GrnSummary $grn)
    {
        $validated = $request->validated();

        // Validate that GRN quantities don't exceed PO line quantities
        $validationResult = $this->validateGrnQuantities($validated['details'], $grn);
        if (!$validationResult['valid']) {
            return response()->json(['message' => $validationResult['message']], 422);
        }

        DB::beginTransaction();
        try {
            $initialPoIds = $grn->details()
                ->whereNotNull('purchase_order_detail_id')
                ->with('purchaseOrderDetail')
                ->get()
                ->map(fn($detail) => $detail->purchaseOrderDetail->purchase_order_id)
                ->unique();

            $totalAmount = collect($validated['details'])->sum(function ($detail) {
                return $detail['quantity'] * $detail['cost'];
            });

            $grn->update([
                'grn_date' => $validated['grn_date'],
                'supplier_id' => $validated['supplier_id'],
                'location_id' => $validated['location_id'],
                'ap_account_id' => $validated['ap_account_id'],
                'grn_billing_address' => $validated['grn_billing_address'] ?? null,
                'grn_delivery_address' => $validated['grn_delivery_address'] ?? null,
                'grn_status' => $validated['grn_status'] ?? 'draft',
                'total_amount' => $totalAmount,
            ]);

            $existingDetailIds = $grn->details->pluck('id')->toArray();
            $incomingDetailIds = collect($validated['details'])->pluck('id')->filter()->toArray();
            $idsToDelete = array_diff($existingDetailIds, $incomingDetailIds);

            if (!empty($idsToDelete)) {
                $grn->details()->whereIn('id', $idsToDelete)->delete();
            }

            foreach ($validated['details'] as $detail) {
                $grn->details()->updateOrCreate(
                    ['id' => $detail['id'] ?? null],
                    [
                        'product_id' => $detail['product_id'],
                        'location_id' => $detail['location_id'],
                        'quantity' => $detail['quantity'],
                        'cost' => $detail['cost'],
                        'total' => $detail['quantity'] * $detail['cost'],
                        'purchase_order_detail_id' => $detail['purchase_order_detail_id'] ?? null,
                    ]
                );
            }
            
            $finalPoIds = collect($validated['details'])
                ->whereNotNull('purchase_order_detail_id')
                ->map(fn($detail) => PurchaseOrderDetail::find($detail['purchase_order_detail_id'])->purchase_order_id)
                ->unique();

            $allPoIdsToUpdate = $initialPoIds->merge($finalPoIds)->unique();

            foreach($allPoIdsToUpdate as $poId) {
                // We need to update all PO details associated with the PO
                $po = PurchaseOrder::with('details')->find($poId);
                if($po) {
                    foreach($po->details as $poDetail) {
                        $this->updatePoDetailReceivedQuantity($poDetail->id);
                    }
                    $this->checkAndUpdatePoStatus($poId);
                }
            }

            DB::commit();

            return response()->json($grn->load('details'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update GRN.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GrnSummary $grn)
    {
        // We need to reverse the received quantity updates before deleting
        DB::beginTransaction();
        try {
            $poIdsToUpdate = [];
            foreach ($grn->details as $detail) {
                if ($detail->purchase_order_detail_id) {
                    $poIdsToUpdate[] = $detail->purchaseOrderDetail->purchase_order_id;
                }
            }

            $grn->delete(); // This will cascade delete details

            // After deleting, recalculate received quantities and update PO status
            foreach (array_unique($poIdsToUpdate) as $poId) {
                $po = PurchaseOrder::with('details')->find($poId);
                if ($po) {
                    // Update all PO details for this PO
                    foreach ($po->details as $poDetail) {
                        $this->updatePoDetailReceivedQuantity($poDetail->id);
                    }
                    // Check and update PO status
                    $this->checkAndUpdatePoStatus($poId);
                }
            }
            DB::commit();
            return response()->json(['message' => 'GRN deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete GRN.', 'error' => $e->getMessage()], 500);
        }
    }

    private function updatePoDetailReceivedQuantity($poDetailId)
    {
        $poDetail = PurchaseOrderDetail::find($poDetailId);
        if ($poDetail) {
            $totalReceived = $poDetail->grnDetails()->sum('quantity');
            // Ensure received quantity doesn't exceed ordered quantity
            $totalReceived = min($totalReceived, $poDetail->quantity);
            $poDetail->update(['received_quantity' => $totalReceived]);
        }
    }

    private function checkAndUpdatePoStatus($poId)
    {
        $po = PurchaseOrder::with('details')->find($poId);
        if ($po) {
            $details = $po->details;
            
            // Check if all lines are fully received
            $allFullyReceived = $details->every(function ($detail) {
                return $detail->quantity == $detail->received_quantity;
            });
            
            // Check if any lines have partial receipts
            $hasPartialReceipts = $details->some(function ($detail) {
                return $detail->received_quantity > 0 && $detail->received_quantity < $detail->quantity;
            });
            
            // Check if no lines have any receipts
            $noReceipts = $details->every(function ($detail) {
                return $detail->received_quantity == 0;
            });
            
            // Determine new status
            $newStatus = 'Open';
            if ($allFullyReceived) {
                $newStatus = 'Closed';
            } elseif ($hasPartialReceipts) {
                $newStatus = 'Partial';
            }
            
            // Update status if it has changed
            if ($po->po_status !== $newStatus) {
                $po->update(['po_status' => $newStatus]);
            }
        }
    }

    /**
     * Validate that GRN quantities don't exceed PO line quantities
     */
    private function validateGrnQuantities($details, $grn = null)
    {
        foreach ($details as $detail) {
            if (!empty($detail['purchase_order_detail_id'])) {
                $poDetail = PurchaseOrderDetail::find($detail['purchase_order_detail_id']);
                
                if (!$poDetail) {
                    return [
                        'valid' => false,
                        'message' => "Purchase order detail not found for line {$detail['purchase_order_detail_id']}"
                    ];
                }

                // Calculate current total received quantity for this PO detail
                $currentReceivedQuantity = $poDetail->grnDetails()->sum('quantity');
                
                // If this is an update, subtract the quantity of the current GRN detail being updated
                if ($grn && isset($detail['id'])) {
                    $existingGrnDetail = $grn->details()->find($detail['id']);
                    if ($existingGrnDetail) {
                        $currentReceivedQuantity -= $existingGrnDetail->quantity;
                    }
                }

                // Calculate what the total received quantity would be after this GRN
                $newReceivedQuantity = $currentReceivedQuantity + $detail['quantity'];
                
                // Check if this would exceed the original PO quantity
                if ($newReceivedQuantity > $poDetail->quantity) {
                    $remainingQuantity = $poDetail->quantity - $currentReceivedQuantity;
                    return [
                        'valid' => false,
                        'message' => "Cannot receive {$detail['quantity']} units. Only {$remainingQuantity} units remaining for PO line {$detail['purchase_order_detail_id']} (original quantity: {$poDetail->quantity})"
                    ];
                }
            }
        }

        return ['valid' => true];
    }
}
