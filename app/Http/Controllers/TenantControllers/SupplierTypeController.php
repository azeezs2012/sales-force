<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\TenantModels\SupplierType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Validators\SupplierTypeValidator;

class SupplierTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $supplierTypes = SupplierType::with(['creator', 'updater', 'approver'])->get();
        return response()->json($supplierTypes);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = SupplierTypeValidator::validate($request->all());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->parent && !$this->hasValidParentHierarchy($request->parent)) {
            return response()->json(['error' => 'Parent hierarchy exceeds maximum depth of 5.'], 422);
        }

        $supplierType = new SupplierType($request->all());
        $supplierType->created_by = auth()->id();
        $supplierType->created_at = now();
        if ($supplierType->approved) {
            $supplierType->approved_by = auth()->id();
        }
        $supplierType->save();

        return response()->json($supplierType, 201);
    }

    /**
     * Check if the supplier type has a valid parent hierarchy.
     *
     * @param  int  $supplierTypeId
     * @return bool
     */
    private function hasValidParentHierarchy($supplierTypeId)
    {
        $depth = 0;
        $currentType = SupplierType::find($supplierTypeId);

        while ($currentType && $currentType->parent && $depth < 5) {
            $currentType = SupplierType::find($currentType->parent);
            $depth++;
        }

        return $depth < 5;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $supplierType = SupplierType::findOrFail($id);
        return response()->json($supplierType);
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
        $supplierType = SupplierType::findOrFail($id);

        $validator = SupplierTypeValidator::validate($request->all(), $supplierType->id);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $supplierType->fill($request->all());
        $supplierType->updated_by = auth()->id();
        $supplierType->updated_at = now();
        if ($supplierType->approved) {
            $supplierType->approved_by = auth()->id();
        }
        $supplierType->save();

        return response()->json($supplierType);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $supplierType = SupplierType::findOrFail($id);

        // Check if the supplier type has child types
        $hasChildren = SupplierType::where('parent', $id)->exists();
        if ($hasChildren) {
            throw new \Exception('Cannot delete supplier type with child types.');
        }

        $supplierType->deleted_by = auth()->id();
        $supplierType->deleted_at = now();
        $supplierType->save();
        $supplierType->delete();

        return response()->json(null, 204);
    }
} 