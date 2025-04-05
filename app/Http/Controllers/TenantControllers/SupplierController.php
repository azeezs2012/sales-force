<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\TenantModels\Supplier;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Validators\SupplierValidator;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Controller for managing suppliers.
 */
class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $suppliers = Supplier::with(['creator', 'updater', 'approver', 'user'])->get();
        return response()->json($suppliers);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request The request containing supplier data
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = SupplierValidator::validate($request->all());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::transaction(function () use ($request) {
            // Create a user for the supplier
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password ?? 'password'),
                'role' => 'supplier',
            ]);

            $supplier = new Supplier();
            $supplier->supplier_code = $request->supplier_code;
            $supplier->phone_no = $request->phone_no;
            $supplier->default_payment_method = $request->default_payment_method;
            $supplier->default_payment_term = $request->default_payment_term;
            $supplier->active = $request->active;
            $supplier->approved = $request->approved;
            $supplier->created_by = auth()->id();
            $supplier->created_at = now();
            $supplier->user_id = $user->id;
            if ($supplier->approved) {
                $supplier->approved_by = auth()->id();
            }
            $supplier->save();
        });

        return response()->json(['message' => 'Supplier created successfully.'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id The ID of the supplier to show
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $supplier = Supplier::findOrFail($id);
        return response()->json($supplier);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request The request containing updated supplier data
     * @param int $id The ID of the supplier to update
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $validator = SupplierValidator::validate($request->all(), $supplier->id);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        DB::transaction(function () use ($request, $supplier) {
            // Update the user associated with the supplier
            $user = $supplier->user;
            $user->name = $request->name;
            $user->email = $request->email;
            if ($request->password) {
                $user->password = bcrypt($request->password);
            }
            $user->save();

            $supplier->fill($request->all());
            $supplier->updated_by = auth()->id();
            $supplier->updated_at = now();
            if ($supplier->approved) {
                $supplier->approved_by = auth()->id();
            }
            $supplier->save();
        });

        return response()->json($supplier);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id The ID of the supplier to delete
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $supplier = Supplier::findOrFail($id);

            // Soft delete the supplier first
            $supplier->deleted_by = auth()->id();
            $supplier->deleted_at = now();
            $supplier->save();

            $user = $supplier->user;

            $supplier->delete();

            if ($user) {
                $user->delete(); // Force delete the user to avoid foreign key issues
            }
        });

        return response()->json(['message' => 'Supplier deleted successfully.'], 204);
    }
} 