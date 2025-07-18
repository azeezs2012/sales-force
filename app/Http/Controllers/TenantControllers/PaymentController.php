<?php

namespace App\Http\Controllers\TenantControllers;

use App\Http\Controllers\Controller;
use App\Models\TenantModels\GrnSummary;
use App\Models\TenantModels\GrnSettlement;
use App\Models\TenantModels\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Create a payment settlement for GRNs
     */
    public function createSettlement(Request $request)
    {
        $request->validate([
            'grn_ids' => 'required|array',
            'grn_ids.*' => 'exists:grn_summaries,id',
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_reference' => 'required|string',
            'payment_notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $grnIds = $request->grn_ids;
            $paymentAmount = $request->payment_amount;
            $paymentDate = $request->payment_date;
            $paymentReference = $request->payment_reference;
            $paymentNotes = $request->payment_notes;

            // Validate that all GRNs belong to the same supplier
            $grns = GrnSummary::whereIn('id', $grnIds)->get();
            $supplierIds = $grns->pluck('supplier_id')->unique();
            
            if ($supplierIds->count() > 1) {
                return response()->json(['message' => 'All GRNs must belong to the same supplier.'], 422);
            }

            // Calculate total unsettled amount for selected GRNs
            $totalUnsettledAmount = $grns->sum('remaining_amount');
            
            if ($paymentAmount > $totalUnsettledAmount) {
                return response()->json(['message' => 'Payment amount cannot exceed total unsettled amount.'], 422);
            }

            // Create the payment record
            $payment = Payment::create([
                'supplier_id' => $supplierIds->first(),
                'account_id' => $request->account_id ?? 1, // Default account, can be made configurable
                'payment_date' => $paymentDate,
                'payment_amount' => $paymentAmount,
                'payment_reference' => $paymentReference,
                'payment_notes' => $paymentNotes,
                'payment_status' => 'posted',
                'payment_method' => $request->payment_method ?? 'cash',
                'created_by' => auth()->id(),
            ]);

            // Distribute payment across GRNs (proportional to remaining amounts)
            $remainingPaymentAmount = $paymentAmount;
            
            foreach ($grns as $grn) {
                if ($remainingPaymentAmount <= 0) break;
                
                $grnUnsettledAmount = $grn->remaining_amount;
                if ($grnUnsettledAmount <= 0) continue;
                
                // Calculate settlement amount for this GRN
                $settlementAmount = min($remainingPaymentAmount, $grnUnsettledAmount);
                
                // Create settlement record
                GrnSettlement::create([
                    'grn_summary_id' => $grn->id,
                    'settlement_type' => 'payment',
                    'settlement_reference_id' => $payment->id,
                    'settlement_reference_type' => 'payments',
                    'settlement_amount' => $settlementAmount,
                    'settlement_date' => $paymentDate,
                    'settlement_notes' => $paymentNotes ?: "Payment: {$paymentReference}",
                    'created_by' => auth()->id(),
                ]);

                // Update GRN settlement totals and status
                $grn->updateSettlementTotals();
                
                $remainingPaymentAmount -= $settlementAmount;
            }

            DB::commit();

            return response()->json([
                'message' => 'Payment settlement created successfully.',
                'settled_amount' => $paymentAmount - $remainingPaymentAmount,
                'remaining_amount' => $remainingPaymentAmount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create payment settlement.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get settlement summary for a GRN
     */
    public function getSettlementSummary($grnId)
    {
        $grn = GrnSummary::with(['supplier.user', 'location', 'account'])
            ->findOrFail($grnId);

        return response()->json([
            'grn' => $grn,
            'summary' => [
                'total_amount' => $grn->total_amount,
                'total_payment_received_amount' => $grn->total_payment_received_amount,
                'total_grn_credit_settled_amount' => $grn->total_grn_credit_settled_amount,
                'total_settled_amount' => $grn->total_settled_amount,
                'remaining_amount' => $grn->remaining_amount,
                'status' => $grn->grn_status,
            ]
        ]);
    }

    /**
     * Get all GRNs with settlement information
     */
    public function getGrnsWithSettlements()
    {
        $grns = GrnSummary::with(['supplier.user', 'location', 'account'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($grn) {
                return [
                    'id' => $grn->id,
                    'grn_date' => $grn->grn_date,
                    'supplier' => $grn->supplier->user->name,
                    'location' => $grn->location->location_name,
                    'total_amount' => $grn->total_amount,
                    'total_settled_amount' => $grn->total_settled_amount,
                    'remaining_amount' => $grn->remaining_amount,
                    'status' => $grn->grn_status,
                    'settlement_percentage' => $grn->total_amount > 0 ? 
                        round(($grn->total_settled_amount / $grn->total_amount) * 100, 2) : 0,
                ];
            });

        return response()->json($grns);
    }
}
