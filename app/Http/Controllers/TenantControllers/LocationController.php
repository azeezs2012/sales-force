<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\TenantModels\Location;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Validators\LocationValidator;

class LocationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $locations = Location::with(['childLocations', 'creator', 'updater', 'approver'])->get();
        return response()->json($locations);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = LocationValidator::validate($request->all());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->parent && !$this->hasValidParentHierarchy($request->parent)) {
            return response()->json(['error' => 'Parent hierarchy exceeds maximum depth of 5.'], 422);
        }

        $location = new Location($request->all());
        $location->created_by = auth()->id();
        $location->created_at = now();
        if ($location->approved) {
            $location->approved_by = auth()->id();
        }
        $location->save();

        return response()->json($location, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $location = Location::findOrFail($id);
        return response()->json($location);
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
        $location = Location::findOrFail($id);

        $validator = LocationValidator::validate($request->all(), $location->id);

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

        $location->fill($request->all());
        $location->updated_by = auth()->id();
        $location->updated_at = now();
        if ($location->approved) {
            $location->approved_by = auth()->id();
        }
        $location->save();

        return response()->json($location);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $location = Location::findOrFail($id);

        // Check if the location has child locations
        $hasChildren = Location::where('parent', $id)->exists();
        if ($hasChildren) {
            throw new \Exception('Cannot delete location with child locations.');
        }

        $location->deleted_by = auth()->id();
        $location->deleted_at = now();
        $location->save();
        $location->delete();

        return response()->json(null, 204);
    }

    /**
     * Check if the location has a valid parent hierarchy.
     *
     * @param  int  $locationId
     * @return bool
     */
    private function hasValidParentHierarchy($locationId)
    {
        $depth = 0;
        $currentLocation = Location::find($locationId);

        while ($currentLocation && $currentLocation->parent && $depth < 5) {
            $currentLocation = Location::find($currentLocation->parent);
            $depth++;
        }

        return $depth < 5;
    }

    /**
     * Check for circular reference when setting a parent.
     *
     * @param  int  $locationId
     * @param  int  $parentId
     * @return bool
     */
    private function hasCircularReference($locationId, $parentId)
    {
        // A location cannot be its own parent
        if ($locationId == $parentId) {
            return true;
        }

        $visited = [];
        $currentParentId = $parentId;

        while ($currentParentId) {
            // Check if we've already visited this parent (circular reference)
            if (in_array($currentParentId, $visited)) {
                return true;
            }

            // Check if the current parent is the location we're trying to update
            if ($currentParentId == $locationId) {
                return true;
            }

            $visited[] = $currentParentId;
            $parentLocation = Location::find($currentParentId);
            $currentParentId = $parentLocation ? $parentLocation->parent : null;
        }

        return false;
    }
} 