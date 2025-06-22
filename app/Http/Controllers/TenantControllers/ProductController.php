<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\TenantModels\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Validators\ProductValidator;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with([
            'parent', 'children', 'productType', 'productCategory',
            'salesAccount', 'expenseAccount', 'inventoryAccount',
            'creator', 'updater'
        ])->get();

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validator = ProductValidator::validate($request->all());

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        if ($request->parent_id && !$this->hasValidParentHierarchy($request->parent_id)) {
            return response()->json(['error' => 'Parent hierarchy exceeds maximum depth of 5.'], 422);
        }

        $product = new Product($request->all());
        $product->created_by = auth()->id();
        $product->save();

        return response()->json($product->load([
            'parent', 'children', 'productType', 'productCategory',
            'salesAccount', 'expenseAccount', 'inventoryAccount',
            'creator', 'updater'
        ]), 201);
    }

    public function show($id)
    {
        $product = Product::with([
            'parent', 'children', 'productType', 'productCategory',
            'salesAccount', 'expenseAccount', 'inventoryAccount',
            'creator', 'updater'
        ])->findOrFail($id);

        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = ProductValidator::validate($request->all(), $product->id);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        if ($request->parent_id && !$this->hasValidParentHierarchy($request->parent_id)) {
            return response()->json(['error' => 'Parent hierarchy exceeds maximum depth of 5.'], 422);
        }

        $product->fill($request->all());
        $product->updated_by = auth()->id();
        $product->save();

        return response()->json($product->load([
            'parent', 'children', 'productType', 'productCategory',
            'salesAccount', 'expenseAccount', 'inventoryAccount',
            'creator', 'updater'
        ]));
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->children()->exists()) {
            throw new \Exception('Cannot delete a product that has child products.');
        }

        $product->deleted_by = auth()->id();
        $product->save();
        $product->delete();

        return response()->json(null, 204);
    }

    private function hasValidParentHierarchy($productId, $depth = 5)
    {
        $count = 0;
        $current = Product::find($productId);

        while ($current && $current->parent_id) {
            $current = $current->parent;
            $count++;
            if ($count >= $depth) {
                return false;
            }
        }

        return true;
    }
} 