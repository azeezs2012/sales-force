<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\TenantModels\Branch;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Validators\BranchValidator;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $branches = Branch::with(['childBranches', 'creator', 'updater', 'approver'])->get();
        return response()->json($branches);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = BranchValidator::validate($request->all());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->parent && !$this->hasValidParentHierarchy($request->parent)) {
            return response()->json(['error' => 'Parent hierarchy exceeds maximum depth of 5.'], 422);
        }

        $branch = new Branch($request->all());
        $branch->created_by = auth()->id();
        $branch->created_at = now();
        if ($branch->approved) {
            $branch->approved_by = auth()->id();
        }
        $branch->save();

        return response()->json($branch, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $branch = Branch::findOrFail($id);
        return response()->json($branch);
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
        $branch = Branch::findOrFail($id);

        $validator = BranchValidator::validate($request->all(), $branch->id);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Check for circular reference
        if ($request->parent && $this->hasCircularReference($id, $request->parent)) {
            return response()->json(['error' => 'Cannot set parent to create a circular reference.'], 422);
        }

        if ($request->parent && !$this->hasValidParentHierarchy($request->parent)) {
            return response()->json(['error' => 'Parent hierarchy exceeds maximum depth of 5.'], 422);
        }

        $branch->fill($request->all());
        $branch->updated_by = auth()->id();
        $branch->updated_at = now();
        if ($branch->approved) {
            $branch->approved_by = auth()->id();
        }
        $branch->save();

        return response()->json($branch);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);

        // Check if the branch has child branches
        $hasChildren = Branch::where('parent', $id)->exists();
        if ($hasChildren) {
            throw new \Exception('Cannot delete branch with child branches.');
        }

        $branch->deleted_by = auth()->id();
        $branch->deleted_at = now();
        $branch->save();
        $branch->delete();

        return response()->json(null, 204);
    }

    /**
     * Check if the branch has a valid parent hierarchy.
     *
     * @param  int  $branchId
     * @return bool
     */
    private function hasValidParentHierarchy($branchId)
    {
        $depth = 0;
        $currentBranch = Branch::find($branchId);

        while ($currentBranch && $currentBranch->parent && $depth < 5) {
            $currentBranch = Branch::find($currentBranch->parent);
            $depth++;
        }

        return $depth < 5;
    }

    /**
     * Check for circular reference when setting a parent.
     *
     * @param  int  $branchId
     * @param  int  $parentId
     * @return bool
     */
    private function hasCircularReference($branchId, $parentId)
    {
        // A branch cannot be its own parent
        if ($branchId == $parentId) {
            return true;
        }

        $visited = [];
        $currentParentId = $parentId;

        while ($currentParentId) {
            // Check if we've already visited this parent (circular reference)
            if (in_array($currentParentId, $visited)) {
                return true;
            }

            // Check if the current parent is the branch we're trying to update
            if ($currentParentId == $branchId) {
                return true;
            }

            $visited[] = $currentParentId;
            $parentBranch = Branch::find($currentParentId);
            $currentParentId = $parentBranch ? $parentBranch->parent : null;
        }

        return false;
    }
} 