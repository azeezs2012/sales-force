<?php

namespace App\Http\Controllers\TenantControllers;

use App\Http\Controllers\Controller;
use App\Models\TenantModels\GrnSummary;
use App\Models\TenantModels\GrnCreditSummary;
use App\Models\TenantModels\Payment;
use App\Models\TenantModels\GrnSettlement;
use App\Models\TenantModels\GrnCreditSettlement;
use App\Models\TenantModels\Supplier;
use App\Models\TenantModels\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrnPaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = Payment::with(['supplier.user'])
            ->latest()
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_date' => $payment->payment_date,
                    'supplier_id' => $payment->supplier_id,
                    'payment_method_id' => null, // Payment model stores payment_method as string
                    'payment_amount' => $payment->payment_amount,
                    'payment_reference' => $payment->payment_reference,
                    'payment_notes' => $payment->payment_notes,
                    'payment_status' => $payment->payment_status,
                    'supplier' => $payment->supplier,
                    'payment_method' => ['payment_method_name' => $payment->payment_method || 'Unknown'],
                ];
            });

        return response()->json($payments);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $payment = Payment::with(['supplier.user', 'settlements.grnSummary'])
            ->findOrFail($id);

        // Get GRN applications from settlements
        $grnApplications = GrnSettlement::where('settlement_reference_type', 'payments')
            ->where('settlement_reference_id', $payment->id)
            ->get()
            ->groupBy('grn_summary_id')
            ->map(function ($settlements) {
                $applyPayment = $settlements->where('settlement_type', 'payment')->sum('settlement_amount');
                $applyCredits = $settlements->where('settlement_type', 'grn_credit')->sum('settlement_amount');
                
                return [
                    'grn_id' => $settlements->first()->grn_summary_id,
                    'apply_payment' => $applyPayment,
                    'apply_credits' => $applyCredits,
                ];
            })
            ->values()
            ->toArray();

        // Get credit applications from GRN credit settlements
        $creditApplications = GrnCreditSettlement::where('settlement_reference_type', 'payments')
            ->where('settlement_reference_id', $payment->id)
            ->pluck('grn_credit_summary_id')
            ->toArray();

        $paymentData = $payment->toArray();
        $paymentData['grn_applications'] = $grnApplications;
        $paymentData['credit_applications'] = $creditApplications;

        return response()->json($paymentData);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'payment_date' => 'required|date',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_reference' => 'nullable|string',
            'payment_notes' => 'nullable|string',
            'grn_applications' => 'array',
            'grn_applications.*.grn_id' => 'required|exists:grn_summaries,id',
            'grn_applications.*.apply_payment' => 'required|numeric|min:0',
            'grn_applications.*.apply_credits' => 'required|numeric|min:0',
            'credit_applications' => 'array',
            'credit_applications.*' => 'exists:grn_credit_summaries,id',
        ]);

        DB::beginTransaction();
        try {
            $payment = Payment::findOrFail($id);

            // Update basic payment fields
            $payment->update([
                'payment_date' => $request->payment_date,
                'payment_method_id' => $request->payment_method_id,
                'payment_amount' => $request->payment_amount,
                'payment_reference' => $request->payment_reference,
                'payment_notes' => $request->payment_notes,
            ]);

            // Get affected GRN IDs and credit IDs for status updates
            $affectedGrnIds = collect();
            $affectedCreditIds = collect();

            // Delete existing GRN settlements for this payment
            $existingGrnSettlements = GrnSettlement::where('settlement_reference_type', 'payments')
                ->where('settlement_reference_id', $payment->id)
                ->get();
            
            foreach ($existingGrnSettlements as $settlement) {
                $affectedGrnIds->push($settlement->grn_summary_id);
            }
            $existingGrnSettlements->each->delete();

            // Delete existing GRN credit settlements for this payment
            $existingCreditSettlements = GrnCreditSettlement::where('settlement_reference_type', 'payments')
                ->where('settlement_reference_id', $payment->id)
                ->get();
            
            foreach ($existingCreditSettlements as $settlement) {
                $affectedCreditIds->push($settlement->grn_credit_summary_id);
            }
            $existingCreditSettlements->each->delete();

            // Process new GRN applications
            if (!empty($request->grn_applications)) {
                foreach ($request->grn_applications as $application) {
                    $grn = GrnSummary::find($application['grn_id']);
                    $applyPayment = floatval($application['apply_payment']);
                    $applyCredits = floatval($application['apply_credits']);

                    if ($applyPayment > 0) {
                        // Create payment settlement
                        GrnSettlement::create([
                            'grn_summary_id' => $grn->id,
                            'settlement_type' => 'payment',
                            'settlement_reference_id' => $payment->id,
                            'settlement_reference_type' => 'payments',
                            'settlement_amount' => $applyPayment,
                            'settlement_date' => $request->payment_date,
                            'settlement_notes' => "Payment: {$payment->payment_reference}",
                            'created_by' => auth()->id(),
                        ]);

                        $affectedGrnIds->push($grn->id);
                    }

                    if ($applyCredits > 0) {
                        // Create credit settlement
                        GrnSettlement::create([
                            'grn_summary_id' => $grn->id,
                            'settlement_type' => 'grn_credit',
                            'settlement_reference_id' => 0, // Will be updated when credits are applied
                            'settlement_reference_type' => 'grn_credit_summaries',
                            'settlement_amount' => $applyCredits,
                            'settlement_date' => $request->payment_date,
                            'settlement_notes' => "Credit Application",
                            'created_by' => auth()->id(),
                        ]);

                        $affectedGrnIds->push($grn->id);
                    }
                }
            }

            // Process new credit applications
            if (!empty($request->credit_applications)) {
                foreach ($request->credit_applications as $creditId) {
                    $credit = GrnCreditSummary::find($creditId);
                    
                    // Calculate how much of this credit to apply
                    $creditAmountToApply = $credit->total_amount; // For now, apply full amount
                    
                    // Create GRN credit settlement record
                    GrnCreditSettlement::create([
                        'grn_credit_summary_id' => $credit->id,
                        'settlement_type' => 'payment_applied',
                        'settlement_reference_id' => $payment->id,
                        'settlement_reference_type' => 'payments',
                        'settlement_amount' => $creditAmountToApply,
                        'settlement_date' => $request->payment_date,
                        'settlement_notes' => "Applied to Payment: {$payment->payment_reference}",
                        'created_by' => auth()->id(),
                    ]);

                    $affectedCreditIds->push($credit->id);
                }
            }

            // Update settlement totals for all affected GRNs
            foreach ($affectedGrnIds->unique() as $grnId) {
                $grn = GrnSummary::find($grnId);
                if ($grn) {
                    $grn->updateSettlementTotals();
                }
            }

            // Update settlement totals for all affected GRN credits
            foreach ($affectedCreditIds->unique() as $creditId) {
                $credit = GrnCreditSummary::find($creditId);
                if ($credit) {
                    $credit->updateSettlementTotals();
                }
            }

            DB::commit();

            return response()->json($payment->load(['supplier.user']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update payment.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $payment = Payment::findOrFail($id);
            
            // Get affected GRN IDs and credit IDs before deleting settlements
            $affectedGrnIds = collect();
            $affectedCreditIds = collect();
            
            // Get GRNs that were settled by this payment
            $grnSettlements = GrnSettlement::where('settlement_reference_type', 'payments')
                ->where('settlement_reference_id', $payment->id)
                ->get();
            
            foreach ($grnSettlements as $settlement) {
                $affectedGrnIds->push($settlement->grn_summary_id);
            }
            
            // Get credits that were applied to this payment
            $creditSettlements = GrnCreditSettlement::where('settlement_reference_type', 'payments')
                ->where('settlement_reference_id', $payment->id)
                ->get();
            
            foreach ($creditSettlements as $settlement) {
                $affectedCreditIds->push($settlement->grn_credit_summary_id);
            }
            
            // Delete related GRN settlements
            $grnSettlements->each->delete();
            
            // Delete related GRN credit settlements
            $creditSettlements->each->delete();
            
            // Delete the payment
            $payment->delete();
            
            // Update settlement totals for all affected GRNs
            foreach ($affectedGrnIds->unique() as $grnId) {
                $grn = GrnSummary::find($grnId);
                if ($grn) {
                    $grn->updateSettlementTotals();
                }
            }
            
            // Update settlement totals for all affected GRN credits
            foreach ($affectedCreditIds->unique() as $creditId) {
                $credit = GrnCreditSummary::find($creditId);
                if ($credit) {
                    $credit->updateSettlementTotals();
                }
            }
            
            DB::commit();
            return response()->json(['message' => 'Payment deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to delete payment.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get open GRNs for a supplier
     */
    public function getOpenGrns($supplierId)
    {
        $grns = GrnSummary::with(['location', 'supplier.user'])
            ->where('supplier_id', $supplierId)
            ->where('grn_status', '!=', 'Closed')
            ->orderBy('grn_date', 'asc')
            ->get()
            ->filter(function ($grn) {
                // Only include GRNs that have remaining balance due
                return $grn->remaining_amount > 0;
            })
            ->map(function ($grn) {
                return [
                    'id' => $grn->id,
                    'grn_date' => $grn->grn_date,
                    'location' => $grn->location,
                    'total_amount' => $grn->total_amount,
                    'total_settled_amount' => $grn->total_settled_amount,
                    'balance_due' => $grn->remaining_amount,
                    'grn_status' => $grn->grn_status,
                ];
            });

        return response()->json($grns);
    }

    /**
     * Get all GRNs for a supplier (including applied ones for editing)
     */
    public function getAllGrns($supplierId, $paymentId = null)
    {
        $grns = GrnSummary::with(['location', 'supplier.user'])
            ->where('supplier_id', $supplierId)
            ->orderBy('grn_date', 'asc')
            ->get();
            
        $filteredGrns = $grns->filter(function ($grn) use ($paymentId) {
            // If editing a payment, include GRNs that:
            // 1. Have remaining balance due (open GRNs), OR
            // 2. Were settled by the current payment being edited
            if ($paymentId) {
                $hasSettlementsForThisPayment = $grn->settlements()
                    ->where('settlement_reference_type', 'payments')
                    ->where('settlement_reference_id', $paymentId)
                    ->exists();
                
                return $grn->remaining_amount > 0 || $hasSettlementsForThisPayment;
            }
            
            // If creating new payment, only include open GRNs
            return $grn->remaining_amount > 0;
        })->values(); // Convert to array to avoid object with numeric keys
        
        $result = $filteredGrns->map(function ($grn) {
            return [
                'id' => $grn->id,
                'grn_date' => $grn->grn_date,
                'location' => $grn->location,
                'total_amount' => $grn->total_amount,
                'total_settled_amount' => $grn->total_settled_amount,
                'balance_due' => $grn->remaining_amount,
                'grn_status' => $grn->grn_status,
            ];
        });

        return response()->json($result);
    }

    /**
     * Get available GRN credits for a supplier
     */
    public function getAvailableCredits($supplierId)
    {
        $credits = GrnCreditSummary::with(['supplier.user'])
            ->where('supplier_id', $supplierId)
            ->where('grn_credit_status', '!=', 'Closed')
            ->orderBy('grn_credit_date', 'asc')
            ->get()
            ->filter(function ($credit) {
                // Only include credits that have remaining amount to apply
                return $credit->remaining_amount > 0;
            })
            ->map(function ($credit) {
                return [
                    'id' => $credit->id,
                    'grn_credit_date' => $credit->grn_credit_date,
                    'total_amount' => $credit->total_amount,
                    'grn_credit_status' => $credit->grn_credit_status,
                    'credit_reason' => $credit->credit_reason,
                ];
            });

        return response()->json($credits);
    }

    /**
     * Get all GRN credits for a supplier (including applied ones for editing)
     */
    public function getAllCredits($supplierId, $paymentId = null)
    {
        $credits = GrnCreditSummary::with(['supplier.user'])
            ->where('supplier_id', $supplierId)
            ->orderBy('grn_credit_date', 'asc')
            ->get()
            ->filter(function ($credit) use ($paymentId) {
                // If editing a payment, include credits that:
                // 1. Have remaining amount to apply (open credits), OR
                // 2. Were applied to the current payment being edited
                if ($paymentId) {
                    $hasSettlementsForThisPayment = $credit->settlements()
                        ->where('settlement_reference_type', 'payments')
                        ->where('settlement_reference_id', $paymentId)
                        ->exists();
                    
                    return $credit->remaining_amount > 0 || $hasSettlementsForThisPayment;
                }
                
                // If creating new payment, only include open credits
                return $credit->remaining_amount > 0;
            })->values() // Convert to array to avoid object with numeric keys
            ->map(function ($credit) {
                return [
                    'id' => $credit->id,
                    'grn_credit_date' => $credit->grn_credit_date,
                    'total_amount' => $credit->total_amount,
                    'grn_credit_status' => $credit->grn_credit_status,
                    'credit_reason' => $credit->credit_reason,
                ];
            });

        return response()->json($credits);
    }

    /**
     * Create a comprehensive payment with GRN applications
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'payment_date' => 'required|date',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_reference' => 'nullable|string',
            'payment_notes' => 'nullable|string',
            'grn_applications' => 'array',
            'grn_applications.*.grn_id' => 'required|exists:grn_summaries,id',
            'grn_applications.*.apply_payment' => 'required|numeric|min:0',
            'grn_applications.*.apply_credits' => 'required|numeric|min:0',
            'credit_applications' => 'array',
            'credit_applications.*' => 'exists:grn_credit_summaries,id',
        ]);

        DB::beginTransaction();
        try {
            // Validate supplier consistency
            $grnIds = collect($request->grn_applications)->pluck('grn_id');
            $grns = GrnSummary::whereIn('id', $grnIds)->get();
            
            if ($grns->pluck('supplier_id')->unique()->count() > 1) {
                return response()->json(['message' => 'All GRNs must belong to the same supplier.'], 422);
            }

            if ($grns->pluck('supplier_id')->first() != $request->supplier_id) {
                return response()->json(['message' => 'GRNs must belong to the selected supplier.'], 422);
            }

            // Validate credit applications
            if (!empty($request->credit_applications)) {
                $credits = GrnCreditSummary::whereIn('id', $request->credit_applications)->get();
                if ($credits->pluck('supplier_id')->unique()->first() != $request->supplier_id) {
                    return response()->json(['message' => 'Credits must belong to the selected supplier.'], 422);
                }
            }

            // Create the payment record
            $payment = Payment::create([
                'supplier_id' => $request->supplier_id,
                'account_id' => 1, // Default account, can be made configurable
                'payment_date' => $request->payment_date,
                'payment_amount' => $request->payment_amount,
                'payment_reference' => $request->payment_reference,
                'payment_notes' => $request->payment_notes,
                'payment_status' => 'Open', // Default status
                'payment_method' => PaymentMethod::find($request->payment_method_id)->payment_method_name,
                'created_by' => auth()->id(),
            ]);

            $totalAppliedPayment = 0;
            $totalAppliedCredits = 0;

            // Process GRN applications
            foreach ($request->grn_applications as $application) {
                $grn = GrnSummary::find($application['grn_id']);
                $applyPayment = floatval($application['apply_payment']);
                $applyCredits = floatval($application['apply_credits']);

                if ($applyPayment > 0) {
                    // Create payment settlement
                    GrnSettlement::create([
                        'grn_summary_id' => $grn->id,
                        'settlement_type' => 'payment',
                        'settlement_reference_id' => $payment->id,
                        'settlement_reference_type' => 'payments',
                        'settlement_amount' => $applyPayment,
                        'settlement_date' => $request->payment_date,
                        'settlement_notes' => "Payment: {$payment->payment_reference}",
                        'created_by' => auth()->id(),
                    ]);

                    $totalAppliedPayment += $applyPayment;
                }

                if ($applyCredits > 0) {
                    // Create credit settlement
                    GrnSettlement::create([
                        'grn_summary_id' => $grn->id,
                        'settlement_type' => 'grn_credit',
                        'settlement_reference_id' => 0, // Will be updated when credits are applied
                        'settlement_reference_type' => 'grn_credit_summaries',
                        'settlement_amount' => $applyCredits,
                        'settlement_date' => $request->payment_date,
                        'settlement_notes' => "Credit Application",
                        'created_by' => auth()->id(),
                    ]);

                    $totalAppliedCredits += $applyCredits;
                }

                // Update GRN settlement totals
                $grn->updateSettlementTotals();
            }

            // Process credit applications
            if (!empty($request->credit_applications)) {
                foreach ($request->credit_applications as $creditId) {
                    $credit = GrnCreditSummary::find($creditId);
                    
                    // Calculate how much of this credit to apply
                    $creditAmountToApply = $credit->total_amount; // For now, apply full amount
                    
                    // Create GRN credit settlement record
                    GrnCreditSettlement::create([
                        'grn_credit_summary_id' => $credit->id,
                        'settlement_type' => 'payment_applied',
                        'settlement_reference_id' => $payment->id,
                        'settlement_reference_type' => 'payments',
                        'settlement_amount' => $creditAmountToApply,
                        'settlement_date' => $request->payment_date,
                        'settlement_notes' => "Applied to Payment: {$payment->payment_reference}",
                        'created_by' => auth()->id(),
                    ]);

                    // Update credit settlement totals and status
                    $credit->updateSettlementTotals();
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment created successfully',
                'payment_id' => $payment->id,
                'total_applied_payment' => $totalAppliedPayment,
                'total_applied_credits' => $totalAppliedCredits,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create payment.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get payment summary for a supplier
     */
    public function getPaymentSummary($supplierId)
    {
        $supplier = Supplier::with('user')->findOrFail($supplierId);
        
        $openGrns = GrnSummary::where('supplier_id', $supplierId)
            ->where('grn_status', '!=', 'Closed')
            ->get();

        $availableCredits = GrnCreditSummary::where('supplier_id', $supplierId)
            ->where('grn_credit_status', '!=', 'Closed')
            ->get();

        return response()->json([
            'supplier' => $supplier,
            'open_grns_count' => $openGrns->count(),
            'total_grn_balance' => $openGrns->sum('remaining_amount'),
            'available_credits_count' => $availableCredits->count(),
            'total_credit_value' => $availableCredits->sum('total_amount'),
        ]);
    }
}
