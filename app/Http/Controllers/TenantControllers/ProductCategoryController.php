<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\TenantModels\ProductCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Validators\ProductCategoryValidator;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $productCategories = ProductCategory::with(['creator', 'updater', 'approver'])->get();
        return response()->json($productCategories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = ProductCategoryValidator::validate($request->all());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->parent && !$this->hasValidParentHierarchy($request->parent)) {
            return response()->json(['error' => 'Parent hierarchy exceeds maximum depth of 5.'], 422);
        }

        // Check for circular reference
        if ($request->parent && $this->hasCircularReference($request->parent)) {
            return response()->json(['error' => 'A product category cannot be its own parent or create a circular reference.'], 422);
        }

        $productCategory = new ProductCategory($request->all());
        $productCategory->created_by = auth()->id();
        $productCategory->created_at = now();
        if ($productCategory->approved) {
            $productCategory->approved_by = auth()->id();
        }
        $productCategory->save();

        return response()->json($productCategory, 201);
    }

    /**
     * Check if the product category has a valid parent hierarchy.
     *
     * @param  int  $productCategoryId
     * @return bool
     */
    private function hasValidParentHierarchy($productCategoryId)
    {
        $depth = 0;
        $currentCategory = ProductCategory::find($productCategoryId);

        while ($currentCategory && $currentCategory->parent && $depth < 5) {
            $currentCategory = ProductCategory::find($currentCategory->parent);
            $depth++;
        }

        return $depth < 5;
    }

    /**
     * Check for circular reference in parent hierarchy.
     *
     * @param  int  $parentId
     * @return bool
     */
    private function hasCircularReference($parentId)
    {
        $visited = [];
        $currentParentId = $parentId;

        while ($currentParentId) {
            if (in_array($currentParentId, $visited)) {
                return true;
            }
            $visited[] = $currentParentId;
            $currentCategory = ProductCategory::find($currentParentId);
            $currentParentId = $currentCategory ? $currentCategory->parent : null;
        }

        return false;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $productCategory = ProductCategory::findOrFail($id);
        return response()->json($productCategory);
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
        $productCategory = ProductCategory::findOrFail($id);

        $validator = ProductCategoryValidator::validate($request->all(), $productCategory->id);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->parent && !$this->hasValidParentHierarchy($request->parent)) {
            return response()->json(['error' => 'Parent hierarchy exceeds maximum depth of 5.'], 422);
        }

        // Check for circular reference
        if ($request->parent && $this->hasCircularReference($request->parent)) {
            return response()->json(['error' => 'A product category cannot be its own parent or create a circular reference.'], 422);
        }

        $productCategory->fill($request->all());
        $productCategory->updated_by = auth()->id();
        $productCategory->updated_at = now();
        if ($productCategory->approved) {
            $productCategory->approved_by = auth()->id();
        }
        $productCategory->save();

        return response()->json($productCategory);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $productCategory = ProductCategory::findOrFail($id);

        // Check if the product category has child categories
        $hasChildren = ProductCategory::where('parent', $id)->exists();
        if ($hasChildren) {
            throw new \Exception('Cannot delete product category with child categories.');
        }

        $productCategory->deleted_by = auth()->id();
        $productCategory->deleted_at = now();
        $productCategory->save();
        $productCategory->delete();

        return response()->json(null, 204);
    }
} 