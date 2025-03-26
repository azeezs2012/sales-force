<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\TenantModels\ProductType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Validators\ProductTypeValidator;

class ProductTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $productTypes = ProductType::with(['creator', 'updater', 'approver'])->get();
        return response()->json($productTypes);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = ProductTypeValidator::validate($request->all());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->parent && !$this->hasValidParentHierarchy($request->parent)) {
            return response()->json(['error' => 'Parent hierarchy exceeds maximum depth of 5.'], 422);
        }

        $productType = new ProductType($request->all());
        $productType->created_by = auth()->id();
        $productType->created_at = now();
        if ($productType->approved) {
            $productType->approved_by = auth()->id();
        }
        $productType->save();

        return response()->json($productType, 201);
    }

    /**
     * Check if the product type has a valid parent hierarchy.
     *
     * @param  int  $productTypeId
     * @return bool
     */
    private function hasValidParentHierarchy($productTypeId)
    {
        $depth = 0;
        $currentType = ProductType::find($productTypeId);

        while ($currentType && $currentType->parent && $depth < 5) {
            $currentType = ProductType::find($currentType->parent);
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
        $productType = ProductType::findOrFail($id);
        return response()->json($productType);
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
        $productType = ProductType::findOrFail($id);

        $validator = ProductTypeValidator::validate($request->all(), $productType->id);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $productType->fill($request->all());
        $productType->updated_by = auth()->id();
        $productType->updated_at = now();
        if ($productType->approved) {
            $productType->approved_by = auth()->id();
        }
        $productType->save();

        return response()->json($productType);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $productType = ProductType::findOrFail($id);

        // Check if the product type has child types
        $hasChildren = ProductType::where('parent', $id)->exists();
        if ($hasChildren) {
            throw new \Exception('Cannot delete product type with child types.');
        }

        $productType->deleted_by = auth()->id();
        $productType->deleted_at = now();
        $productType->save();
        $productType->delete();

        return response()->json(null, 204);
    }
} 