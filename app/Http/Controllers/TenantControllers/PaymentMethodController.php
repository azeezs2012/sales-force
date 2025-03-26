<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\TenantModels\PaymentMethod;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Validators\PaymentMethodValidator;

class PaymentMethodController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $paymentMethods = PaymentMethod::with(['creator', 'updater'])->get();
        return response()->json($paymentMethods);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = PaymentMethodValidator::validate($request->all());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $paymentMethod = new PaymentMethod($request->all());
        $paymentMethod->created_by = auth()->id();
        $paymentMethod->created_at = now();
        $paymentMethod->save();

        if ($paymentMethod->approved) {
            $paymentMethod->approved_by = auth()->id();
        }

        return response()->json($paymentMethod, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);
        return response()->json($paymentMethod);
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
        $paymentMethod = PaymentMethod::findOrFail($id);

        $validator = PaymentMethodValidator::validate($request->all(), $paymentMethod->id);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $paymentMethod->fill($request->all());
        $paymentMethod->updated_by = auth()->id();
        $paymentMethod->updated_at = now();
        $paymentMethod->save();

        if ($paymentMethod->approved) {
            $paymentMethod->approved_by = auth()->id();
        }

        return response()->json($paymentMethod);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $paymentMethod = PaymentMethod::findOrFail($id);

        $paymentMethod->deleted_by = auth()->id();
        $paymentMethod->deleted_at = now();
        $paymentMethod->save();
        $paymentMethod->delete();

        return response()->json(null, 204);
    }
} 