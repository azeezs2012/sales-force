<?php

namespace App\Http\Controllers\TenantControllers;

use App\Models\TenantModels\Customer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Validators\CustomerValidator;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $customers = Customer::with(['creator', 'updater', 'approver', 'user', 'childCustomers'])->get();
        return response()->json($customers);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = CustomerValidator::validate($request->all());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->parent && !$this->hasValidParentHierarchy($request->parent)) {
            throw new \Exception('Parent hierarchy exceeds maximum depth of 5.');
        }

        DB::transaction(function () use ($request) {
            // Create a user for the customer
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email, // Assuming email is provided in the request
                'password' => bcrypt('defaultpassword'), // Set a default password or generate one
                'role' => 'customer',
            ]);

            $customer = new Customer();
            $customer->customer_code = $request->customer_code;
            $customer->credit_limit = $request->credit_limit;
            $customer->phone_no = $request->phone_no;
            $customer->default_payment_method = $request->default_payment_method;
            $customer->default_payment_term = $request->default_payment_term;
            $customer->active = $request->active;
            $customer->approved = $request->approved;
            $customer->parent = $request->parent;
            $customer->created_by = auth()->id();
            $customer->created_at = now();
            $customer->user_id = $user->id;
            if ($customer->approved) {
                $customer->approved_by = auth()->id();
            }
            $customer->save();
        });

        return response()->json(['message' => 'Customer created successfully.'], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $customer = Customer::findOrFail($id);
        return response()->json($customer);
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
        $customer = Customer::findOrFail($id);

        $validator = CustomerValidator::validate($request->all(), $customer->id);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->parent && !$this->hasValidParentHierarchy($request->parent)) {
            throw new \Exception('Parent hierarchy exceeds maximum depth of 5.');
        }

        DB::transaction(function () use ($request, $customer) {
            // Update the user associated with the customer
            $user = $customer->user;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->save();

            $customer->fill($request->all());
            $customer->parent = $request->parent;
            $customer->updated_by = auth()->id();
            $customer->updated_at = now();
            if ($customer->approved) {
                $customer->approved_by = auth()->id();
            }
            $customer->save();
        });

        return response()->json($customer);
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
            $customer = Customer::findOrFail($id);

            // Check if the customer has child customers
            $hasChildren = Customer::where('parent', $id)->exists();
            if ($hasChildren) {
                throw new \Exception('Cannot delete customer with child customers.');
            }

            // Soft delete the customer first
            $customer->deleted_by = auth()->id();
            $customer->deleted_at = now();
            $customer->save();

            $user = $customer->user;

            $customer->delete();

            if ($user) {
                $user->delete(); // Force delete the user to avoid foreign key issues
            }
        });

        return response()->json(['message' => 'Customer deleted successfully.'], 204);
    }

    private function hasValidParentHierarchy($customerId)
    {
        $depth = 0;
        $currentCustomer = Customer::find($customerId);

        while ($currentCustomer && $currentCustomer->parent && $depth < 5) {
            $currentCustomer = Customer::find($currentCustomer->parent);
            $depth++;
        }

        return $depth < 5;
    }
} 