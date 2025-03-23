<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\TenantModels\SalesRep;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Validators\SalesRepValidator;

class SalesRepController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $salesReps = SalesRep::with(['childSalesReps', 'creator', 'updater'])->get();
        return response()->json($salesReps);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = SalesRepValidator::validate($request->all());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->parent && !$this->hasValidParentHierarchy($request->parent)) {
            return response()->json(['error' => 'Parent hierarchy exceeds maximum depth of 5.'], 422);
        }

        $salesRep = new SalesRep($request->all());
        $salesRep->created_by = auth()->id();
        $salesRep->created_at = now();
        $salesRep->save();

        return response()->json($salesRep, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $salesRep = SalesRep::findOrFail($id);
        return response()->json($salesRep);
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
        $salesRep = SalesRep::findOrFail($id);

        $validator = SalesRepValidator::validate($request->all(), $salesRep->id);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->parent && !$this->hasValidParentHierarchy($request->parent)) {
            return response()->json(['error' => 'Parent hierarchy exceeds maximum depth of 5.'], 422);
        }

        $salesRep->fill($request->all());
        $salesRep->updated_by = auth()->id();
        $salesRep->updated_at = now();
        $salesRep->save();

        return response()->json($salesRep);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $salesRep = SalesRep::findOrFail($id);

        // Check if the sales representative has child sales representatives
        $hasChildren = SalesRep::where('parent', $id)->exists();
        if ($hasChildren) {
            throw new \Exception('Cannot delete sales representative with child sales representatives.');
        }

        $salesRep->deleted_by = auth()->id();
        $salesRep->deleted_at = now();
        $salesRep->save();
        $salesRep->delete();

        return response()->json(null, 204);
    }

    /**
     * Check if the sales representative has a valid parent hierarchy.
     *
     * @param  int  $salesRepId
     * @return bool
     */
    private function hasValidParentHierarchy($salesRepId)
    {
        $depth = 0;
        $currentSalesRep = SalesRep::find($salesRepId);

        while ($currentSalesRep && $currentSalesRep->parent && $depth < 5) {
            $currentSalesRep = SalesRep::find($currentSalesRep->parent);
            $depth++;
        }

        return $depth < 5;
    }
} 