<?php

namespace App\Http\Controllers\TenantControllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\GrnCreditValidator;
use App\Models\TenantModels\GrnCreditSummary;
use App\Models\TenantModels\GrnSummary;
use App\Models\TenantModels\GrnDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrnCreditController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $grnCredits = GrnCreditSummary::with('supplier.user', 'location', 'account')->latest()->get();
        return response()->json($grnCredits);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(GrnCreditValidator $request)
    {
        $validated = $request->validated();

        // Validate that GRN Credit quantities don't exceed available GRN quantities
        $validationResult = $this->validateGrnCreditQuantities($validated['details']);
        if (!$validationResult['valid']) {
            return response()->json(['message' => $validationResult['message']], 422);
        }

        DB::beginTransaction();
        try {
            $totalAmount = collect($validated['details'])->sum(function ($detail) {
                return $detail['quantity'] * $detail['cost'];
            });

            $grnCreditSummary = GrnCreditSummary::create([
                'grn_credit_date' => $validated['grn_credit_date'],
                'supplier_id' => $validated['supplier_id'],
                'location_id' => $validated['location_id'],
                'ap_account_id' => $validated['ap_account_id'],
                'grn_credit_billing_address' => $validated['grn_credit_billing_address'] ?? null,
                'grn_credit_delivery_address' => $validated['grn_credit_delivery_address'] ?? null,
                'grn_credit_status' => $validated['grn_credit_status'] ?? 'draft',
                'total_amount' => $totalAmount,
                'credit_reason' => $validated['credit_reason'] ?? null,
            ]);

            foreach ($validated['details'] as $detail) {
                $grnCreditDetail = $grnCreditSummary->details()->create([
                    'product_id' => $detail['product_id'],
                    'location_id' => $detail['location_id'],
                    'quantity' => $detail['quantity'],
                    'cost' => $detail['cost'],
                    'total' => $detail['quantity'] * $detail['cost'],
                    'grn_detail_id' => $detail['grn_detail_id'] ?? null,
                ]);
            }

            // After all details are processed, check and update GRN statuses
            $grnIds = collect($validated['details'])
                ->whereNotNull('grn_detail_id')
                ->map(function ($detail) {
                    return GrnDetail::find($detail['grn_detail_id'])->grn_summary_id;
                })->unique()->toArray();

            foreach($grnIds as $grnId) {
                $this->checkAndUpdateGrnStatus($grnId);
            }

            DB::commit();

            return response()->json($grnCreditSummary->load('details'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create GRN Credit.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(GrnCreditSummary $grnCredit)
    {
        $grnCredit = $grnCredit->load('details.product', 'details.location', 'supplier.user', 'location', 'account');
        
        // Add GRN line information to each detail for display
        $grnCredit->details->each(function ($detail) {
            if ($detail->grn_detail_id) {
                $grnDetail = GrnDetail::find($detail->grn_detail_id);
                if ($grnDetail) {
                    $detail->original_grn_quantity = $grnDetail->quantity;
                }
            }
        });
        
        return $grnCredit;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(GrnCreditValidator $request, GrnCreditSummary $grnCredit)
    {
        $validated = $request->validated();

        // Validate that GRN Credit quantities don't exceed available GRN quantities
        $validationResult = $this->validateGrnCreditQuantities($validated['details'], $grnCredit);
        if (!$validationResult['valid']) {
            return response()->json(['message' => $validationResult['message']], 422);
        }

        DB::beginTransaction();
        try {
            $initialGrnIds = $grnCredit->details()
                ->whereNotNull('grn_detail_id')
                ->with('grnDetail')
                ->get()
                ->map(fn($detail) => $detail->grnDetail->grn_summary_id)
                ->unique();

            $totalAmount = collect($validated['details'])->sum(function ($detail) {
                return $detail['quantity'] * $detail['cost'];
            });

            $grnCredit->update([
                'grn_credit_date' => $validated['grn_credit_date'],
                'supplier_id' => $validated['supplier_id'],
                'location_id' => $validated['location_id'],
                'ap_account_id' => $validated['ap_account_id'],
                'grn_credit_billing_address' => $validated['grn_credit_billing_address'] ?? null,
                'grn_credit_delivery_address' => $validated['grn_credit_delivery_address'] ?? null,
                'grn_credit_status' => $validated['grn_credit_status'] ?? 'draft',
                'total_amount' => $totalAmount,
                'credit_reason' => $validated['credit_reason'] ?? null,
            ]);

            $existingDetailIds = $grnCredit->details->pluck('id')->toArray();
            $incomingDetailIds = collect($validated['details'])->pluck('id')->filter()->toArray();
            $idsToDelete = array_diff($existingDetailIds, $incomingDetailIds);

            if (!empty($idsToDelete)) {
                $grnCredit->details()->whereIn('id', $idsToDelete)->delete();
            }

            foreach ($validated['details'] as $detail) {
                $grnCredit->details()->updateOrCreate(
                    ['id' => $detail['id'] ?? null],
                    [
                        'product_id' => $detail['product_id'],
                        'location_id' => $detail['location_id'],
                        'quantity' => $detail['quantity'],
                        'cost' => $detail['cost'],
                        'total' => $detail['quantity'] * $detail['cost'],
                        'grn_detail_id' => $detail['grn_detail_id'] ?? null,
                    ]
                );
            }
            
            $finalGrnIds = collect($validated['details'])
                ->whereNotNull('grn_detail_id')
                ->map(fn($detail) => GrnDetail::find($detail['grn_detail_id'])->grn_summary_id)
                ->unique();

            $allGrnIdsToUpdate = $initialGrnIds->merge($finalGrnIds)->unique();

            foreach($allGrnIdsToUpdate as $grnId) {
                $this->checkAndUpdateGrnStatus($grnId);
            }

            DB::commit();

            return response()->json($grnCredit->load('details'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update GRN Credit.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GrnCreditSummary $grnCredit)
    {
        DB::beginTransaction();
        try {
            $grnIdsToUpdate = [];
            foreach ($grnCredit->details as $detail) {
                if ($detail->grn_detail_id) {
                    $grnIdsToUpdate[] = $detail->grnDetail->grn_summary_id;
                }
            }

            \Log::info("Deleting GRN Credit - ID: {$grnCredit->id}, GRN IDs to update: " . implode(', ', array_unique($grnIdsToUpdate)));

            $grnCredit->delete(); // This will cascade delete details

            DB::commit();

            // After committing the deletion, check and update GRN status
            foreach (array_unique($grnIdsToUpdate) as $grnId) {
                $this->checkAndUpdateGrnStatus($grnId);
            }
            
            return response()->json(['message' => 'GRN Credit deleted successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete GRN Credit.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get GRN details for creating GRN Credit
     */
    public function getGrnDetailsForCredit(Request $request)
    {
        $grnIds = explode(',', $request->query('grn_ids'));

        if (empty($grnIds) || empty($grnIds[0])) {
            return response()->json(['error' => 'No GRN IDs provided.'], 400);
        }

        $suppliers = GrnSummary::whereIn('id', $grnIds)->distinct('supplier_id')->pluck('supplier_id');
        if ($suppliers->count() > 1) {
            return response()->json(['error' => 'All selected GRNs must belong to the same supplier.'], 400);
        }

        $supplierId = $suppliers->first();

        $details = GrnDetail::with('product')
            ->whereIn('grn_summary_id', $grnIds)
            ->whereRaw('quantity > COALESCE((SELECT SUM(quantity) FROM grn_credit_details WHERE grn_detail_id = grn_details.id), 0)')
            ->get()
            ->map(function ($detail) {
                $arr = $detail->toArray();
                $arr['original_grn_quantity'] = $detail->quantity;
                $arr['credited_quantity'] = $detail->grnCreditDetails()->sum('quantity');
                $arr['available_quantity'] = $detail->quantity - $detail->grnCreditDetails()->sum('quantity');
                return $arr;
            });

        return response()->json([
            'supplier_id' => $supplierId,
            'details' => $details
        ]);
    }

    private function checkAndUpdateGrnStatus($grnId)
    {
        // Force a fresh query to get the latest state after deletion
        $grn = GrnSummary::with('details')->find($grnId);
        if ($grn) {
            $details = $grn->details;
            
            // Calculate total credited amount for this GRN using a direct query
            $totalCreditedAmount = \DB::table('grn_credit_details')
                ->join('grn_details', 'grn_credit_details.grn_detail_id', '=', 'grn_details.id')
                ->where('grn_details.grn_summary_id', $grnId)
                ->sum('grn_credit_details.total');
            
            // Log the values for debugging
            \Log::info("GRN Status Update - GRN ID: {$grnId}, Current Status: {$grn->grn_status}, Total Amount: {$grn->total_amount}, Credited Amount: {$totalCreditedAmount}");
            
            // Determine new status based on credited amount vs total amount
            $newStatus = 'Open';
            if ($totalCreditedAmount > 0 && $totalCreditedAmount >= $grn->total_amount) {
                $newStatus = 'Paid';
            } else {
                $newStatus = 'Open';
            }
            
            // Update status if it has changed
            if ($grn->grn_status !== $newStatus) {
                $grn->update(['grn_status' => $newStatus]);
                \Log::info("GRN Status Updated - GRN ID: {$grnId}, Old Status: {$grn->grn_status}, New Status: {$newStatus}");
            } else {
                \Log::info("GRN Status Unchanged - GRN ID: {$grnId}, Status: {$grn->grn_status}");
            }
        }
    }

    /**
     * Validate that GRN Credit quantities don't exceed available GRN quantities
     */
    private function validateGrnCreditQuantities($details, $grnCredit = null)
    {
        foreach ($details as $detail) {
            if (!empty($detail['grn_detail_id'])) {
                $grnDetail = GrnDetail::find($detail['grn_detail_id']);
                
                if (!$grnDetail) {
                    return [
                        'valid' => false,
                        'message' => "GRN detail not found for line {$detail['grn_detail_id']}"
                    ];
                }

                // Calculate current total credited quantity for this GRN detail
                $currentCreditedQuantity = $grnDetail->grnCreditDetails()->sum('quantity');
                
                // If this is an update, subtract the quantity of the current GRN Credit detail being updated
                if ($grnCredit && isset($detail['id'])) {
                    $existingGrnCreditDetail = $grnCredit->details()->find($detail['id']);
                    if ($existingGrnCreditDetail) {
                        $currentCreditedQuantity -= $existingGrnCreditDetail->quantity;
                    }
                }

                // Calculate what the total credited quantity would be after this GRN Credit
                $newCreditedQuantity = $currentCreditedQuantity + $detail['quantity'];
                
                // Check if this would exceed the original GRN quantity
                if ($newCreditedQuantity > $grnDetail->quantity) {
                    $remainingQuantity = $grnDetail->quantity - $currentCreditedQuantity;
                    return [
                        'valid' => false,
                        'message' => "Cannot credit {$detail['quantity']} units. Only {$remainingQuantity} units available for GRN line {$detail['grn_detail_id']} (original quantity: {$grnDetail->quantity})"
                    ];
                }
            }
        }

        return ['valid' => true];
    }
}
