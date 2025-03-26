<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\TenantModels\SalesRep;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Validators\SalesRepValidator;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SalesRepController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $salesReps = SalesRep::with(['childSalesReps', 'creator', 'updater', 'approver', 'user'])->get();
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
            throw new \Exception('Parent hierarchy exceeds maximum depth of 5.');
        }

        DB::transaction(function () use ($request) {
            // Create a user for the sales rep
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email, // Assuming email is provided in the request
                'password' => bcrypt('defaultpassword'), // Set a default password or generate one
                'role' => 'sales_rep',
            ]);

            $salesRep = new SalesRep();
            $salesRep->code = $request->code;
            $salesRep->active = $request->active;
            $salesRep->approved = $request->approved;
            $salesRep->parent = $request->parent;
            $salesRep->created_by = auth()->id();
            $salesRep->created_at = now();
            $salesRep->user_id = $user->id;
            if ($salesRep->approved) {
                $salesRep->approved_by = auth()->id();
            }
            $salesRep->save();
        });

        return response()->json(['message' => 'Sales representative created successfully.'], 201);
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
            throw new \Exception('Parent hierarchy exceeds maximum depth of 5.');
        }

        DB::transaction(function () use ($request, $salesRep) {
            // Update the user associated with the sales rep
            $user = $salesRep->user;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->save();

            $salesRep->fill($request->all());
            $salesRep->updated_by = auth()->id();
            $salesRep->updated_at = now();
            if ($salesRep->approved) {
                $salesRep->approved_by = auth()->id();
            }
            $salesRep->save();
        });

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
        DB::transaction(function () use ($id) {
            $salesRep = SalesRep::findOrFail($id);

            // Check if the sales representative has child sales representatives
            $hasChildren = SalesRep::where('parent', $id)->exists();
            if ($hasChildren) {
                throw new \Exception('Cannot delete sales representative with child sales representatives.');
            }

            // Soft delete the sales rep first
            $salesRep->deleted_by = auth()->id();
            $salesRep->deleted_at = now();
            $salesRep->save();

            $user = $salesRep->user;

            $salesRep->delete();

            if ($user) {
                $user->delete(); // Force delete the user to avoid foreign key issues
            }
            
        });

        return response()->json(['message' => 'Sales representative deleted successfully.'], 204);
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