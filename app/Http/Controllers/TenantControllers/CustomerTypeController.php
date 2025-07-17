<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\TenantModels\CustomerType;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Validators\CustomerTypeValidator;

class CustomerTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $customerTypes = CustomerType::with(['creator', 'updater', 'approver'])->get();
        return response()->json($customerTypes);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = CustomerTypeValidator::validate($request->all());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Check for circular reference
        if ($request->parent && $this->hasCircularReference(null, $request->parent, $request->all())) {
            return response()->json(['error' => 'Cannot set parent to create a circular reference.'], 422);
        }

        if ($request->parent && !$this->hasValidParentHierarchy($request->parent)) {
            return response()->json(['error' => 'Parent hierarchy exceeds maximum depth of 5.'], 422);
        }

        $customerType = new CustomerType($request->all());
        $customerType->created_by = auth()->id();
        $customerType->created_at = now();
        if ($customerType->approved) {
            $customerType->approved_by = auth()->id();
        }
        $customerType->save();

        return response()->json($customerType, 201);
    }

    /**
     * Check if the customer type has a valid parent hierarchy.
     *
     * @param  int  $customerTypeId
     * @return bool
     */
    private function hasValidParentHierarchy($customerTypeId)
    {
        $depth = 0;
        $currentType = CustomerType::find($customerTypeId);

        while ($currentType && $currentType->parent && $depth < 5) {
            $currentType = CustomerType::find($currentType->parent);
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
        $customerType = CustomerType::findOrFail($id);
        return response()->json($customerType);
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
        $customerType = CustomerType::findOrFail($id);

        $validator = CustomerTypeValidator::validate($request->all(), $customerType->id);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Check for circular reference
        if ($request->parent && $this->hasCircularReference($id, $request->parent)) {
            return response()->json(['error' => 'Cannot set parent to create a circular reference.'], 422);
        }

        $customerType->fill($request->all());
        $customerType->updated_by = auth()->id();
        $customerType->updated_at = now();
        if ($customerType->approved) {
            $customerType->approved_by = auth()->id();
        }
        $customerType->save();

        return response()->json($customerType);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $customerType = CustomerType::findOrFail($id);

        // Check if the customer type has child types
        $hasChildren = CustomerType::where('parent', $id)->exists();
        if ($hasChildren) {
            throw new \Exception('Cannot delete customer type with child types.');
        }

        $customerType->deleted_by = auth()->id();
        $customerType->deleted_at = now();
        $customerType->save();
        $customerType->delete();

        return response()->json(null, 204);
    }

    /**
     * Check for circular reference when setting a parent.
     *
     * @param  int|null  $customerTypeId
     * @param  int  $parentId
     * @param  array|null $data (for store)
     * @return bool
     */
    private function hasCircularReference($customerTypeId, $parentId, $data = null)
    {
        // A customer type cannot be its own parent
        if ($customerTypeId && $customerTypeId == $parentId) {
            return true;
        }

        $visited = [];
        $currentParentId = $parentId;

        while ($currentParentId) {
            if (in_array($currentParentId, $visited)) {
                return true;
            }
            if ($customerTypeId && $currentParentId == $customerTypeId) {
                return true;
            }
            $visited[] = $currentParentId;
            if ($data && isset($data['id']) && $currentParentId == $data['id']) {
                return true;
            }
            $parentType = CustomerType::find($currentParentId);
            $currentParentId = $parentType ? $parentType->parent : null;
        }
        return false;
    }
} 