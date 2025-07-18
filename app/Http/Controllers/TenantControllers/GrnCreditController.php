<?php

namespace App\Http\Controllers\TenantControllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\GrnCreditValidator;
use App\Models\TenantModels\GrnCreditSummary;
use App\Models\TenantModels\GrnSummary;
use App\Models\TenantModels\GrnDetail;
use App\Models\TenantModels\GrnSettlement;
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
                'grn_credit_status' => 'Open', // Always start as Open
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

            // Create settlements for GRN credits that have GRN detail relationships
            $grnIds = collect($validated['details'])
                ->whereNotNull('grn_detail_id')
                ->map(function ($detail) {
                    return GrnDetail::find($detail['grn_detail_id'])->grn_summary_id;
                })->unique();

            foreach ($grnIds as $grnId) {
                $grnSummary = GrnSummary::find($grnId);
                if ($grnSummary) {
                    // Calculate total GRN credit amount for this GRN
                    $grnCreditAmount = collect($validated['details'])
                        ->whereNotNull('grn_detail_id')
                        ->filter(function ($detail) use ($grnId) {
                            $grnDetail = GrnDetail::find($detail['grn_detail_id']);
                            return $grnDetail && $grnDetail->grn_summary_id == $grnId;
                        })
                        ->sum(function ($detail) {
                            return $detail['quantity'] * $detail['cost'];
                        });

                    // Create settlement record
                    GrnSettlement::create([
                        'grn_summary_id' => $grnId,
                        'settlement_type' => 'grn_credit',
                        'settlement_reference_id' => $grnCreditSummary->id,
                        'settlement_reference_type' => 'grn_credit_summaries',
                        'settlement_amount' => $grnCreditAmount,
                        'settlement_date' => $validated['grn_credit_date'],
                        'settlement_notes' => "GRN Credit: {$grnCreditSummary->id}",
                        'created_by' => auth()->id(),
                    ]);

                    // Update GRN settlement totals
                    $grnSummary->updateSettlementTotals();
                }
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
                'total_amount' => $totalAmount,
                'credit_reason' => $validated['credit_reason'] ?? null,
            ]);

            // Update status automatically based on settlements
            $grnCredit->updateSettlementTotals();

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

            // Delete existing settlements for this GRN credit
            GrnSettlement::where('settlement_reference_type', 'grn_credit_summaries')
                ->where('settlement_reference_id', $grnCredit->id)
                ->delete();

            // Create new settlements for GRN credits that have GRN detail relationships
            $grnIds = collect($validated['details'])
                ->whereNotNull('grn_detail_id')
                ->map(function ($detail) {
                    return GrnDetail::find($detail['grn_detail_id'])->grn_summary_id;
                })->unique();

            foreach ($grnIds as $grnId) {
                $grnSummary = GrnSummary::find($grnId);
                if ($grnSummary) {
                    // Calculate total GRN credit amount for this GRN
                    $grnCreditAmount = collect($validated['details'])
                        ->whereNotNull('grn_detail_id')
                        ->filter(function ($detail) use ($grnId) {
                            $grnDetail = GrnDetail::find($detail['grn_detail_id']);
                            return $grnDetail && $grnDetail->grn_summary_id == $grnId;
                        })
                        ->sum(function ($detail) {
                            return $detail['quantity'] * $detail['cost'];
                        });

                    // Create settlement record
                    GrnSettlement::create([
                        'grn_summary_id' => $grnId,
                        'settlement_type' => 'grn_credit',
                        'settlement_reference_id' => $grnCredit->id,
                        'settlement_reference_type' => 'grn_credit_summaries',
                        'settlement_amount' => $grnCreditAmount,
                        'settlement_date' => $validated['grn_credit_date'],
                        'settlement_notes' => "GRN Credit: {$grnCredit->id}",
                        'created_by' => auth()->id(),
                    ]);

                    // Update GRN settlement totals
                    $grnSummary->updateSettlementTotals();
                }
            }

            // Update settlement totals for GRNs that were affected but no longer have credits
            $allAffectedGrnIds = $initialGrnIds->merge($grnIds)->unique();
            foreach ($allAffectedGrnIds as $grnId) {
                $grnSummary = GrnSummary::find($grnId);
                if ($grnSummary) {
                    $grnSummary->updateSettlementTotals();
                }
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
            // Get GRN IDs that will be affected by this deletion
            $affectedGrnIds = $grnCredit->details()
                ->whereNotNull('grn_detail_id')
                ->with('grnDetail')
                ->get()
                ->map(fn($detail) => $detail->grnDetail->grn_summary_id)
                ->unique();

            // Delete settlements for this GRN credit
            GrnSettlement::where('settlement_reference_type', 'grn_credit_summaries')
                ->where('settlement_reference_id', $grnCredit->id)
                ->delete();

            // Delete GRN credit settlements
            GrnCreditSettlement::where('grn_credit_summary_id', $grnCredit->id)
                ->delete();

            $grnCredit->delete(); // This will cascade delete details

            // Update settlement totals for affected GRNs
            foreach ($affectedGrnIds as $grnId) {
                $grnSummary = GrnSummary::find($grnId);
                if ($grnSummary) {
                    $grnSummary->updateSettlementTotals();
                }
            }

            DB::commit();
            
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
