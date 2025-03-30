<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\TenantModels\PaymentTerm;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Validators\PaymentTermValidator;

class PaymentTermController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paymentTerms = PaymentTerm::with(['creator', 'updater', 'approver'])->get();
        return response()->json($paymentTerms);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = PaymentTermValidator::validate($request->all());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $paymentTerm = new PaymentTerm($request->all());
        $paymentTerm->created_by = auth()->id();
        $paymentTerm->created_at = now();
        $paymentTerm->save();

        if ($paymentTerm->approved) {
            $paymentTerm->approved_by = auth()->id();
        }

        return response()->json($paymentTerm, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $paymentTerm = PaymentTerm::findOrFail($id);
        return response()->json($paymentTerm);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $paymentTerm = PaymentTerm::findOrFail($id);

        $validator = PaymentTermValidator::validate($request->all(), $paymentTerm->id);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $paymentTerm->fill($request->all());
        $paymentTerm->updated_by = auth()->id();
        $paymentTerm->updated_at = now();
        $paymentTerm->save();

        if ($paymentTerm->approved) {
            $paymentTerm->approved_by = auth()->id();
        }

        return response()->json($paymentTerm);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $paymentTerm = PaymentTerm::findOrFail($id);

        $paymentTerm->deleted_by = auth()->id();
        $paymentTerm->deleted_at = now();
        $paymentTerm->save();
        $paymentTerm->delete();

        return response()->json(null, 204);
    }
} 